<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Słownik modułu strategii: punkt statutu, w ramach którego realizowane jest działanie. */
class StrategyStatuteRef extends Model
{
    protected $fillable = ['code', 'title', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(StrategyPlan::class, 'statute_ref_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('code');
    }

    /** Pełna etykieta do list wyboru, np. „§8 pkt 2 — Wsparcie osób…". */
    public function fullLabel(): string
    {
        return $this->code . ' — ' . $this->title;
    }
}
