<?php

namespace App\Support\Dashboard;

use App\Models\Project;
use App\Models\ProjectContext;
use App\Support\History\HistoryCycleView;
use App\Support\History\HistoryReader;
use RuntimeException;

final class KelajakMaskanDashboardData
{
    private const PROJECT_SLUG = 'kelajak-maskan';

    private const CAPABILITY_COMMANDS = [
        'history:latest-want',
        'history:summary',
        'history:open-cycle',
        'history:record-cycle',
        'project:context',
        'project:refresh-context',
    ];

    public function __construct(
        private readonly HistoryReader $historyReader,
    ) {}

    /**
     * @return array{
     *     project: Project,
     *     projectContext: ProjectContext,
     *     recentWants: array<int, \App\Models\Want>,
     *     openCycle: ?HistoryCycleView,
     *     latestCompletedOutcome: ?HistoryCycleView,
     *     capabilities: array<int, string>,
     *     availableActions: array<int, string>
     * }
     */
    public function build(): array
    {
        $project = Project::query()
            ->with('projectContext')
            ->where('slug', self::PROJECT_SLUG)
            ->first();

        if ($project === null) {
            throw new RuntimeException('Dashboard project [kelajak-maskan] not found.');
        }

        $projectContext = $project->projectContext;

        if (! $projectContext instanceof ProjectContext) {
            throw new RuntimeException('Dashboard project context for [kelajak-maskan] not found.');
        }

        $historySummary = $this->historyReader->summary(self::PROJECT_SLUG);

        return [
            'project' => $project,
            'projectContext' => $projectContext,
            'recentWants' => $historySummary->recentWants,
            'openCycle' => $historySummary->openCycle,
            'latestCompletedOutcome' => $historySummary->latestCompletedOutcome,
            'capabilities' => $this->capabilities($projectContext),
            'availableActions' => $this->availableActions(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function capabilities(ProjectContext $projectContext): array
    {
        return array_values(array_filter(
            array_map(
                static fn (string $command): string => str_replace('php artisan ', '', $command),
                $projectContext->commands ?? [],
            ),
            static fn (string $command): bool => in_array($command, self::CAPABILITY_COMMANDS, true),
        ));
    }

    /**
     * @return array<int, string>
     */
    private function availableActions(): array
    {
        return [
            'Refresh project context',
            'Inspect latest want',
            'Inspect open cycle',
            'Inspect project summary',
            'Record a new cycle',
        ];
    }
}
