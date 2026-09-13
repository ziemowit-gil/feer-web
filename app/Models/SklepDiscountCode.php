<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SklepDiscountCode extends Model
{
    public const TYPES = [
        'percent' => 'Procentowy',
        'fixed' => 'Kwotowy',
    ];

    protected $fillable = [
        'code',
        'type',
        'value',
        'valid_from',
        'valid_until',
        'max_uses',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotalGrosze): int
    {
        $discount = $this->type === 'percent'
            ? (int) round($subtotalGrosze * $this->value / 100)
            : $this->value;

        return max(0, min($discount, $subtotalGrosze));
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
