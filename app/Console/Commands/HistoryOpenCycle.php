<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\History\HistoryReader;
use Illuminate\Console\Command;

class HistoryOpenCycle extends Command
{
    protected $signature = 'history:open-cycle {project=kelajak-maskan}';

    protected $description = 'Read the newest open history cycle for a project.';

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

        $cycle = $historyReader->openCycle($projectSlug);

        if ($cycle === null) {
            $this->info("Project [$projectSlug] has no open cycle.");

            return self::SUCCESS;
        }

        $this->line(sprintf('Project: %s (%s)', $cycle->project->name, $cycle->project->slug));
        $this->line(sprintf('Open want id: %d', $cycle->want->id));
        $this->line(sprintf('Open want title: %s', $cycle->want->title));
        $this->line(sprintf('Want status: %s', $cycle->want->status));
        $this->line(sprintf('Validation facts status: %s', $cycle->validationRun?->facts_status ?? 'none'));
        $this->line(sprintf('Validation constraints status: %s', $cycle->validationRun?->constraints_status ?? 'none'));
        $this->line(sprintf('Validation experience status: %s', $cycle->validationRun?->experience_status ?? 'none'));
        $this->line(sprintf('Validation ikhlas status: %s', $cycle->validationRun?->ikhlas_status ?? 'none'));
        $this->line(sprintf(
            'Plan revision: %s',
            $cycle->planRevision === null ? 'none' : sprintf('v%d', $cycle->planRevision->version),
        ));
        $this->line(sprintf('Action status: %s', $cycle->actionRun?->status ?? 'none'));
        $this->line(sprintf('Reason: %s', $cycle->openReason ?? 'none'));

        return self::SUCCESS;
    }
}
