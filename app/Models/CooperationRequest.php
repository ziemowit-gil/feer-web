<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperationRequest extends Model
{
    protected $fillable = [
        'page_id', 'name', 'email', 'organization',
        'sector', 'cooperation_types', 'message', 'read_at',
    ];

    protected $casts = [
        'cooperation_types' => 'array',
        'read_at'           => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
