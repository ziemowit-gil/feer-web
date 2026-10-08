<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Zgłoszenie w panelu "Pomoc" — lokalne odzwierciedlenie wątku prowadzonego
 * w Helpdesku Centralnym. Patrz App\Support\Helpdesk, helpdesk:sync.
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id', 'remote_id', 'reference', 'subject', 'status',
        'submitted_by', 'last_synced_at', 'submit_error',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            $ticket->external_id ??= (string) Str::uuid();
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function submitted(): bool
    {
        return $this->remote_id !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'closed', 'resolved' => 'Zamknięte',
            'pending' => 'Oczekuje',
            default => 'Otwarte',
        };
    }
}
