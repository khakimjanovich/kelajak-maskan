<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'title', 'raw_text', 'status'])]
class Want extends Model
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function constraintSnapshots(): HasMany
    {
        return $this->hasMany(ConstraintSnapshot::class);
    }

    public function validationRuns(): HasMany
    {
        return $this->hasMany(ValidationRun::class);
    }

    public function planRevisions(): HasMany
    {
        return $this->hasMany(PlanRevision::class);
    }
}
