<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Termin warsztatu z mikro modułu „Warsztaty – Kreatywni” (panel: Treści → Strona główna → Warsztaty – Kreatywni).
 * Pokazywany na stronie typu „Kalendarz warsztatów” (App\PageTypes\Types\KreatywniWorkshops) szablonu „kreatywni”.
 * Czas w bazie w UTC, na stronie i w panelu w czasie polskim (TZ). Warsztat jest „minął”, gdy minął jego koniec
 * (a bez końca — dzień rozpoczęcia).
 */
class KreatywniWorkshop extends Model implements HasMedia
{
    use InteractsWithMedia;
    use Concerns\BelongsToSite;
    use Concerns\LogsActivity;

    public const TZ = 'Europe/Warsaw';

    protected $table = 'kreatywni_workshops';

    protected $fillable = ['site_id', 'title', 'place', 'starts_at', 'ends_at', 'participants', 'description', 'image_alt', 'is_active'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(960)->format('webp')->quality(82)->nonQueued();
    }

    /** Czy tabela modułu istnieje (push wdraża kod przed migracją). */
    public static function supported(): bool
    {
        return once(fn () => Schema::hasTable('kreatywni_workshops'));
    }

    /** Chwila, po której warsztat jest miniony: koniec albo — bez końca — koniec dnia rozpoczęcia (czas polski). */
    public function endsMoment(): CarbonInterface
    {
        return $this->ends_at ?? $this->starts_at->copy()->timezone(self::TZ)->endOfDay()->utc();
    }

    public function isPast(?CarbonInterface $now = null): bool
    {
        return $this->endsMoment()->lt($now ?? now());
    }

    /** Nadchodzące i trwające (koniec jeszcze nie minął). */
    public function scopeUpcoming(Builder $query): Builder
    {
        $now = now();
        $startOfToday = $now->copy()->timezone(self::TZ)->startOfDay()->utc();

        return $query->where(fn ($q) => $q->where('ends_at', '>=', $now)
            ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', $startOfToday)));
    }

    public function scopePast(Builder $query): Builder
    {
        $now = now();
        $startOfToday = $now->copy()->timezone(self::TZ)->startOfDay()->utc();

        return $query->where(fn ($q) => $q->where('ends_at', '<', $now)
            ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '<', $startOfToday)));
    }

    public function startsLocal(): CarbonInterface
    {
        return $this->starts_at->copy()->timezone(self::TZ);
    }

    public function endsLocal(): ?CarbonInterface
    {
        return $this->ends_at?->copy()->timezone(self::TZ);
    }

    /**
     * Termin słownie: „sobota, 12 grudnia 2026, 10:00–13:00”, wielodniowy „12–14 grudnia 2026”, bez końca — sam początek.
     */
    public function whenLabel(): string
    {
        $start = $this->startsLocal();
        $end = $this->endsLocal();
        $day = $start->translatedFormat('l, j F Y');
        if (! $end) {
            return $day.', '.$start->format('H:i');
        }
        if ($start->isSameDay($end)) {
            return $day.', '.$start->format('H:i').'–'.$end->format('H:i');
        }

        return $start->translatedFormat('j F Y, H:i').' – '.$end->translatedFormat('j F Y, H:i');
    }

    public function imageUrl(): ?string
    {
        $media = $this->getFirstMedia('image');

        return $media ? ($media->getAvailableUrl(['card']) ?: $media->getUrl()) : null;
    }

    /** Opis grafiki: własny z formularza, a bez niego — z biblioteki (custom property „alt”); pusty = dekoracyjna. */
    public function imageAltText(): string
    {
        return trim((string) ($this->image_alt ?: $this->getFirstMedia('image')?->getCustomProperty('alt')));
    }

    /** @return list<string> akapity opisu (pusta linia = nowy akapit) */
    public function descriptionParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) $this->description) ?: []), fn ($p) => $p !== ''));
    }
}
