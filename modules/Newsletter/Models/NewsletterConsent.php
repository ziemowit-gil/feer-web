<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rejestr zgód (append-only): kto, kiedy, na co, skąd, jak potwierdzono i kiedy wycofano. */
class NewsletterConsent extends Model
{
    protected $table = 'newsletter_consents';

    public $timestamps = false;

    protected $fillable = [
        'subscriber_id', 'channel', 'clause_id', 'clause_version', 'clause_text', 'source', 'method',
        'ip_hash', 'user_agent', 'granted_at', 'confirmed_at', 'revoked_at', 'revoked_via',
    ];

    protected $casts = ['granted_at' => 'datetime', 'confirmed_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
