<?php

namespace App\Data\History;

final class SaveConstraintSnapshotData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly int $wantId,
        public readonly array $payload,
    ) {}
}
