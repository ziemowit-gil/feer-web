<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /** Rodzaje powtarzania — wartości przechowywane w DB => etykiety dla UI. */
    public const RECURRENCE_TYPES = [
        'weekly' => 'Co tydzień',
        'biweekly' => 'Co dwa tygodnie',
        'monthly' => 'Co miesiąc',
        'yearly' => 'Co rok',
    ];

    protected $fillable = [
        'title', 'slug', 'lead', 'description',
        'facilitator_name', 'facilitator_role', 'facilitator_bio',
        'type', 'mode', 'location', 'latitude', 'longitude', 'online_url',
        'starts_at', 'ends_at', 'published_at',
        'registration_url', 'registration_cta_label', 'hide_registration', 'contact_email', 'price_info',
        'audience', 'is_published', 'is_featured', 'order', 'archived_at',
        'recurrence_type', 'recurrence_ends_at', 'recurrence_parent_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'hide_registration' => 'boolean',
        'archived_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'recurrence_ends_at' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // -------------------------------------------------------------------------
    // Relacje powtarzania
    // -------------------------------------------------------------------------

    /** Rekord-rodzic serii, do której należy ta instancja. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'recurrence_parent_id');
    }

    /** Instancje (dzieci) tej serii, posortowane chronologicznie. */
    public function instances(): HasMany
    {
        return $this->hasMany(Event::class, 'recurrence_parent_id')->orderBy('starts_at');
    }

    // -------------------------------------------------------------------------
    // Pomocniki powtarzania
    // -------------------------------------------------------------------------

    /** Czy to rekord-rodzic serii (ma zdefiniowany cykl i nie jest dzieckiem)? */
    public function isSeries(): bool
    {
        return $this->recurrence_type !== null && $this->recurrence_parent_id === null;
    }

    /** Czy to instancja (dziecko) serii? */
    public function isInstance(): bool
    {
        return $this->recurrence_parent_id !== null;
    }

    /**
     * Usuwa istniejące instancje tej serii i generuje nowe wg cyklu.
     * Każda instancja dostaje odrębny, unikalny slug z sufiksem -2, -3…
     * Zwraca liczbę utworzonych instancji.
     */
    public function generateInstances(): int
    {
        // Usuń stare instancje (każdą osobno, żeby wyzwolić Model::delete i media)
        $this->instances()->each->delete();

        if (! $this->recurrence_type || ! $this->starts_at) {
            return 0;
        }

        // Horyzont czasowy: albo podana data końca serii, albo domyślny limit
        $maxDate = $this->recurrence_ends_at
            ? $this->recurrence_ends_at->copy()->endOfDay()
            : (in_array($this->recurrence_type, ['weekly', 'biweekly'])
                ? $this->starts_at->copy()->addMonths(12)
                : $this->starts_at->copy()->addYears(3));

        // Czas trwania (zachowany dla każdej instancji)
        $duration = $this->ends_at
            ? $this->starts_at->diffInSeconds($this->ends_at)
            : null;

        // Dane skalarne do skopiowania (daty, slug i pola cyklu podajemy osobno)
        $base = collect($this->only($this->fillable))->except([
            'slug', 'starts_at', 'ends_at', 'archived_at',
            'recurrence_type', 'recurrence_ends_at', 'recurrence_parent_id',
        ])->merge([
            'recurrence_parent_id' => $this->id,
            'recurrence_type' => null,
            'recurrence_ends_at' => null,
            'archived_at' => null,
            'is_published' => $this->is_published,
        ])->all();

        $count = 0;
        $slugSuffix = 2;

        for ($i = 1; $i <= 52; $i++) {
            // Oblicz datę od rodzica (nie od poprzedniej instancji — brak dryftu)
            $starts = match ($this->recurrence_type) {
                'weekly' => $this->starts_at->copy()->addWeeks($i),
                'biweekly' => $this->starts_at->copy()->addWeeks($i * 2),
                'monthly' => $this->starts_at->copy()->addMonths($i),
                'yearly' => $this->starts_at->copy()->addYears($i),
            };

            if ($starts->greaterThan($maxDate)) {
                break;
            }

            // Unikalny slug z sufiksem
            do {
                $slug = $this->slug.'-'.$slugSuffix;
                $slugSuffix++;
            } while (static::where('slug', $slug)->exists());

            static::create(array_merge($base, [
                'slug' => $slug,
                'starts_at' => $starts,
                'ends_at' => $duration !== null ? $starts->copy()->addSeconds($duration) : null,
            ]));

            $count++;
        }

        return $count;
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
            ->whereNull('archived_at')
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
        if ($this->hide_registration) {
            return null;
        }

        if (filled($this->registration_url)) {
            return $this->registration_url;
        }

        return filled($this->contact_email) ? 'mailto:'.$this->contact_email : null;
    }
}
