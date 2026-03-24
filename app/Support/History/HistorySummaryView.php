<?php

namespace App\Support\History;

use App\Models\Project;
use App\Models\Want;

final class HistorySummaryView
{
    /**
     * @param  array<int, Want>  $recentWants
     * @param  array<int, string>  $unresolvedIssues
     */
    public function __construct(
        public readonly Project $project,
        public readonly array $recentWants,
        public readonly ?HistoryCycleView $openCycle,
        public readonly ?HistoryCycleView $latestCompletedOutcome,
        public readonly array $unresolvedIssues,
    ) {}
}
