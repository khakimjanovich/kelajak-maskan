<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'summary',
    'repo_path',
    'primary_branch',
    'stack',
    'commands',
    'conventions',
    'key_paths',
    'current_phase',
    'source_refs',
])]
class ProjectContext extends Model
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stack' => 'array',
            'commands' => 'array',
            'conventions' => 'array',
            'key_paths' => 'array',
            'source_refs' => 'array',
        ];
    }
}
