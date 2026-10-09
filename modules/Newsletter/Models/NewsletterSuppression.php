<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

/** Lista tłumienia: hashe adresów, na które nie wolno wysyłać (anonimizacja, czarna lista, FBL). */
class NewsletterSuppression extends Model
{
    protected $table = 'newsletter_suppressions';

    protected $fillable = ['email_hash', 'reason', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public static function contains(string $emailHash): bool
    {
        return static::where('email_hash', $emailHash)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
