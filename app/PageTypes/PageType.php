<?php

namespace App\PageTypes;

/**
 * Typ strony (Page::$type) — jedno miejsce z opisem typu: nazwa w panelu, grupa
 * i ikona w wyborze typu, reguły walidacji pól `type_data`, listy normalizowane
 * przez Page::typeData(), widok publiczny i sekcja pól w edycji strony.
 *
 * Jak dodać nowy typ strony (oparty na kolumnie JSON `type_data`):
 *
 *  1. Utwórz klasę w app/PageTypes/Types/ (np. Webinar.php) dziedziczącą po PageType.
 *     Rejestr (PageTypeRegistry) wykrywa ją sam — nie trzeba niczego dopisywać
 *     w modelu Page, kontrolerze ani w widokach wspólnych. Ustaw:
 *       - key()        — wartość kolumny `type` (np. 'webinar'),
 *       - label()      — „Nazwa (krótki opis)" — część w nawiasie to opis karty w wyborze typu,
 *       - group()      — jedna z PageTypeRegistry::GROUPS (inna nazwa utworzy nową grupę na końcu),
 *       - order()      — pozycja na liście typów; groupOrder() — pozycja w grupie (domyślnie = order()),
 *       - icon()       — klasa Font Awesome bez „fa-solid" (np. 'fa-video'),
 *       - usesTypeData() => true, standardLayout() => false,
 *       - rules()      — reguły dla kluczy 'type_data.<pole>' (pola bez reguły nie zostaną zapisane!),
 *       - listKeys()   — klucze list wierszy (repeaterów), które Page::typeData() ma oczyścić z pustych wierszy,
 *       - frontView()  — widok publiczny, np. 'page.partials.typed.webinar',
 *       - adminView()  — sekcja pól w panelu, np. 'admin.pages.partials.type-data.webinar'.
 *  2. Dodaj widok publiczny (resources/views/page/partials/typed/<typ>.blade.php) —
 *     dostępne są $page i $page->typeData().
 *  3. Dodaj sekcję pól w panelu (resources/views/admin/pages/partials/type-data/<typ>.blade.php):
 *     główny <div data-type-fields="<typ>"> z klasą 'hidden', gdy $currentType !== '<typ>';
 *     pola nazywaj type_data[<pole>]. Do dyspozycji są wspólne pomocniki z
 *     type-data-fields.blade.php ($td, $tdRows, $inp, $lbl, $box, $addBtn, $rmBtn, $recRow).
 *  4. (Opcjonalnie) metoda isXxx() w Page i przykładowa strona w PageTypesDemoSeeder.
 *
 * Typy „starsze" (event, about, faq…) trzymają dane we własnych kolumnach — ich
 * klasy opisują tylko nazwę, grupę, ikonę i układ; widoki i reguły zostają
 * w typed-content.blade.php, partials/types/*.blade.php i PageController.
 */
abstract class PageType
{
    /** Wartość kolumny `pages.type`. */
    abstract public function key(): string;

    /** Nazwa w panelu: „Nazwa (opis)". */
    abstract public function label(): string;

    /** Grupa w wyborze typu i filtrze listy stron (PageTypeRegistry::GROUPS). */
    abstract public function group(): string;

    /** Kolejność na liście typów (Page::types()). */
    abstract public function order(): int;

    /** Kolejność w obrębie grupy (wybór typu, filtr listy). */
    public function groupOrder(): int
    {
        return $this->order();
    }

    /** Ikona Font Awesome (bez „fa-solid") albo null — wtedy widoki używają fa-file-lines. */
    public function icon(): ?string
    {
        return null;
    }

    /** Czy strona używa standardowej sekcji treści (a nie własnego układu typu). */
    public function standardLayout(): bool
    {
        return false;
    }

    /** Czy dane typu trzymamy we wspólnej kolumnie JSON `type_data`. */
    public function usesTypeData(): bool
    {
        return false;
    }

    /**
     * Reguły walidacji pól `type_data.*` tego typu (klucze jak w Request::validate,
     * np. 'type_data.rec_status'). Wspólne klucze (lead, cta_*, contact_*, listy…)
     * mogą deklarować różne typy — rejestr je scala.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Klucze `type_data` będące listami wierszy (repeatery) — Page::typeData()
     * zamienia je na listy bez pustych wierszy.
     *
     * @return array<int, string>
     */
    public function listKeys(): array
    {
        return [];
    }

    /** Widok publiczny typu (dołączany w typed-content.blade.php) albo null. */
    public function frontView(): ?string
    {
        return null;
    }

    /** Sekcja pól w edycji strony (dołączana w type-data-fields.blade.php) albo null. */
    public function adminView(): ?string
    {
        return null;
    }

    /**
     * Reguły pól wspólnych dla wielu typów `type_data` — jedna definicja, żeby
     * typy deklarujące to samo pole miały identyczne reguły.
     *
     * @return array<string, array<int, mixed>>
     */
    protected static function sharedRules(string ...$blocks): array
    {
        $shared = [
            'lead' => [
                'type_data.lead' => ['nullable', 'string', 'max:1000'],
            ],
            'cta' => [
                'type_data.cta_label' => ['nullable', 'string', 'max:80'],
                'type_data.cta_url' => ['nullable', 'string', 'max:500'],
            ],
            'contact' => [
                'type_data.contact_name' => ['nullable', 'string', 'max:120'],
                'type_data.contact_email' => ['nullable', 'email', 'max:190'],
                'type_data.contact_phone' => ['nullable', 'string', 'max:40'],
            ],
            'audience' => [
                'type_data.audience' => ['nullable', 'array'],
                'type_data.audience.*.text' => ['nullable', 'string', 'max:200'],
            ],
            'benefits' => [
                'type_data.benefits' => ['nullable', 'array'],
                'type_data.benefits.*.icon' => ['nullable', 'string', 'max:100'],
                'type_data.benefits.*.title' => ['nullable', 'string', 'max:120'],
                'type_data.benefits.*.text' => ['nullable', 'string', 'max:500'],
            ],
            'steps' => [
                'type_data.steps' => ['nullable', 'array'],
                'type_data.steps.*.title' => ['nullable', 'string', 'max:160'],
                'type_data.steps.*.text' => ['nullable', 'string', 'max:5000'],
                'type_data.steps.*.tip' => ['nullable', 'string', 'max:500'],
            ],
            'requirements' => [
                'type_data.requirements' => ['nullable', 'array'],
                'type_data.requirements.*.text' => ['nullable', 'string', 'max:200'],
            ],
            'docs' => [
                'type_data.docs' => ['nullable', 'array', 'max:100'],
                'type_data.docs.*.group' => ['nullable', 'string', 'max:120'],
                'type_data.docs.*.title' => ['nullable', 'string', 'max:200'],
                'type_data.docs.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/|mailto:)~i'],
                'type_data.docs.*.note' => ['nullable', 'string', 'max:300'],
            ],
        ];

        $rules = [];
        foreach ($blocks as $block) {
            $rules += $shared[$block] ?? throw new \InvalidArgumentException("Nieznany blok reguł: {$block}");
        }

        return $rules;
    }
}
