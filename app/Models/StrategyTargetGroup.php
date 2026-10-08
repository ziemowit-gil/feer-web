<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Słownik modułu strategii: grupa docelowa działań (np. seniorzy, młodzież). */
class StrategyTargetGroup extends Model
{
    protected $fillable = ['name', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            StrategyPlan::class,
            'strategy_plan_target_group',
            'strategy_target_group_id',
            'strategy_plan_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
