<?php

namespace App\Data\History;

final class CreateWantData
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $title,
        public readonly string $rawText,
        public readonly string $status,
    ) {}
}
