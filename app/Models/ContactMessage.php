<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'ip_address',
        'email_sent_at', 'coordinator_name', 'coordinator_email',
    ];

    protected $casts = [
        'email_sent_at' => 'datetime',
        'read_at'       => 'datetime',
        'replied_at'    => 'datetime',
    ];

    public function emailDelivered(): bool
    {
        return ! is_null($this->email_sent_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsReplied(): void
    {
        $this->update(['replied_at' => now()]);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public static function unreadCount(): int
    {
        return static::unread()->count();
    }
}
