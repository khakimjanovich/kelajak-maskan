<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['action_run_id', 'outcome', 'reflection'])]
class OutcomeLog extends Model
{
    public function actionRun(): BelongsTo
    {
        return $this->belongsTo(ActionRun::class);
    }
}
