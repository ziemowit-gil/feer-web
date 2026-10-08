<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Słownik modułu strategii: typ działania (np. warsztaty, doradztwo). */
class StrategyActionType extends Model
{
    protected $fillable = ['name', 'icon', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(StrategyPlan::class, 'action_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
