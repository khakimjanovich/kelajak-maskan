<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Project extends Model
{
    public function projectContext(): HasOne
    {
        return $this->hasOne(ProjectContext::class);
    }

    public function wants(): HasMany
    {
        return $this->hasMany(Want::class);
    }
}
