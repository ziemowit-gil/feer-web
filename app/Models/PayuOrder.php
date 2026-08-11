<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayuOrder extends Model
{
    protected $fillable = [
        'user_id',
        'payu_order_id',
        'plan_slug',
        'podcast_id',
        'status',
        'amount_grosze',
        'currency',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'amount_grosze' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }
}
