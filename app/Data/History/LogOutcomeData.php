<?php

namespace App\Data\History;

final class LogOutcomeData
{
    public function __construct(
        public readonly int $actionRunId,
        public readonly string $outcome,
        public readonly string $reflection,
    ) {}
}
