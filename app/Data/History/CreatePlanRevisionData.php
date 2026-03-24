<?php

namespace App\Data\History;

final class CreatePlanRevisionData
{
    public function __construct(
        public readonly int $wantId,
        public readonly int $version,
        public readonly string $planText,
        public readonly string $groundedSummary,
    ) {}
}
