<?php

namespace App\Data\History;

final class CreateActionRunData
{
    public function __construct(
        public readonly int $planRevisionId,
        public readonly string $status,
        public readonly ?string $startedAt,
        public readonly ?string $finishedAt,
    ) {}
}
