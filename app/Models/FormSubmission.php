<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_definition_id',
        'data',
        'ip_address',
        'read_at',
        'szo_contact_id',
        'szo_synced_at',
        'szo_error',
    ];

    protected $casts = [
        'data'          => 'array',
        'read_at'       => 'datetime',
        'szo_synced_at' => 'datetime',
    ];

    /** Czy zgłoszenie dotarło do CRM-a w SZO. */
    public function syncedToSzo(): bool
    {
        return $this->szo_synced_at !== null;
    }

    /** Zgłoszenia do ponowienia: próbowano i się nie udało. */
    public function scopePendingSzo($query)
    {
        return $query->whereNull('szo_synced_at');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_definition_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
