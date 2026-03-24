<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectContext;
use App\Support\ProjectContext\ProjectContextPayload;
use Illuminate\Console\Command;

class ProjectRefreshContext extends Command
{
    protected $signature = 'project:refresh-context {project=kelajak-maskan}';

    protected $description = 'Refresh the stored project context for a project from current app facts.';

    public function handle(): int
    {
        $projectSlug = (string) $this->argument('project');
        $project = Project::query()
            ->where('slug', $projectSlug)
            ->first();

        if ($project === null) {
            $this->error("Project [$projectSlug] not found.");

            return self::FAILURE;
        }

        $payload = match ($project->slug) {
            'kelajak-maskan' => ProjectContextPayload::forKelajakMaskan(base_path()),
            default => null,
        };

        if ($payload === null) {
            $this->error("Project context refresh for [$projectSlug] is not defined.");

            return self::FAILURE;
        }

        ProjectContext::query()->updateOrCreate(
            ['project_id' => $project->id],
            $payload,
        );

        $this->info(sprintf('Project context refreshed for %s (%s).', $project->name, $project->slug));

        return self::SUCCESS;
    }
}
