<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Klucz dostępu (passkey / WebAuthn) użytkownika — logowanie bez hasła (App\Services\Auth\WebAuthn). */
class WebauthnCredential extends Model
{
    protected $fillable = ['user_id', 'name', 'credential_id', 'public_key', 'algorithm', 'sign_count', 'transports', 'last_used_at'];

    protected $hidden = ['public_key'];

    protected $casts = [
        'transports' => 'array',
        'last_used_at' => 'datetime',
        'sign_count' => 'integer',
        'algorithm' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
