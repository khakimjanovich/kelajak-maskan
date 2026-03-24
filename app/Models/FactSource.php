<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['validation_run_id', 'label', 'url', 'status', 'notes'])]
class FactSource extends Model
{
    public function validationRun(): BelongsTo
    {
        return $this->belongsTo(ValidationRun::class);
    }
}
