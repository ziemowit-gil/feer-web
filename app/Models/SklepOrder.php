<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'buyer_name',
        'buyer_email',
        'user_id',
        'session_id',
        'p24_order_id',
        'status',
        'subtotal_grosze',
        'discount_code_id',
        'discount_amount_grosze',
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
            'subtotal_grosze' => 'integer',
            'discount_amount_grosze' => 'integer',
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

    public function items(): HasMany
    {
        return $this->hasMany(SklepOrderItem::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(SklepDiscountCode::class, 'discount_code_id');
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
