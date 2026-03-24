<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'action_name',
    'actor_type',
    'actor_ref',
    'target_type',
    'target_id',
    'status',
    'input_payload',
    'result_payload',
    'error_message',
])]
class AuditLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'result_payload' => 'array',
        ];
    }
}
