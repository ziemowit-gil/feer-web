<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan działania w module „Strategia organizacji" — pojedyncze działanie
 * operacyjne osadzone w miesiącu/roku, powiązane ze słownikami (typ działania,
 * punkt statutu, źródło finansowania), grupami docelowymi i alokacją zasobów.
 */
class StrategyPlan extends Model
{
    use LogsActivity;

    public const STATUSES = [
        'planned'     => 'Planowane',
        'in_progress' => 'W realizacji',
        'done'        => 'Zrealizowane',
        'cancelled'   => 'Odwołane',
    ];

    public const MONTHS = [
        1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
        5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
        9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień',
    ];

    protected $fillable = [
        'title', 'description', 'year', 'month', 'status',
        'action_type_id', 'statute_ref_id', 'funding_source_id',
        'budget_planned', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year'           => 'integer',
            'month'          => 'integer',
            'budget_planned' => 'decimal:2',
        ];
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(StrategyActionType::class, 'action_type_id');
    }

    public function statuteRef(): BelongsTo
    {
        return $this->belongsTo(StrategyStatuteRef::class, 'statute_ref_id');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(StrategyFundingSource::class, 'funding_source_id');
    }

    public function targetGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            StrategyTargetGroup::class,
            'strategy_plan_target_group',
            'strategy_plan_id',
            'strategy_target_group_id'
        );
    }

    public function resources(): HasMany
    {
        return $this->hasMany(StrategyPlanResource::class)->orderBy('type')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Zawężenie do jednego roku planistycznego. */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }
}
