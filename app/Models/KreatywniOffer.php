<?php

namespace App\Models;

use App\Support\KreatywniTexts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Oferta z mikro modułu „Mikrooferta – Kreatywni” (panel: Treści → Strona główna → Mikrooferta – Kreatywni).
 * Pokazywana w bloku sprzedażowym (partials/offer-sales) na stronie głównej i na górze strony „Oferta” szablonu
 * „kreatywni”: karta cenowa i warsztaty w grupach. Bez tabeli (kod przed migracją) albo bez żadnej oferty witryny
 * szablon bierze ofertę z tekstów (forDisplay()).
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

    /** @return array<string, list<array{title: string, desc: string}>> warsztaty pogrupowane: grupa => [tytuł, opis] */
    public function itemGroups(): array
    {
        return self::groupItems(array_map(fn (string $line) => array_map('trim', explode('|', $line, 3)) + [1 => '', 2 => ''], self::lines((string) $this->items)));
    }

    public function itemCount(): int
    {
        return count(self::lines((string) $this->items));
    }

    /**
     * Oferty do bloku sprzedażowego: widoczne oferty modułu bieżącej witryny w kolejności, a gdy modułu nie ma albo
     * jest pusty — jedna oferta z tekstów szablonu (z wyglądem warsztatów z Ustawienia → Szablon). Pusta lista, gdy
     * w tekstach nie ma ani warsztatów, ani ceny.
     *
     * @return list<array{name: string, price: string, unit: string, note: string, features: list<string>, groups: array<string, list<array{title: string, desc: string}>>, style: string, button: string, button_url: ?string}>
     */
    public static function forDisplay(KreatywniTexts $texts): array
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
     * Wiersze „Tytuł | grupa | opis” (grupa i opis opcjonalne) pogrupowane w kolejności z listy.
     *
     * @param  list<array<int, string>>  $rows  [tytuł, grupa, opis]
     * @return array<string, list<array{title: string, desc: string}>>
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
            $groups[$group !== '' ? $group : self::DEFAULT_GROUP][] = ['title' => $title, 'desc' => trim((string) ($row[2] ?? ''))];
        }

        return $groups;
    }

    /** @return list<string> niepuste wiersze tekstu */
    private static function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn ($l) => $l !== ''));
    }
}
