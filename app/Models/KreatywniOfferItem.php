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

    protected $fillable = ['kreatywni_offer_id', 'title', 'slug', 'group', 'description', 'details', 'price', 'image_alt', 'order', 'is_active', 'meta_title', 'meta_description'];

    /** Znaczniki dozwolone w krótkim opisie (karta): tylko pogrubienie i kursywa. */
    private const SHORT_TAGS = '<strong><b><em><i><br>';

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

    /** Czy wartość to HTML z edytora (starsze opisy to zwykły tekst z pustą linią między akapitami). */
    public static function looksLikeHtml(?string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][a-z0-9]*[\s>\/]/i', (string) $value);
    }

    /** Krótki opis z edytora do zapisu: zostaje pogrubienie i kursywa, akapity łączone przez <br>. */
    public static function cleanShortHtml(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }
        if (! self::looksLikeHtml($value)) {
            return trim($value);
        }
        $html = \App\Support\SafeHtml::clean($value);
        $html = (string) preg_replace('#</(p|li|h[1-6]|blockquote)>\s*<(p|li|h[1-6]|blockquote|ul|ol)\b[^>]*>#i', '<br>', $html);
        $html = strip_tags($html, self::SHORT_TAGS);
        $html = trim((string) preg_replace(['#^(\s|&nbsp;|<br\s*/?>)+#i', '#(\s|&nbsp;|<br\s*/?>)+$#i'], '', $html));

        return trim(strip_tags($html)) === '' ? null : $html;
    }

    /** Opis na podstronie z edytora do zapisu: akapity, nagłówki, listy, pogrubienie, kursywa i linki (SafeHtml). */
    public static function cleanDetailsHtml(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }
        if (! self::looksLikeHtml($value)) {
            return trim($value);
        }
        $html = \App\Support\SafeHtml::clean($value);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '' ? null : $html;
    }

    /** Krótki opis jako bezpieczny HTML do wstawienia w <p> (pogrubienie, kursywa, <br>). */
    public function descriptionHtml(): string
    {
        $value = trim((string) $this->description);
        if ($value === '') {
            return '';
        }

        return self::looksLikeHtml($value)
            ? trim(strip_tags(\App\Support\SafeHtml::clean($value), self::SHORT_TAGS))
            : nl2br(e($value), false);
    }

    /** Opis na podstronie jako bezpieczny HTML (stary zwykły tekst → akapity <p>). */
    public function detailsHtml(): string
    {
        $value = trim((string) $this->details);
        if ($value === '') {
            return '';
        }

        return self::looksLikeHtml($value)
            ? \App\Support\SafeHtml::clean($value)
            : implode("\n", array_map(fn (string $p) => '<p>'.nl2br(e($p), false).'</p>', $this->detailParagraphs()));
    }

    /** Krótki opis jako zwykły tekst (opis SEO, lista tekstowa, karty „inne warsztaty”). */
    public function descriptionText(): string
    {
        return self::plain((string) $this->description);
    }

    /** Opis na podstronie jako zwykły tekst. */
    public function detailsText(): string
    {
        return self::plain((string) $this->details);
    }

    private static function plain(string $value): string
    {
        if (self::looksLikeHtml($value)) {
            $value = html_entity_decode(strip_tags((string) preg_replace('#<br\s*/?>|</(p|li|h[1-6])>#i', ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $value)));
    }

    /** Wartości do edytora w panelu: stary zwykły tekst zamieniony na akapity HTML. */
    public function descriptionForEditor(): string
    {
        $value = (string) $this->description;

        return $value === '' || self::looksLikeHtml($value) ? $value : '<p>'.nl2br(e(trim($value)), false).'</p>';
    }

    public function detailsForEditor(): string
    {
        $value = (string) $this->details;

        return $value === '' || self::looksLikeHtml($value) ? $value : $this->detailsHtml();
    }

    /** Tytuł SEO podstrony (bez nazwy witryny). */
    public function seoTitle(): string
    {
        return trim((string) ($this->meta_title ?? '')) ?: (string) $this->title;
    }

    /** Opis SEO podstrony: własny, a bez niego krótki albo dłuższy opis (do 160 znaków). */
    public function seoDescription(): string
    {
        $own = trim((string) ($this->meta_description ?? ''));

        return $own !== '' ? $own : \Illuminate\Support\Str::limit($this->descriptionText() ?: $this->detailsText(), 160);
    }

    /** Czy tabela ma kolumny SEO (push wdraża kod przed migracją). */
    public static function supportsSeo(): bool
    {
        return once(fn () => self::supported() && Schema::hasColumn('kreatywni_offer_items', 'meta_title'));
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
        $desc = $this->descriptionText();

        return rtrim($this->title.' | '.$this->group.($desc !== '' ? ' | '.$desc : ''), ' |');
    }
}
