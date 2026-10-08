<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jedna wiadomość w wątku zgłoszenia "Pomoc" — 'me' (od administratora CMS-a)
 * albo 'agent' (odpowiedź obsługi, dociągnięta przez helpdesk:sync).
 */
class SupportTicketMessage extends Model
{
    protected $fillable = ['support_ticket_id', 'sender_type', 'remote_message_id', 'body'];

    protected static function booted(): void
    {
        static::creating(function (self $message) {
            if ($message->sender_type === 'me') {
                $message->remote_message_id ??= (string) Str::uuid();
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
