<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subskrypcja Web Push — endpoint i klucze przeglądarki użytkownika.
 *
 * @property int         $id
 * @property string      $endpoint
 * @property string      $p256dh_key
 * @property string      $auth_token
 * @property int|null    $user_id
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'p256dh_key',
        'auth_token',
        'user_id',
    ];

    /** Opcjonalnie powiązany użytkownik panelu. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
