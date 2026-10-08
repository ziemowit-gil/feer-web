<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pojedyncza pozycja alokacji zasobów planu działania:
 * zasób ludzki (opcjonalnie powiązany z użytkownikiem panelu),
 * sprzętowy lub finansowy (kwota w polu `cost`).
 */
class StrategyPlanResource extends Model
{
    public const TYPES = [
        'human'     => 'Zasób ludzki',
        'equipment' => 'Sprzęt',
        'financial' => 'Środki finansowe',
    ];

    protected $fillable = [
        'strategy_plan_id', 'type', 'name', 'user_id',
        'quantity', 'unit', 'cost', 'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost'     => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StrategyPlan::class, 'strategy_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
