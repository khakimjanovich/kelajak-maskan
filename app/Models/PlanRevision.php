<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['want_id', 'version', 'plan_text', 'grounded_summary'])]
class PlanRevision extends Model
{
    public function want(): BelongsTo
    {
        return $this->belongsTo(Want::class);
    }

    public function actionRuns(): HasMany
    {
        return $this->hasMany(ActionRun::class);
    }
}
