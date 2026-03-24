<?php

namespace App\Data\History;

final class CreateProjectData
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
    ) {}
}
