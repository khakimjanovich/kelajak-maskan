<?php

namespace App\Support\History;

use App\Models\ActionRun;
use App\Models\ConstraintSnapshot;
use App\Models\OutcomeLog;
use App\Models\PlanRevision;
use App\Models\Project;
use App\Models\ValidationRun;
use App\Models\Want;

final class HistoryCycleView
{
    public function __construct(
        public readonly Project $project,
        public readonly Want $want,
        public readonly ?ConstraintSnapshot $constraintSnapshot,
        public readonly ?ValidationRun $validationRun,
        public readonly int $factSourceCount,
        public readonly ?PlanRevision $planRevision,
        public readonly ?ActionRun $actionRun,
        public readonly ?OutcomeLog $outcomeLog,
        public readonly ?string $openReason,
    ) {}
}
