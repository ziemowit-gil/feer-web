<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Jedna dostawa = jeden odbiorca × jeden kanał. */
class NewsletterDelivery extends Model
{
    public const STATUS_QUEUED       = 'queued';
    public const STATUS_SENT         = 'sent';
    public const STATUS_DELIVERED    = 'delivered';
    public const STATUS_SOFT_BOUNCED = 'soft_bounced';
    public const STATUS_HARD_BOUNCED = 'hard_bounced';
    public const STATUS_COMPLAINED   = 'complained';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_SKIPPED      = 'skipped';

    public const STATUSES = [
        self::STATUS_QUEUED => 'W kolejce', self::STATUS_SENT => 'Wysłana', self::STATUS_DELIVERED => 'Dostarczona',
        self::STATUS_SOFT_BOUNCED => 'Odbicie miękkie', self::STATUS_HARD_BOUNCED => 'Odbicie twarde',
        self::STATUS_COMPLAINED => 'Zgłoszenie spamu', self::STATUS_FAILED => 'Błąd', self::STATUS_SKIPPED => 'Pominięta',
    ];

    protected $table = 'newsletter_deliveries';

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'campaign_id', 'subscriber_id', 'channel', 'variant', 'status', 'provider', 'provider_message_id',
        'error_code', 'error_message', 'attempts', 'queued_at', 'sent_at', 'delivered_at', 'opened_at', 'clicked_at',
        'opens_count', 'clicks_count',
    ];

    protected $casts = [
        'queued_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime',
        'opened_at' => 'datetime', 'clicked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $d) => $d->uuid ??= (string) Str::uuid());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NewsletterCampaign::class, 'campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    public function opens(): HasMany
    {
        return $this->hasMany(NewsletterOpen::class, 'delivery_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(NewsletterClick::class, 'delivery_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
