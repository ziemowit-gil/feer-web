<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Kampania newslettera (nie mylić z `campaigns` = kampanie zbiórkowe).
 * Cykl: draft → scheduled → queued → sending → sent | paused | cancelled | failed.
 */
class NewsletterCampaign extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED    = 'failed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Szkic', self::STATUS_SCHEDULED => 'Zaplanowana', self::STATUS_QUEUED => 'W kolejce',
        self::STATUS_SENDING => 'Wysyłanie', self::STATUS_PAUSED => 'Wstrzymana', self::STATUS_SENT => 'Wysłana',
        self::STATUS_CANCELLED => 'Anulowana', self::STATUS_FAILED => 'Błąd',
    ];

    protected $table = 'newsletter_campaigns';

    protected $fillable = [
        'site_id', 'uuid', 'title', 'subject', 'subject_b', 'ab_split_percent', 'preheader', 'from_name', 'from_address',
        'reply_to', 'template_id', 'mosaico_template', 'editor_metadata', 'editor_content', 'html_body', 'text_body',
        'short_title', 'short_text', 'short_url', 'channels', 'audience', 'utm', 'track_opens', 'track_clicks', 'status',
        'scheduled_at', 'send_in_recipient_tz', 'started_at', 'finished_at', 'batch_id', 'recipients_count',
        'recurrence', 'recurrence_rule', 'parent_campaign_id', 'content_snapshot', 'last_feed_item_at', 'last_run_at',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'editor_metadata' => 'array', 'editor_content' => 'array', 'channels' => 'array', 'audience' => 'array',
        'utm' => 'array', 'recurrence_rule' => 'array', 'content_snapshot' => 'array',
        'track_opens' => 'boolean', 'track_clicks' => 'boolean', 'send_in_recipient_tz' => 'boolean',
        'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime',
        'last_feed_item_at' => 'datetime', 'last_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $c) => $c->uuid ??= (string) Str::uuid());
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NewsletterTemplate::class, 'template_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class, 'campaign_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(NewsletterCampaignLink::class, 'campaign_id')->orderBy('position');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_campaign_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_CANCELLED, self::STATUS_FAILED], true);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_SENDING, self::STATUS_PAUSED], true);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence !== null && $this->parent_campaign_id === null;
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? ['email'], true);
    }

    public function rate(string $numerator, string $denominator = 'delivered_count'): ?float
    {
        $den = (int) $this->{$denominator};

        return $den > 0 ? round($this->{$numerator} / $den * 100, 1) : null;
    }

    /** Odświeża liczniki zdenormalizowane z tabeli dostaw. */
    public function refreshCounters(): void
    {
        $base = NewsletterDelivery::where('campaign_id', $this->id);

        $this->forceFill([
            'sent_count'         => (clone $base)->whereIn('status', ['sent', 'delivered', 'soft_bounced', 'hard_bounced', 'complained'])->count(),
            'delivered_count'    => (clone $base)->whereIn('status', ['sent', 'delivered'])->count(),
            'failed_count'       => (clone $base)->whereIn('status', ['failed'])->count(),
            'opened_unique'      => (clone $base)->whereNotNull('opened_at')->count(),
            'clicked_unique'     => (clone $base)->whereNotNull('clicked_at')->count(),
            'bounced_count'      => (clone $base)->whereIn('status', ['soft_bounced', 'hard_bounced'])->count(),
            'complained_count'   => (clone $base)->where('status', 'complained')->count(),
            'unsubscribed_count' => \App\Models\Subscriber::where('unsubscribe_campaign_id', $this->id)->count(),
        ])->save();
    }
}
