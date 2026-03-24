<?php

namespace App\Support\History;

use App\Models\Project;
use App\Models\Want;
use RuntimeException;

final class HistoryReader
{
    private const TERMINAL_ACTION_STATUSES = [
        'completed',
        'completed_with_defect_discovery',
        'failed',
        'abandoned',
        'superseded',
    ];

    public function latestWant(string $projectSlug): ?HistoryCycleView
    {
        $project = $this->findProject($projectSlug);

        if ($project === null) {
            return null;
        }

        $want = $this->loadWants($project, limit: 1)->first();

        if ($want === null) {
            return null;
        }

        return $this->buildCycleView($project, $want);
    }

    public function openCycle(string $projectSlug): ?HistoryCycleView
    {
        $project = $this->findProject($projectSlug);

        if ($project === null) {
            return null;
        }

        foreach ($this->loadWants($project) as $want) {
            $view = $this->buildCycleView($project, $want);

            if ($view->openReason !== null) {
                return $view;
            }
        }

        return null;
    }

    public function summary(string $projectSlug, int $recentWantLimit = 3): HistorySummaryView
    {
        $project = $this->findProject($projectSlug);

        if ($project === null) {
            throw new RuntimeException("Project [$projectSlug] not found.");
        }

        $cycleViews = [];

        foreach ($this->loadWants($project) as $want) {
            $cycleViews[] = $this->buildCycleView($project, $want);
        }

        $openCycle = $this->firstOpenCycle($cycleViews);
        $latestCompletedOutcome = $this->firstCompletedCycleWithOutcome($cycleViews);
        $recentWants = Want::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($recentWantLimit)
            ->get()
            ->all();

        return new HistorySummaryView(
            project: $project,
            recentWants: $recentWants,
            openCycle: $openCycle,
            latestCompletedOutcome: $latestCompletedOutcome,
            unresolvedIssues: $this->buildUnresolvedIssues($cycleViews, $openCycle),
        );
    }

    /**
     * @return array<int, string>
     */
    public function terminalActionStatuses(): array
    {
        return self::TERMINAL_ACTION_STATUSES;
    }

    private function findProject(string $projectSlug): ?Project
    {
        return Project::query()
            ->where('slug', $projectSlug)
            ->first();
    }

    private function loadWants(Project $project, ?int $limit = null)
    {
        $query = Want::query()
            ->where('project_id', $project->id)
            ->with([
                'constraintSnapshots' => fn ($query) => $this->applyLatestOrder($query),
                'validationRuns' => fn ($query) => $this->applyLatestOrder($query)
                    ->with([
                        'factSources' => fn ($query) => $this->applyLatestOrder($query),
                    ]),
                'planRevisions' => fn ($query) => $this->applyLatestOrder($query)
                    ->with([
                        'actionRuns' => fn ($query) => $this->applyLatestOrder($query)
                            ->with([
                                'outcomeLogs' => fn ($query) => $this->applyLatestOrder($query),
                            ]),
                    ]),
            ]);

        $this->applyLatestOrder($query);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function buildCycleView(Project $project, Want $want): HistoryCycleView
    {
        $constraintSnapshot = $want->constraintSnapshots->first();
        $validationRun = $want->validationRuns->first();
        $planRevision = $want->planRevisions->first();
        $actionRun = $planRevision?->actionRuns->first();
        $outcomeLog = $actionRun?->outcomeLogs->first();

        return new HistoryCycleView(
            project: $project,
            want: $want,
            constraintSnapshot: $constraintSnapshot,
            validationRun: $validationRun,
            factSourceCount: $validationRun?->factSources->count() ?? 0,
            planRevision: $planRevision,
            actionRun: $actionRun,
            outcomeLog: $outcomeLog,
            openReason: $this->openReason($planRevision, $actionRun, $outcomeLog),
        );
    }

    private function applyLatestOrder($query)
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function openReason(mixed $planRevision, mixed $actionRun, mixed $outcomeLog): ?string
    {
        if ($planRevision === null) {
            return 'No plan revision recorded yet.';
        }

        if ($actionRun === null) {
            return 'No action run recorded yet.';
        }

        if (! $this->isTerminalStatus($actionRun->status)) {
            return sprintf('Latest action status [%s] is not terminal.', $actionRun->status);
        }

        if ($outcomeLog === null) {
            return 'No outcome log recorded for the latest action run.';
        }

        return null;
    }

    private function isTerminalStatus(?string $status): bool
    {
        return $status !== null && in_array($status, self::TERMINAL_ACTION_STATUSES, true);
    }

    /**
     * @param  array<int, HistoryCycleView>  $cycleViews
     * @return array<int, string>
     */
    private function buildUnresolvedIssues(array $cycleViews, ?HistoryCycleView $openCycle): array
    {
        $issues = [];

        if ($openCycle !== null) {
            $issues[] = sprintf(
                'Open cycle: want #%d %s. %s',
                $openCycle->want->id,
                $openCycle->want->title,
                $openCycle->openReason,
            );
        }

        if ($openCycle?->actionRun !== null && ! $this->isTerminalStatus($openCycle->actionRun->status)) {
            $issues[] = sprintf(
                'Latest action for want #%d is still [%s].',
                $openCycle->want->id,
                $openCycle->actionRun->status,
            );
        }

        foreach ($cycleViews as $cycleView) {
            $defectText = $this->defectText($cycleView);

            if ($defectText !== null) {
                $issues[] = $defectText;

                break;
            }
        }

        return $issues;
    }

    /**
     * @param  array<int, HistoryCycleView>  $cycleViews
     */
    private function firstOpenCycle(array $cycleViews): ?HistoryCycleView
    {
        foreach ($cycleViews as $cycleView) {
            if ($cycleView->openReason !== null) {
                return $cycleView;
            }
        }

        return null;
    }

    /**
     * @param  array<int, HistoryCycleView>  $cycleViews
     */
    private function firstCompletedCycleWithOutcome(array $cycleViews): ?HistoryCycleView
    {
        foreach ($cycleViews as $cycleView) {
            if ($cycleView->outcomeLog !== null && $this->isTerminalStatus($cycleView->actionRun?->status)) {
                return $cycleView;
            }
        }

        return null;
    }

    private function defectText(HistoryCycleView $cycleView): ?string
    {
        $outcome = $cycleView->outcomeLog?->outcome;
        $reflection = $cycleView->outcomeLog?->reflection;

        if ($this->containsDefect($outcome)) {
            return $outcome;
        }

        if ($this->containsDefect($reflection)) {
            return $reflection;
        }

        return null;
    }

    private function containsDefect(?string $text): bool
    {
        return $text !== null && str_contains(strtolower($text), 'defect');
    }
}
