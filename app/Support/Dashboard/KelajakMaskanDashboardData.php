<?php

namespace App\Support\Dashboard;

use App\Models\Project;
use App\Models\ProjectContext;
use App\Support\History\HistoryCycleView;
use App\Support\History\HistoryReader;
use App\Support\ProjectContext\ProjectContextPayload;
use Illuminate\Support\Facades\Schema;

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
     *     availableActions: array<int, string>,
     *     isReady: bool
     * }
     */
    public function build(): array
    {
        if (! Schema::hasTable('projects') || ! Schema::hasTable('project_contexts')) {
            return $this->emptyState();
        }

        $project = Project::query()
            ->with('projectContext')
            ->where('slug', self::PROJECT_SLUG)
            ->first();

        if ($project === null || ! $project->projectContext instanceof ProjectContext) {
            return $this->emptyState();
        }

        $historySummary = $this->historyReader->summary(self::PROJECT_SLUG);

        return [
            'project' => $project,
            'projectContext' => $project->projectContext,
            'recentWants' => $historySummary->recentWants,
            'openCycle' => $historySummary->openCycle,
            'latestCompletedOutcome' => $historySummary->latestCompletedOutcome,
            'capabilities' => $this->capabilities($project->projectContext),
            'availableActions' => $this->availableActions(),
            'isReady' => true,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function capabilities(ProjectContext $projectContext): array
    {
        return $this->capabilitiesFromCommands($projectContext->commands ?? []);
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

    /**
     * @param  array<int, string>  $commands
     * @return array<int, string>
     */
    private function capabilitiesFromCommands(array $commands): array
    {
        return array_values(array_filter(
            array_map(
                static fn (string $command): string => str_replace('php artisan ', '', $command),
                $commands,
            ),
            static fn (string $command): bool => in_array($command, self::CAPABILITY_COMMANDS, true),
        ));
    }

    /**
     * @return array{
     *     project: Project,
     *     projectContext: ProjectContext,
     *     recentWants: array<int, \App\Models\Want>,
     *     openCycle: null,
     *     latestCompletedOutcome: null,
     *     capabilities: array<int, string>,
     *     availableActions: array<int, string>,
     *     isReady: bool
     * }
     */
    private function emptyState(): array
    {
        $payload = ProjectContextPayload::forKelajakMaskan(base_path());

        $project = new Project();
        $project->forceFill([
            'name' => 'Kelajak-Maskan',
            'slug' => self::PROJECT_SLUG,
        ]);

        $projectContext = new ProjectContext();
        $projectContext->forceFill($payload);

        return [
            'project' => $project,
            'projectContext' => $projectContext,
            'recentWants' => [],
            'openCycle' => null,
            'latestCompletedOutcome' => null,
            'capabilities' => $this->capabilitiesFromCommands($payload['commands']),
            'availableActions' => $this->availableActions(),
            'isReady' => false,
        ];
    }
}
