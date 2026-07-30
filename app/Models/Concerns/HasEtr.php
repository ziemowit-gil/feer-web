<?php

namespace App\Models\Concerns;

use App\Models\EtrContent;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasEtr
{
    public function etr(): MorphOne
    {
        return $this->morphOne(EtrContent::class, 'etrable');
    }

    public function hasActiveEtr(): bool
    {
        return $this->relationLoaded('etr')
            ? ($this->etr !== null && $this->etr->is_enabled)
            : $this->etr()->where('is_enabled', true)->exists();
    }
}
