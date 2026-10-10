<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSubscriberEvent;

/**
 * Subskrybent newslettera (double opt-in, zgody per kanał, historia aktywności).
 *
 * Adres e-mail ma ślepy indeks `email_hash` (HMAC-SHA256 z APP_KEY) — po nim
 * szukamy i deduplikujemy, a listy tłumienia nie muszą trzymać adresów.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class Subscriber extends Model
{
    public const STATUS_PENDING      = 'pending';
    public const STATUS_CONFIRMED    = 'confirmed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BOUNCED      = 'bounced';
    public const STATUS_COMPLAINED   = 'complained';
    public const STATUS_SUPPRESSED   = 'suppressed';
    public const STATUS_EXPIRED      = 'expired';
    public const STATUS_ANONYMIZED   = 'anonymized';

    public const STATUSES = [
        self::STATUS_PENDING      => 'Oczekuje na potwierdzenie',
        self::STATUS_CONFIRMED    => 'Aktywny',
        self::STATUS_UNSUBSCRIBED => 'Wypisany',
        self::STATUS_BOUNCED      => 'Odbity (adres nie istnieje)',
        self::STATUS_COMPLAINED   => 'Zgłosił spam',
        self::STATUS_SUPPRESSED   => 'Zablokowany',
        self::STATUS_EXPIRED      => 'Niepotwierdzony (wygasł)',
        self::STATUS_ANONYMIZED   => 'Zanonimizowany',
    ];

    public const CHANNELS = [
        'email'   => 'E-mail',
        'webpush' => 'Powiadomienia push',
        'sms'     => 'SMS',
    ];

    protected $fillable = [
        'site_id', 'uuid', 'email', 'email_hash', 'name', 'phone', 'status', 'token', 'topics', 'channels', 'tags',
        'locale', 'timezone', 'source', 'confirmed_at', 'confirmation_sent_at', 'unsubscribed_at', 'unsubscribe_reason',
        'unsubscribe_campaign_id', 'bounced_at', 'complained_at', 'last_sent_at', 'last_open_at', 'last_click_at',
        'engagement_score', 'anonymized_at', 'szo_contact_id', 'szo_synced_at', 'szo_error', 'crm_synced_at', 'crm_error',
    ];

    protected $casts = [
        'topics'               => 'array',
        'channels'             => 'array',
        'tags'                 => 'array',
        'phone'                => 'encrypted',
        'confirmed_at'         => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'unsubscribed_at'      => 'datetime',
        'bounced_at'           => 'datetime',
        'complained_at'        => 'datetime',
        'last_sent_at'         => 'datetime',
        'last_open_at'         => 'datetime',
        'last_click_at'        => 'datetime',
        'anonymized_at'        => 'datetime',
        'szo_synced_at'        => 'datetime',
        'crm_synced_at'        => 'datetime',
    ];

    /**
     * Dostępne tematy subskrypcji: klucz => etykieta. Źródłem jest tabela
     * `newsletter_topics` (panel: Newsletter → Tematy); lista poniżej to fallback
     * przed migracją i wartości startowe.
     */
    public static function availableTopics(bool $onlyActive = true): array
    {
        try {
            if (class_exists(\Modules\Newsletter\Models\NewsletterTopic::class) && \Illuminate\Support\Facades\Schema::hasTable('newsletter_topics')) {
                $topics = \Modules\Newsletter\Models\NewsletterTopic::options($onlyActive);
                if ($topics !== []) {
                    return $topics;
                }
            }
        } catch (\Throwable) {
            // brak bazy (instalacja) — fallback poniżej
        }

        return static::$availableTopics;
    }

    /** Fallback / wartości startowe tematów. */
    public static array $availableTopics = [
        'news'      => 'Aktualności',
        'events'    => 'Szkolenia i wydarzenia',
        'blog'      => 'Blog Wiem FEER',
        'materials' => 'Materiały edukacyjne',
        'etr'       => 'Treści ETR (Łatwy Odczyt)',
        'projects'  => 'Działania',
        'campaigns' => 'Kampanie zbiórkowe',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            $s->uuid ??= (string) Str::uuid();
            $s->token ??= static::generateToken();
            if ($s->email) {
                $s->email      = static::normalizeEmail($s->email);
                $s->email_hash = static::hashEmail($s->email);
            }
            $s->channels ??= ['email'];
        });

        static::updating(function (self $s) {
            if ($s->isDirty('email') && $s->email) {
                $s->email      = static::normalizeEmail($s->email);
                $s->email_hash = static::hashEmail($s->email);
            }
        });
    }

    // ── Pomocnicze ───────────────────────────────────────────────────────────

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Ślepy indeks adresu: HMAC-SHA256 kluczem aplikacji. */
    public static function hashEmail(string $email): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', static::normalizeEmail($email), $key);
    }

    /** Pseudonim IP do logów: 16 znaków HMAC, nie da się odwrócić. */
    public static function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return substr(static::hashEmail('ip:' . $ip), 0, 16);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public static function findByEmail(string $email): ?static
    {
        return static::where('email_hash', static::hashEmail($email))->first();
    }

    // ── Relacje ──────────────────────────────────────────────────────────────

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(NewsletterList::class, 'newsletter_list_subscriber', 'subscriber_id', 'list_id')
            ->withPivot(['added_at', 'added_by']);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(NewsletterConsent::class, 'subscriber_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class, 'subscriber_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(NewsletterSubscriberEvent::class, 'subscriber_id')->latest('created_at');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class, 'subscriber_id');
    }

    // ── Zapytania ────────────────────────────────────────────────────────────

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeForSite(Builder $q, ?int $siteId): Builder
    {
        return $siteId ? $q->where(fn ($w) => $w->where('site_id', $siteId)->orWhereNull('site_id')) : $q;
    }

    // ── Stan ─────────────────────────────────────────────────────────────────

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canReceive(string $channel = 'email'): bool
    {
        return $this->isConfirmed() && $this->hasChannel($channel) && $this->anonymized_at === null;
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? ['email'], true);
    }

    public function hasTopic(string $topic): bool
    {
        return in_array($topic, $this->topics ?? [], true);
    }

    public function topicLabels(): array
    {
        return array_values(array_intersect_key(
            static::availableTopics(false),
            array_flip($this->topics ?? [])
        ));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function firstName(): string
    {
        $name = trim((string) $this->name);

        return $name === '' ? '' : Str::before($name, ' ');
    }

    /** Dopisuje zdarzenie do osi czasu subskrybenta. */
    public function logEvent(string $type, array $meta = [], ?int $campaignId = null, ?int $actorId = null): void
    {
        $this->events()->create([
            'type'        => $type,
            'campaign_id' => $campaignId,
            'actor_id'    => $actorId ?? (auth()->check() ? auth()->id() : null),
            'meta'        => $meta ?: null,
            'created_at'  => now(),
        ]);
    }

    /** Wypisuje subskrybenta (link, one-click, preferencje, admin). */
    public function unsubscribe(string $via = 'link', ?int $campaignId = null, ?string $reason = null): void
    {
        if ($this->status === self::STATUS_UNSUBSCRIBED) {
            return;
        }

        $this->forceFill([
            'status'                  => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at'         => now(),
            'unsubscribe_reason'      => $reason,
            'unsubscribe_campaign_id' => $campaignId,
        ])->save();

        $this->consents()->whereNull('revoked_at')->update(['revoked_at' => now(), 'revoked_via' => $via]);
        $this->logEvent('unsubscribed', ['via' => $via, 'reason' => $reason], $campaignId);
    }

    /**
     * Prawo do bycia zapomnianym: usuwa dane osobowe, zachowuje rekord do
     * statystyk, a hash adresu ląduje na 30 dni na liście tłumienia.
     */
    public function anonymize(?int $actorId = null): void
    {
        \Modules\Newsletter\Models\NewsletterSuppression::updateOrCreate(
            ['email_hash' => $this->email_hash],
            ['reason' => 'anonymized', 'expires_at' => now()->addDays(30)],
        );

        $this->forceFill([
            'email'          => "deleted-{$this->id}@anonymized.invalid",
            'email_hash'     => static::hashEmail("deleted-{$this->id}@anonymized.invalid"),
            'name'           => null,
            'phone'          => null,
            'token'          => static::generateToken(),
            'status'         => self::STATUS_ANONYMIZED,
            'anonymized_at'  => now(),
            'szo_contact_id' => null,
        ])->save();

        $this->consents()->update(['ip_hash' => null, 'user_agent' => null]);
        $this->pushSubscriptions()->delete();
        $this->logEvent('anonymized', [], null, $actorId);
    }

    /** Eksport danych osoby (art. 15 i 20 RODO). */
    public function exportPersonalData(): array
    {
        return [
            'profil' => [
                'email'       => $this->email,
                'imie'        => $this->name,
                'telefon'     => $this->phone,
                'status'      => $this->statusLabel(),
                'tematy'      => $this->topicLabels(),
                'kanaly'      => $this->channels,
                'zapisany'    => $this->created_at?->toIso8601String(),
                'potwierdzony'=> $this->confirmed_at?->toIso8601String(),
                'wypisany'    => $this->unsubscribed_at?->toIso8601String(),
                'zrodlo'      => $this->source,
            ],
            'zgody' => $this->consents()->get()->map(fn ($c) => [
                'kanal' => $c->channel, 'tresc' => $c->clause_text, 'udzielona' => $c->granted_at?->toIso8601String(),
                'potwierdzona' => $c->confirmed_at?->toIso8601String(), 'wycofana' => $c->revoked_at?->toIso8601String(),
                'zrodlo' => $c->source, 'sposob' => $c->method,
            ])->all(),
            'wysylki' => $this->deliveries()->with('campaign:id,title,subject')->get()->map(fn ($d) => [
                'kampania' => $d->campaign?->title, 'kanal' => $d->channel, 'status' => $d->status,
                'wyslano' => $d->sent_at?->toIso8601String(), 'otwarto' => $d->opened_at?->toIso8601String(),
            ])->all(),
            'historia' => $this->events()->get()->map(fn ($e) => [
                'typ' => $e->type, 'kiedy' => $e->created_at?->toIso8601String(), 'szczegoly' => $e->meta,
            ])->all(),
        ];
    }
}
