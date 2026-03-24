<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'want_id',
    'facts_status',
    'constraints_status',
    'experience_status',
    'ikhlas_status',
    'summary',
])]
class ValidationRun extends Model
{
    public function want(): BelongsTo
    {
        return $this->belongsTo(Want::class);
    }

    public function factSources(): HasMany
    {
        return $this->hasMany(FactSource::class);
    }
}
