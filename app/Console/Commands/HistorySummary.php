<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\History\HistoryReader;
use Illuminate\Console\Command;

class HistorySummary extends Command
{
    protected $signature = 'history:summary {project=kelajak-maskan}';

    protected $description = 'Read a compact history summary for a project.';

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

        $summary = $historyReader->summary($projectSlug);

        $this->line('Project');
        $this->line(sprintf('%s (%s)', $summary->project->name, $summary->project->slug));

        $this->line('Latest wants');

        if ($summary->recentWants === []) {
            $this->line('- none');
        } else {
            foreach ($summary->recentWants as $want) {
                $this->line(sprintf('- #%d %s [%s]', $want->id, $want->title, $want->status));
            }
        }

        $this->line('Open cycle');

        if ($summary->openCycle === null) {
            $this->line('- none');
        } else {
            $this->line(sprintf('- Want #%d', $summary->openCycle->want->id));
            $this->line(sprintf('- Title: %s', $summary->openCycle->want->title));
            $this->line(sprintf('- Action status: %s', $summary->openCycle->actionRun?->status ?? 'none'));
            $this->line(sprintf('- Reason: %s', $summary->openCycle->openReason ?? 'none'));
        }

        $this->line('Latest completed outcome');

        if ($summary->latestCompletedOutcome === null) {
            $this->line('- none');
        } else {
            $this->line(sprintf('- Want #%d', $summary->latestCompletedOutcome->want->id));
            $this->line(sprintf('- Title: %s', $summary->latestCompletedOutcome->want->title));
            $this->line(sprintf('- Outcome: %s', $summary->latestCompletedOutcome->outcomeLog?->outcome ?? 'none'));
        }

        $this->line('Unresolved issues');

        if ($summary->unresolvedIssues === []) {
            $this->line('- none');
        } else {
            foreach ($summary->unresolvedIssues as $issue) {
                $this->line(sprintf('- %s', $issue));
            }
        }

        return self::SUCCESS;
    }
}
