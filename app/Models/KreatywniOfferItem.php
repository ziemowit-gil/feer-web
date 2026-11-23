<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Warsztat w ofercie z modułu „Mikrooferta – Kreatywni” (karta w bloku oferty): nazwa, grupa (np. „Boże Narodzenie”),
 * krótki opis (na karcie), dłuższy opis (na podstronie), cena (opcjonalna, np. „od 35 zł”), grafika w kolekcji „image”
 * z opisem alternatywnym, kolejność i widoczność. Każdy warsztat ma podstronę /warsztat/{slug} (adres nadawany z nazwy
 * przy dodaniu i potem stały). Edycja: panel oferty → „Warsztaty w tej ofercie” (KreatywniOfferItemController).
 */
class KreatywniOfferItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    use Concerns\LogsActivity;

    protected $table = 'kreatywni_offer_items';

    protected $fillable = ['kreatywni_offer_id', 'title', 'slug', 'group', 'description', 'details', 'price', 'image_alt', 'order', 'is_active'];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (blank($item->slug)) {
                $item->slug = self::uniqueSlug((string) $item->title);
            }
        });
    }

    /** Adres podstrony z nazwy, niepowtarzalny w tabeli (np. „swiece-sojowe”, „swiece-sojowe-2”). */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = mb_substr(\Illuminate\Support\Str::slug($title) ?: 'warsztat', 0, 150);
        $slug = $base;
        for ($i = 2; self::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /** Adres podstrony warsztatu (null, gdy brak trasy — stary cache tras — albo adresu). */
    public function publicUrl(): ?string
    {
        return $this->slug && \Illuminate\Support\Facades\Route::has('kreatywni.offer-item') ? site_route('kreatywni.offer-item', ['slug' => $this->slug]) : null;
    }

    /** @return list<string> akapity dłuższego opisu (pusta linia = nowy akapit) */
    public function detailParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) $this->details) ?: []), fn ($p) => $p !== ''));
    }

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(720)->format('webp')->quality(82)->nonQueued();
    }

    /** Czy tabela warsztatów oferty istnieje (push wdraża kod przed migracją). */
    public static function supported(): bool
    {
        return once(fn () => Schema::hasTable('kreatywni_offer_items'));
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(KreatywniOffer::class, 'kreatywni_offer_id');
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

    /** Wiersz tekstowego zapasu oferty: „Tytuł | grupa | opis”. */
    public function asTextLine(): string
    {
        $desc = trim(preg_replace('/\s+/u', ' ', (string) $this->description) ?? '');

        return rtrim($this->title.' | '.$this->group.($desc !== '' ? ' | '.$desc : ''), ' |');
    }
}
