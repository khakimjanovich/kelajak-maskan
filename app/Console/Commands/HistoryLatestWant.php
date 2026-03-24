<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\History\HistoryReader;
use Illuminate\Console\Command;

class HistoryLatestWant extends Command
{
    protected $signature = 'history:latest-want {project=kelajak-maskan}';

    protected $description = 'Read the latest recorded want and its linked history context for a project.';

    public function handle(HistoryReader $historyReader): int
    {
        $projectSlug = (string) $this->argument('project');
        $project = Project::query()
            ->where('slug', $projectSlug)
            ->first();

        if ($project === null) {
            $this->error("Project [$projectSlug] not found.");

            return self::FAILURE;
        }

        $cycle = $historyReader->latestWant($projectSlug);

        if ($cycle === null) {
            $this->info("Project [$projectSlug] has no wants recorded yet.");

            return self::SUCCESS;
        }

        $this->line(sprintf('Project: %s (%s)', $cycle->project->name, $cycle->project->slug));
        $this->line(sprintf('Want id: %d', $cycle->want->id));
        $this->line(sprintf('Want title: %s', $cycle->want->title));
        $this->line(sprintf('Want status: %s', $cycle->want->status));
        $this->line(sprintf('Created: %s', (string) $cycle->want->created_at));
        $this->line(sprintf(
            'Constraint snapshot: %s',
            $cycle->constraintSnapshot === null
                ? 'none'
                : json_encode($cycle->constraintSnapshot->payload, JSON_UNESCAPED_SLASHES),
        ));
        $this->line(sprintf('Validation facts status: %s', $cycle->validationRun?->facts_status ?? 'none'));
        $this->line(sprintf('Validation constraints status: %s', $cycle->validationRun?->constraints_status ?? 'none'));
        $this->line(sprintf('Validation experience status: %s', $cycle->validationRun?->experience_status ?? 'none'));
        $this->line(sprintf('Validation ikhlas status: %s', $cycle->validationRun?->ikhlas_status ?? 'none'));
        $this->line(sprintf('Fact sources: %d', $cycle->factSourceCount));
        $this->line(sprintf(
            'Plan revision: %s',
            $cycle->planRevision === null ? 'none' : sprintf('v%d', $cycle->planRevision->version),
        ));
        $this->line(sprintf('Action status: %s', $cycle->actionRun?->status ?? 'none'));
        $this->line(sprintf('Outcome: %s', $cycle->outcomeLog?->outcome ?? 'none'));

        return self::SUCCESS;
    }
}
