<?php

namespace App\Support\Audit;

final class AuditContext
{
    public function __construct(
        public readonly string $actorType,
        public readonly ?string $actorRef = null,
    ) {}
}
