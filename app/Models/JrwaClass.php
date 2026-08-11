<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JrwaClass extends Model
{
    protected $fillable = [
        'symbol', 'name', 'category', 'notes',
        'flag', 'parent_symbol', 'sort_order',
    ];

    protected $casts = ['flag' => 'integer', 'sort_order' => 'integer'];

    // flag constants
    const FLAG_GROUP     = 0;
    const FLAG_LEAF      = 1;
    const FLAG_WITHDRAWN = 2;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(JrwaClass::class, 'parent_symbol', 'symbol');
    }

    public function children(): HasMany
    {
        return $this->hasMany(JrwaClass::class, 'parent_symbol', 'symbol')
                    ->orderBy('sort_order')
                    ->orderBy('symbol');
    }

    public function isWithdrawn(): bool
    {
        return $this->flag === self::FLAG_WITHDRAWN;
    }

    public function isLeaf(): bool
    {
        return $this->flag === self::FLAG_LEAF;
    }

    public function retentionLabel(): string
    {
        return match ($this->category) {
            'A'    => 'Trwale (A)',
            'Bc'   => 'Manipulacyjna (Bc)',
            'BE10' => 'B10 lub ekspertyza (BE10)',
            default => $this->category,
        };
    }

    /** Determine parent_symbol from numeric symbol (e.g. '001' → '00', '10' → '1'). */
    public static function inferParent(string $symbol): ?string
    {
        if (! ctype_digit($symbol)) {
            return null;
        }
        if (strlen($symbol) <= 1) {
            return null;
        }

        return substr($symbol, 0, -1);
    }

    /** Tree of active classes, keyed by symbol. */
    public static function tree(): Collection
    {
        return static::where('flag', '!=', self::FLAG_WITHDRAWN)
                     ->orderBy('sort_order')
                     ->orderBy('symbol')
                     ->get();
    }
}
