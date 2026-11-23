<?php

namespace App\Models;

use App\Support\KreatywniTexts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * Oferta z mikro modułu „Mikrooferta – Kreatywni” (panel: Treści → Strona główna → Mikrooferta – Kreatywni).
 * Pokazywana w bloku sprzedażowym (partials/offer-sales) na stronie głównej i na górze strony „Oferta” szablonu
 * „kreatywni”: karta cenowa i warsztaty w grupach. Bez tabeli (kod przed migracją) albo bez żadnej oferty witryny
 * szablon bierze ofertę z tekstów (forDisplay()).
 *
 * Warsztaty oferty to rekordy KreatywniOfferItem (grafika, nazwa, grupa, opis, cena, kolejność, widoczność; tabela
 * kreatywni_offer_items). Kolumna `items` („Tytuł | grupa | opis” w wierszach) zostaje jako zapas: czytana, gdy tabeli
 * warsztatów jeszcze nie ma albo oferta nie ma kart, i odświeżana po każdej zmianie kart (syncItemsText()).
 */
class KreatywniOffer extends Model
{
    use Concerns\BelongsToSite;
    use Concerns\LogsActivity;

    protected $table = 'kreatywni_offers';

    protected $fillable = ['site_id', 'name', 'price', 'price_unit', 'price_note', 'features', 'items', 'items_style', 'button_label', 'button_url', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /** Grupa warsztatu, gdy wiersz nie ma części po „|”. */
    public const DEFAULT_GROUP = 'Warsztaty';

    /** Czy tabela modułu istnieje (push wdraża kod przed migracją). */
    public static function supported(): bool
    {
        return once(fn () => Schema::hasTable('kreatywni_offers'));
    }

    /** Wygląd warsztatów tej oferty (klucz z KreatywniTexts::OFFER_STYLES). */
    public function itemsStyle(): string
    {
        return array_key_exists((string) $this->items_style, KreatywniTexts::OFFER_STYLES)
            ? $this->items_style : array_key_first(KreatywniTexts::OFFER_STYLES);
    }

    /** @return list<string> korzyści, po jednej w wierszu */
    public function featureList(): array
    {
        return self::lines((string) $this->features);
    }

    /** Warsztaty (karty) oferty w kolejności z panelu. */
    public function cards(): HasMany
    {
        return $this->hasMany(KreatywniOfferItem::class, 'kreatywni_offer_id')->orderBy('order')->orderBy('id');
    }

    /** Czy oferta ma karty warsztatów w tabeli (inaczej działa tekstowy zapas `items`). */
    public function usesCards(): bool
    {
        return KreatywniOfferItem::supported() && $this->exists && $this->cards()->exists();
    }

    /**
     * Widoczne warsztaty pogrupowane w kolejności pierwszego wystąpienia grupy: grupa => karty. Z tabeli kart (z grafiką
     * i ceną), a bez niej — z tekstu `items`.
     *
     * @return array<string, list<array{title: string, desc: string, price: string, image: ?string, alt: string}>>
     */
    public function itemGroups(): array
    {
        if ($this->usesCards()) {
            $groups = [];
            foreach ($this->cards()->where('is_active', true)->with('media')->get() as $card) {
                $group = trim((string) $card->group);
                $groups[$group !== '' ? $group : self::DEFAULT_GROUP][] = [
                    'title' => $card->title, 'desc' => trim((string) $card->description), 'price' => trim((string) $card->price),
                    'image' => $card->imageUrl(), 'alt' => $card->imageAltText(), 'url' => $card->publicUrl(),
                ];
            }

            return $groups;
        }

        return self::groupItems(self::parseLines((string) $this->items));
    }

    public function itemCount(): int
    {
        return $this->usesCards() ? $this->cards()->count() : count(self::lines((string) $this->items));
    }

    /**
     * Dodaje karty z wierszy „Tytuł | grupa | opis” na końcu oferty (import, seeder, „dodaj kilka naraz” w panelu).
     * Bez tabeli kart dopisuje wiersze do tekstu `items`. Zwraca liczbę dodanych warsztatów.
     */
    public function addItemsFromText(string $text): int
    {
        $rows = self::parseLines($text);
        if ($rows === []) {
            return 0;
        }
        if (! KreatywniOfferItem::supported()) {
            $this->forceFill(['items' => trim(rtrim((string) $this->items)."\n".implode("\n", array_map(fn ($r) => rtrim(implode(' | ', $r), ' |'), $rows)))])->save();

            return count($rows);
        }
        $order = (int) $this->cards()->max('order');
        foreach ($rows as [$title, $group, $desc]) {
            $this->cards()->create(['title' => mb_substr($title, 0, 255), 'group' => $group !== '' ? mb_substr($group, 0, 120) : null,
                'description' => $desc !== '' ? $desc : null, 'order' => ++$order, 'is_active' => true]);
        }
        $this->syncItemsText();

        return count($rows);
    }

    /** Odświeża tekstowy zapas `items` z kart (wszystkie, także ukryte — ukrycie dotyczy kart). */
    public function syncItemsText(): void
    {
        if (! KreatywniOfferItem::supported()) {
            return;
        }
        $this->forceFill(['items' => $this->cards()->get()->map(fn (KreatywniOfferItem $c) => $c->asTextLine())->implode("\n") ?: null])->saveQuietly();
    }

    /** Czy któryś widoczny warsztat ma grafikę (wtedy blok pokazuje karty zamiast pigułek). */
    public static function groupsHaveImages(array $groups): bool
    {
        foreach ($groups as $items) {
            foreach ($items as $item) {
                if (! empty($item['image'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<array{0: string, 1: string, 2: string}> wiersze „Tytuł | grupa | opis” bez pustych */
    public static function parseLines(string $text): array
    {
        return array_values(array_filter(array_map(fn (string $line) => array_slice(array_map('trim', explode('|', $line, 3)) + [1 => '', 2 => ''], 0, 3), self::lines($text)), fn ($r) => $r[0] !== ''));
    }

    /**
     * Oferty do bloku sprzedażowego: widoczne oferty modułu bieżącej witryny w kolejności, a gdy modułu nie ma albo
     * jest pusty — jedna oferta z tekstów szablonu (z wyglądem warsztatów z Ustawienia → Szablon). Pusta lista, gdy
     * w tekstach nie ma ani warsztatów, ani ceny.
     *
     * @return list<array{name: string, price: string, unit: string, note: string, features: list<string>, groups: array<string, list<array{title: string, desc: string, price: string, image: ?string, alt: string}>>, style: string, button: string, button_url: ?string}>
     */
    public static function forDisplay(KreatywniTexts $texts): array
    {
        $offers = self::offersToDisplay($texts);
        if ($texts->ordersOnline()) {
            return $offers;
        }

        // Zamówienia online wyłączone: przycisk prowadzi do kontaktu (button_url null) z napisem z ustawień.
        return array_map(fn (array $o) => ['button' => $texts->get('orders_off_button'), 'button_url' => null] + $o, $offers);
    }

    /** @return list<array<string, mixed>> */
    private static function offersToDisplay(KreatywniTexts $texts): array
    {
        if (self::supported() && self::forCurrentSite()->exists()) {
            return self::forCurrentSite()->where('is_active', true)->orderBy('order')->orderBy('id')->get()
                ->map(fn (self $o) => [
                    'name' => $o->name,
                    'price' => (string) $o->price,
                    'unit' => (string) $o->price_unit,
                    'note' => (string) $o->price_note,
                    'features' => $o->featureList(),
                    'groups' => $o->itemGroups(),
                    'style' => $o->itemsStyle(),
                    'button' => trim((string) $o->button_label),
                    'button_url' => KreatywniTexts::href((string) $o->button_url),
                ])->all();
        }

        if ($texts->rows('offer_items') === [] && $texts->get('offer_price') === '') {
            return [];
        }

        return [[
            'name' => $texts->get('offer_card_title'),
            'price' => $texts->get('offer_price'),
            'unit' => $texts->get('offer_price_unit'),
            'note' => $texts->get('offer_price_note'),
            'features' => array_column($texts->rows('offer_features'), 0),
            'groups' => self::groupItems($texts->rows('offer_items', 3)),
            'style' => $texts->offerStyle(),
            'button' => $texts->get('offer_button'),
            'button_url' => $texts->url('offer_button_url'),
        ]];
    }

    /**
     * Wiersze „Tytuł | grupa | opis” (grupa i opis opcjonalne) pogrupowane w kolejności z listy (bez grafik i cen).
     *
     * @param  list<array<int, string>>  $rows  [tytuł, grupa, opis]
     * @return array<string, list<array{title: string, desc: string, price: string, image: ?string, alt: string}>>
     */
    public static function groupItems(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row[0] ?? ''));
            if ($title === '') {
                continue;
            }
            $group = trim((string) ($row[1] ?? ''));
            $groups[$group !== '' ? $group : self::DEFAULT_GROUP][] = ['title' => $title, 'desc' => trim((string) ($row[2] ?? '')), 'price' => '', 'image' => null, 'alt' => '', 'url' => null];
        }

        return $groups;
    }

    /** @return list<string> niepuste wiersze tekstu */
    private static function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn ($l) => $l !== ''));
    }
}
