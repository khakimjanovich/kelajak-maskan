<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['plan_revision_id', 'status', 'started_at', 'finished_at'])]
class ActionRun extends Model
{
    public function planRevision(): BelongsTo
    {
        return $this->belongsTo(PlanRevision::class);
    }

    public function outcomeLogs(): HasMany
    {
        return $this->hasMany(OutcomeLog::class);
    }
}
