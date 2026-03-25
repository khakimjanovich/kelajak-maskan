<?php

namespace App\Data\History;

final class UpdateWantStatusData
{
    public function __construct(
        public readonly int $wantId,
        public readonly string $status,
    ) {}
}
