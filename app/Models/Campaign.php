<?php

namespace App\Models;

use App\Support\FundraisingTexts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Zbiórka (kampania fundraisingowa). Wpłaty online idą przez Przelewy24
 * (DonationService), alternatywnie przez wplacam.ngo.pl albo zewnętrzny link.
 *
 * Kwota zebrana (collected_amount, pełne złote) jest pamięcią podręczną sumy
 * opłaconych wpłat + external_amount (wpłaty spoza systemu, wpisywane w panelu).
 * Liczona zawsze od zera z bazy (refreshCollected()), więc powtórzony webhook
 * nie może jej zawyżyć.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class Campaign extends Model implements HasMedia
{
    use \App\Models\Concerns\LogsActivity;
    use SoftDeletes;
    use InteractsWithMedia;

    public const STATUSES = [
        'draft' => 'Szkic',
        'active' => 'Aktywna',
        'closed' => 'Zakończona',
    ];

    public const PAYMENT_MODES = [
        'online' => 'Wpłaty online na stronie (Przelewy24)',
        'wplacam' => 'wplacam.ngo.pl',
        'external' => 'Zewnętrzny link do zbiórki',
    ];

    public const WPLACAM_DISPLAYS = [
        'redirect' => 'Przycisk przekierowujący',
        'embed' => 'Formularz osadzony na stronie',
    ];

    /** Domyślne progi kwot, gdy kampania nie ma własnych. */
    public const DEFAULT_AMOUNTS = [30, 50, 100, 200];

    public const MIN_AMOUNT = 5;

    public const MAX_AMOUNT = 50000;

    protected $fillable = [
        'title', 'slug', 'status', 'is_featured', 'excerpt', 'content',
        'goal_amount', 'collected_amount', 'external_amount', 'donation_url',
        'amount_options', 'allow_custom_amount', 'allow_recurring',
        'payment_mode', 'wplacam_url', 'wplacam_display', 'show_recent_donations',
        'image_alt', 'texts',
        'starts_at', 'ends_at', 'is_published', 'order',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'allow_custom_amount' => 'boolean',
        'allow_recurring' => 'boolean',
        'show_recent_donations' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'goal_amount' => 'integer',
        'collected_amount' => 'integer',
        'external_amount' => 'integer',
        'texts' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'payment_mode' => 'online',
        'wplacam_display' => 'redirect',
        'allow_custom_amount' => true,
        'show_recent_donations' => true,
    ];

    protected static function booted(): void
    {
        // Flaga publikacji (sitemap, stare zapytania) wynika ze statusu.
        static::saving(function (Campaign $campaign) {
            $campaign->is_published = in_array($campaign->status, ['active', 'closed'], true);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        // Nadpisania globalnych grafik z Zbiórki → Teksty i podziękowania.
        $this->addMediaCollection('thanks_image')->singleFile();
        $this->addMediaCollection('thanks_email_image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->nonQueued();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getAvailableUrl(['webp']) ?: null,
        );
    }

    public function fundraisingTexts(): FundraisingTexts
    {
        return FundraisingTexts::for($this);
    }

    /** Progi kwot (zł) z pola „30, 50, 100” albo domyślne. */
    public function amounts(): array
    {
        $amounts = collect(preg_split('/[\s,;]+/', (string) $this->amount_options, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= self::MIN_AMOUNT && $v <= self::MAX_AMOUNT)
            ->unique()
            ->take(8)
            ->values()
            ->all();

        return $amounts ?: self::DEFAULT_AMOUNTS;
    }

    /** Przelicza kwotę zebraną od zera: opłacone wpłaty + kwota spoza systemu. */
    public function refreshCollected(): void
    {
        $grosze = (int) $this->donations()->where('status', 'paid')->sum('amount_grosze');

        $this->forceFill(['collected_amount' => intdiv($grosze, 100) + (int) $this->external_amount])->saveQuietly();
    }

    /** Procent realizacji celu (0–100). */
    public function progressPercent(): int
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }

        return min(100, (int) round($this->collected_amount / $this->goal_amount * 100));
    }

    /** Czy zbiórka przyjmuje wpłaty (aktywna i w zakresie dat). */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();

        if ($this->starts_at && $this->starts_at->toDateString() > $today) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->toDateString() < $today) {
            return false;
        }

        return true;
    }

    /** Czy cel został osiągnięty. */
    public function isGoalReached(): bool
    {
        return $this->goal_amount > 0 && $this->collected_amount >= $this->goal_amount;
    }

    /** Czy wpłaty przyjmujemy przez Przelewy24 na stronie. */
    public function acceptsOnline(): bool
    {
        return $this->payment_mode === 'online' && $this->isActive();
    }

    /**
     * Adres wplacam.ngo.pl — tylko domena serwisu, żeby pole w panelu nie stało się
     * furtką do osadzania dowolnych stron w ramce.
     */
    public function wplacamUrl(): ?string
    {
        $url = trim((string) $this->wplacam_url);
        if ($url === '' || ! self::isWplacamUrl($url)) {
            return null;
        }

        return $url;
    }

    public static function isWplacamUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($url, 'https://') && ($host === 'wplacam.ngo.pl' || str_ends_with($host, '.wplacam.ngo.pl'));
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
