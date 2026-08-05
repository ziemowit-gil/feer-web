<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MemberInvitation extends Model
{
    protected $fillable = ['email', 'token', 'invited_by', 'note', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    public static function findValidByToken(string $token): ?self
    {
        return self::where('token', $token)
            ->whereNull('used_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    /** Czy e-mail ma aktywne (nieużyte, niewyekspirowane) zaproszenie. */
    public static function emailHasValidInvitation(string $email): bool
    {
        return self::where('email', strtolower(trim($email)))
            ->whereNull('used_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
