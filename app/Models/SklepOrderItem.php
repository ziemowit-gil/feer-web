<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SklepOrderItem extends Model
{
    protected $fillable = [
        'sklep_order_id',
        'educational_material_id',
        'title',
        'unit_price_grosze',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_grosze' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SklepOrder::class, 'sklep_order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(EducationalMaterial::class, 'educational_material_id');
    }
}
