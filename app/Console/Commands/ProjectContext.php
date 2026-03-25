<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectContext extends Command
{
    protected $signature = 'project:context {project=kelajak-maskan}';

    protected $description = 'Read the current stored project context for a project.';

    public function handle(): int
    {
        $projectSlug = (string) $this->argument('project');
        $project = Project::query()
            ->with('projectContext')
            ->where('slug', $projectSlug)
            ->first();

        if ($project === null) {
            $this->error("Project [$projectSlug] not found.");

            return self::FAILURE;
        }

        if ($project->projectContext === null) {
            $this->error("Project context for [$projectSlug] not found.");

            return self::FAILURE;
        }

        $this->output->writeln((string) json_encode([
            'project' => [
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'summary' => $project->projectContext->summary,
            'primary_branch' => $project->projectContext->primary_branch,
            'stack' => $project->projectContext->stack,
            'commands' => $project->projectContext->commands,
            'conventions' => $project->projectContext->conventions,
            'key_paths' => $project->projectContext->key_paths,
            'current_phase' => $project->projectContext->current_phase,
            'source_refs' => $project->projectContext->source_refs,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
