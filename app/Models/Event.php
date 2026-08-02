<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** Rodzaj wydarzenia (z dedykowaną ikoną w widoku). */
    public const TYPES = [
        'szkolenie' => 'Szkolenie',
        'warsztat' => 'Warsztat',
        'webinar' => 'Webinar',
        'wydarzenie' => 'Wydarzenie',
    ];

    /** Tryb: gdzie/jak odbywa się wydarzenie. */
    public const MODES = [
        'stacjonarnie' => 'Stacjonarnie',
        'zdalnie' => 'Zdalnie',
        'hybrydowo' => 'Hybrydowo',
    ];

    protected $fillable = [
        'title', 'slug', 'lead', 'description',
        'facilitator_name', 'facilitator_role', 'facilitator_bio',
        'type', 'mode', 'location', 'latitude', 'longitude', 'online_url',
        'starts_at', 'ends_at', 'published_at',
        'registration_url', 'registration_cta_label', 'contact_email', 'price_info',
        'audience', 'is_published', 'is_featured', 'order', 'archived_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'archived_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(EventFaq::class)->orderBy('order')->orderBy('id');
    }

    /** Pytania z globalnego FAQ dopięte do tego wydarzenia. */
    public function globalFaqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class, 'event_global_faq')
            ->orderBy('faqs.order')
            ->orderBy('faqs.id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('facilitator_photo')->singleFile();
    }

    /** URL zdjęcia prowadzącej lub null, gdy nie dodano. */
    public function facilitatorPhotoUrl(): ?string
    {
        return $this->getFirstMediaUrl('facilitator_photo') ?: null;
    }

    /** Czy wydarzenie ma opisaną osobę prowadzącą (do warunkowego renderu). */
    public function hasFacilitator(): bool
    {
        return filled($this->facilitator_name) || filled($this->facilitator_bio) || $this->facilitatorPhotoUrl() !== null;
    }

    /**
     * Nadchodzące (jeszcze nietrwające/nierozpoczęte) i opublikowane — to, co
     * odwiedzający zobaczy na liście i na stronie głównej. Wydarzenie z datą
     * końca znika po jej upływie; jednodniowe (bez ends_at) — po zakończeniu dnia.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->where('ends_at', '>=', now())
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNull('ends_at')->whereDate('starts_at', '>=', now());
                    });
            })
            ->orderBy('starts_at')
            ->orderBy('order');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    /** Ikona Font Awesome dobrana do rodzaju wydarzenia. */
    public function typeIcon(): string
    {
        return [
            'szkolenie' => 'fa-chalkboard-user',
            'warsztat' => 'fa-screwdriver-wrench',
            'webinar' => 'fa-video',
            'wydarzenie' => 'fa-calendar-star',
        ][$this->type] ?? 'fa-calendar-day';
    }

    /** Czy wydarzenie już się odbyło (do oznaczenia w panelu i na stronie). */
    public function isPast(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    /**
     * Czytelny termin po polsku: jednodniowy „12 marca 2026, 17:00–19:00",
     * bez godziny końca „12 marca 2026, 17:00" lub wielodniowy z pełną datą końca.
     */
    public function dateRangeLabel(): string
    {
        $start = $this->starts_at->locale('pl');
        $label = $start->isoFormat('D MMMM YYYY, HH:mm');

        if (! $this->ends_at) {
            return $label;
        }

        $end = $this->ends_at->locale('pl');

        return $start->isSameDay($end)
            ? $label.'–'.$end->isoFormat('HH:mm')
            : $label.' – '.$end->isoFormat('D MMMM YYYY, HH:mm');
    }

    /** Skrócony termin na kartę listy. */
    public function shortDateLabel(): string
    {
        return $this->starts_at->locale('pl')->isoFormat('D MMM YYYY, HH:mm');
    }

    /**
     * Dokąd prowadzi przycisk zapisu: zewnętrzny formularz, a gdy go nie
     * podano — e-mail kontaktowy wydarzenia.
     */
    public function registrationHref(): ?string
    {
        if (filled($this->registration_url)) {
            return $this->registration_url;
        }

        return filled($this->contact_email) ? 'mailto:'.$this->contact_email : null;
    }
}
