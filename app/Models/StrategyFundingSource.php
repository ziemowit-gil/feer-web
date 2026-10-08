<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Słownik modułu strategii: źródło finansowania (projekt, dotacja, środki własne). */
class StrategyFundingSource extends Model
{
    public const KINDS = [
        'project' => 'Projekt',
        'grant'   => 'Dotacja',
        'own'     => 'Środki własne',
    ];

    protected $fillable = ['name', 'kind', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(StrategyPlan::class, 'funding_source_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
