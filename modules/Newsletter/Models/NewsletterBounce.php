<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterBounce extends Model
{
    protected $table = 'newsletter_bounces';

    public $timestamps = false;

    protected $fillable = [
        'delivery_id', 'subscriber_id', 'type', 'provider', 'provider_event_id', 'smtp_code', 'reason',
        'raw_payload', 'occurred_at', 'processed_at',
    ];

    protected $casts = ['raw_payload' => 'array', 'occurred_at' => 'datetime', 'processed_at' => 'datetime'];
}
