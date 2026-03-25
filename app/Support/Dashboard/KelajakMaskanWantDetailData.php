<?php

namespace App\Support\Dashboard;

use App\Models\Project;
use App\Models\ProjectContext;
use App\Support\History\HistoryReader;
use App\Support\ProjectContext\ProjectContextPayload;
use Illuminate\Support\Facades\Schema;

final class KelajakMaskanWantDetailData
{
    private const PROJECT_SLUG = 'kelajak-maskan';

    public function __construct(
        private readonly HistoryReader $historyReader,
        private readonly DashboardWantPresenter $presenter,
    ) {}

    /**
     * @return array{
     *     project: Project,
     *     projectContext: ProjectContext,
     *     wantView: array<string, mixed>,
     *     isReady: bool
     * }|null
     */
    public function build(int $wantId): ?array
    {
        if (! Schema::hasTable('projects') || ! Schema::hasTable('project_contexts') || ! Schema::hasTable('wants')) {
            return null;
        }

        $project = Project::query()
            ->with('projectContext')
            ->where('slug', self::PROJECT_SLUG)
            ->first();

        if ($project === null) {
            return null;
        }

        $cycle = $this->historyReader->cycleViewForWant(self::PROJECT_SLUG, $wantId);

        if ($cycle === null) {
            return null;
        }

        return [
            'project' => $project,
            'projectContext' => $project->projectContext instanceof ProjectContext
                ? $project->projectContext
                : $this->fallbackProjectContext(),
            'wantView' => $this->presenter->present($cycle),
            'isReady' => $project->projectContext instanceof ProjectContext,
        ];
    }

    private function fallbackProjectContext(): ProjectContext
    {
        $projectContext = new ProjectContext;
        $projectContext->forceFill(ProjectContextPayload::forKelajakMaskan(base_path()));

        return $projectContext;
    }
}
