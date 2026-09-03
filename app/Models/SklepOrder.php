<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SklepOrder extends Model
{
    public const STATUSES = [
        'pending' => 'Oczekujące',
        'paid' => 'Opłacone',
        'failed' => 'Nieudane',
        'refunded' => 'Zwrócone',
    ];

    protected $fillable = [
        'educational_material_id',
        'buyer_name',
        'buyer_email',
        'user_id',
        'session_id',
        'p24_order_id',
        'status',
        'amount_grosze',
        'currency',
        'access_token',
        'access_delivered_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'p24_order_id' => 'integer',
            'amount_grosze' => 'integer',
            'access_delivered_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->session_id ??= (string) Str::uuid();
            $order->access_token ??= Str::random(48);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'session_id';
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(EducationalMaterial::class, 'educational_material_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markPaid(array $payload): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->update([
            'status' => 'paid',
            'p24_order_id' => $payload['orderId'] ?? $this->p24_order_id,
            'payload' => $payload,
        ]);
    }
}
