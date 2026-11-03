<?php

namespace App\Support;

use App\Models\Page;
use App\PageTypes\PageTypeRegistry;

/**
 * Pola typów stron, które można edytować poza klasycznym formularzem — w oknie
 * „Pola typu strony” edytora blokowego. Jedno miejsce z regułami i normalizacją
 * tych pól: korzysta z niego formularz strony (PageController) i edytor blokowy,
 * więc oba zapisują dane typu tak samo.
 *
 * Obsługiwane są typy z danymi w `type_data` (sekcje z app/PageTypes/Types) oraz
 * typy ze starszymi, własnymi kolumnami (COLUMN_TYPES). Typy bez swobodnej treści
 * (CLASSIC_ONLY) zostają w formularzu klasycznym.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class PageTypeFields
{
    /** Typy z rozbudowanym własnym układem (sekcje, ustawienia witryny) — jak dotąd tylko formularz klasyczny; osoba (about_person) ma osobny formularz. */
    public const CLASSIC_ONLY = ['about', 'contact'];

    /** Typy z własnymi kolumnami: typ => sekcja pól (ta sama co w formularzu strony). */
    private const COLUMN_TYPES = [
        'event' => 'admin.pages.partials.types.event',
        'schedule' => 'admin.pages.partials.types.schedule',
        'faq' => 'admin.pages.partials.types.faq',
        'training_institution' => 'admin.pages.partials.types.training',
        'legacy' => 'admin.pages.partials.types.legacy',
        'brand_assets' => 'admin.pages.partials.types.brand',
        'bip_move' => 'admin.pages.partials.types.bipmove',
        'wspolpraca' => 'admin.pages.partials.types.cooperation',
    ];

    /** Kolumny zapisywane przez dany typ (edytor blokowy nie dotyka pozostałych). */
    private const COLUMNS = [
        'event' => ['event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url'],
        'schedule' => ['schedule_items', 'schedule_change_notice', 'schedule_pending'],
        'faq' => ['faq_intro', 'faq_items'],
        'training_institution' => ['training_manager_name', 'training_manager_title', 'training_ris_number', 'training_bur_number', 'training_extra_info', 'training_bur_note'],
        'legacy' => ['legacy_name', 'legacy_intro'],
        'brand_assets' => ['brand_brandbook_url', 'brand_sections'],
        'bip_move' => ['bip_move_url', 'bip_move_note'],
        'wspolpraca' => ['cooperation_data'],
    ];

    /** Czy pola typu tej strony da się edytować w edytorze blokowym. */
    public static function supports(Page $page): bool
    {
        if (in_array($page->type, self::CLASSIC_ONLY, true)) {
            return false;
        }

        return isset(self::COLUMN_TYPES[$page->type])
            || (in_array($page->type, Page::typeDataTypes(), true) && PageTypeRegistry::get($page->type)?->adminView());
    }

    /** Czy strona tego typu ma swobodną treść (edytor blokowy ma sens). */
    public static function hasFreeContent(Page $page): bool
    {
        return ! $page->isAboutPerson() && ! in_array($page->type, self::CLASSIC_ONLY, true);
    }

    /** Nazwa typu bez opisu w nawiasie, np. „Wydarzenie”. */
    public static function typeName(Page $page): string
    {
        return trim(\Illuminate\Support\Str::before(Page::types()[$page->type] ?? (string) $page->type, ' ('));
    }

    /**
     * Sekcja pól typu wyrenderowana do HTML (te same widoki co w formularzu) albo null.
     */
    public static function render(Page $page): ?string
    {
        if (! self::supports($page)) {
            return null;
        }

        $vars = ['page' => $page, 'currentType' => $page->type];

        if (isset(self::COLUMN_TYPES[$page->type])) {
            return view(self::COLUMN_TYPES[$page->type], $vars + [
                'scheduleItems' => array_values(is_array($page->schedule_items) ? $page->schedule_items : []),
                'faqItems' => array_values((array) ($page->faq_items ?? [])),
            ])->render();
        }

        return view('admin.pages.partials.type-data-fields', $vars + ['onlyType' => $page->type])->render();
    }

    /**
     * Reguły walidacji pól typu (klucze jak w danych wejściowych formularza).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $type): array
    {
        if (in_array($type, Page::typeDataTypes(), true)) {
            return ['type_data' => ['nullable', 'array']] + (PageTypeRegistry::get($type)?->rules() ?? []);
        }

        return self::columnRules()[$type] ?? [];
    }

    /**
     * Reguły wszystkich typów z własnymi kolumnami (formularz strony waliduje je razem).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function allColumnRules(): array
    {
        return array_merge(...array_values(self::columnRules()));
    }

    /**
     * Wartości kolumn dla zapisu z edytora blokowego — tylko kolumny bieżącego typu.
     *
     * @param  array<string, mixed>  $data   dane po walidacji (rules())
     * @param  array<string, mixed>  $input  surowe dane wejściowe (repeatery, przełączniki)
     * @return array<string, mixed>
     */
    public static function columns(string $type, array $data, array $input): array
    {
        if (in_array($type, Page::typeDataTypes(), true)) {
            return ['type_data' => $data['type_data'] ?? []];
        }

        if ($type === 'wspolpraca') {
            return ['cooperation_data' => self::cooperationData((array) ($input['cooperation_data'] ?? []))];
        }

        $keys = self::COLUMNS[$type] ?? [];
        $data = self::apply($type, array_intersect_key($data, array_flip($keys)) + array_fill_keys($keys, null), $input);

        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Normalizacja kolumn typów dla formularza strony: pola bieżącego typu są
     * porządkowane, a pola innych typów czyszczone (po zmianie typu nie zostają
     * osierocone dane). Instytucja szkoleniowa jak dotąd zachowuje swoje pola.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function apply(string $type, array $data, array $input): array
    {
        if ($type !== 'event') {
            $data = array_merge($data, array_fill_keys(self::COLUMNS['event'], null));
        }

        // Harmonogram: uporządkowana lista terminów bez całkiem pustych wierszy.
        if ($type === 'schedule') {
            $items = [];
            foreach ((array) ($input['schedule_items'] ?? []) as $row) {
                $date = trim((string) ($row['date'] ?? ''));
                $time = trim((string) ($row['time'] ?? ''));
                $location = trim((string) ($row['location'] ?? ''));
                $note = trim((string) ($row['note'] ?? ''));

                if ($date === '' && $time === '' && $location === '' && $note === '') {
                    continue;
                }

                $items[] = [
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'note' => $note,
                    'changed' => ! empty($row['changed']),
                ];
            }
            $data['schedule_items'] = $items ?: null;
            $data['schedule_pending'] = filter_var($input['schedule_pending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } else {
            $data['schedule_items'] = null;
            $data['schedule_change_notice'] = null;
            $data['schedule_pending'] = false;
        }

        // FAQ: pary pytanie/odpowiedź (puste wiersze pomijane).
        if ($type === 'faq') {
            $data['faq_items'] = self::compactRows($input['faq_items'] ?? [], ['question', 'answer']);
        } else {
            $data['faq_intro'] = null;
            $data['faq_items'] = null;
        }

        // „Prezentacja tego, co było": nazwa poprzednika + wstęp.
        if ($type === 'legacy') {
            $data['legacy_name'] = trim((string) ($data['legacy_name'] ?? '')) ?: null;
            $data['legacy_intro'] = trim((string) ($data['legacy_intro'] ?? '')) ?: null;
        } else {
            $data['legacy_name'] = null;
            $data['legacy_intro'] = null;
        }

        // Marka — identyfikacja wizualna: brandbook URL + sekcje (klucz z tytułu, gdy pusty).
        if ($type === 'brand_assets') {
            $data['brand_brandbook_url'] = trim((string) ($data['brand_brandbook_url'] ?? '')) ?: null;
            $sections = [];
            foreach ((array) ($input['brand_sections'] ?? []) as $row) {
                $title = trim((string) ($row['title'] ?? ''));
                $key = trim((string) ($row['key'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $sections[] = ['key' => $key !== '' ? $key : \Illuminate\Support\Str::slug($title), 'title' => $title];
            }
            $data['brand_sections'] = $sections ?: null;
        } else {
            $data['brand_brandbook_url'] = null;
            $data['brand_sections'] = null;
        }

        // „Przeniesiono do BIP”: link i dodatkowa informacja (puste = null; formularz strony czyści je dla innych typów sam).
        if ($type === 'bip_move') {
            $data['bip_move_url'] = trim((string) ($data['bip_move_url'] ?? '')) ?: null;
            $data['bip_move_note'] = trim((string) ($data['bip_move_note'] ?? '')) ?: null;
        }

        return $data;
    }

    /**
     * Treść sekcji strony „Współpraca” (cooperation_data) z danych formularza — pola przycięte,
     * puste statystyki pominięte. Wspólne dla formularza strony i edytora blokowego.
     *
     * @param  array<string, mixed>  $cd
     * @return array<string, mixed>
     */
    public static function cooperationData(array $cd): array
    {
        $sectors = [];
        foreach ((array) ($cd['sectors'] ?? []) as $row) {
            $sectors[] = [
                'icon'  => trim((string) ($row['icon'] ?? '')),
                'color' => trim((string) ($row['color'] ?? 'brand')),
                'title' => trim((string) ($row['title'] ?? '')),
                'text'  => trim((string) ($row['text'] ?? '')),
                'tag1'  => trim((string) ($row['tag1'] ?? '')),
                'tag2'  => trim((string) ($row['tag2'] ?? '')),
                'tag3'  => trim((string) ($row['tag3'] ?? '')),
            ];
        }
        $forms = [];
        foreach ((array) ($cd['forms'] ?? []) as $row) {
            $forms[] = [
                'icon'  => trim((string) ($row['icon'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'text'  => trim((string) ($row['text'] ?? '')),
            ];
        }
        $stats = [];
        foreach ((array) ($cd['stats'] ?? []) as $row) {
            $val = trim((string) ($row['value'] ?? ''));
            $lbl = trim((string) ($row['label'] ?? ''));
            if ($val !== '' && $lbl !== '') {
                $stats[] = ['value' => $val, 'label' => $lbl];
            }
        }
        return [
            'hero_badge'         => trim((string) ($cd['hero_badge'] ?? '')),
            'hero_subtitle'      => trim((string) ($cd['hero_subtitle'] ?? '')),
            'hero_cta1_label'    => trim((string) ($cd['hero_cta1_label'] ?? '')),
            'hero_cta1_url'      => trim((string) ($cd['hero_cta1_url'] ?? '')),
            'hero_cta2_label'    => trim((string) ($cd['hero_cta2_label'] ?? '')),
            'stats'              => $stats ?: null,
            'sectors_heading'    => trim((string) ($cd['sectors_heading'] ?? '')),
            'sectors_subtitle'   => trim((string) ($cd['sectors_subtitle'] ?? '')),
            'sectors'            => $sectors,
            'forms_heading'      => trim((string) ($cd['forms_heading'] ?? '')),
            'forms_subtitle'     => trim((string) ($cd['forms_subtitle'] ?? '')),
            'forms'              => $forms,
            'cta_heading'        => trim((string) ($cd['cta_heading'] ?? '')),
            'cta_text'           => trim((string) ($cd['cta_text'] ?? '')),
            'cta_button_label'   => trim((string) ($cd['cta_button_label'] ?? '')),
            'cta_button_url'     => trim((string) ($cd['cta_button_url'] ?? '')),
            'under_construction' => ! empty($cd['under_construction']),
            'form_enabled'       => ! empty($cd['form_enabled']),
            'form_recipient'     => trim((string) ($cd['form_recipient'] ?? '')),
            'form_title'         => trim((string) ($cd['form_title'] ?? '')),
            'form_subtitle'      => trim((string) ($cd['form_subtitle'] ?? '')),
            'form_confirmation'  => trim((string) ($cd['form_confirmation'] ?? '')),
        ];
    }

    /**
     * Wiersze repeatera przycięte do podanych kluczy, bez całkiem pustych wierszy;
     * null, gdy nic nie zostało (kolumna JSON zostaje czysta).
     */
    public static function compactRows($rows, array $keys): ?array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            $clean = [];
            foreach ($keys as $key) {
                $clean[$key] = trim((string) ($row[$key] ?? ''));
            }
            if (implode('', $clean) !== '') {
                $out[] = $clean;
            }
        }

        return $out ?: null;
    }

    /** @return array<string, array<string, array<int, mixed>>> */
    private static function columnRules(): array
    {
        return [
            'event' => [
                'event_mode' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Page::EVENT_MODES))],
                'event_when' => ['nullable', 'string', 'max:255'],
                'event_location' => ['nullable', 'string', 'max:255'],
                'event_how_to_join' => ['nullable', 'string', 'max:2000'],
                'event_registration_url' => ['nullable', 'url', 'max:255'],
            ],
            'schedule' => [
                'schedule_change_notice' => ['nullable', 'string', 'max:2000'],
                'schedule_items' => ['nullable', 'array'],
                'schedule_items.*.date' => ['nullable', 'string', 'max:255'],
                'schedule_items.*.time' => ['nullable', 'string', 'max:255'],
                'schedule_items.*.location' => ['nullable', 'string', 'max:255'],
                'schedule_items.*.note' => ['nullable', 'string', 'max:500'],
            ],
            'wspolpraca' => [
                'cooperation_data' => ['nullable', 'array'],
                'cooperation_data.hero_badge' => ['nullable', 'string', 'max:100'],
                'cooperation_data.hero_subtitle' => ['nullable', 'string', 'max:600'],
                'cooperation_data.hero_cta1_label' => ['nullable', 'string', 'max:80'],
                'cooperation_data.hero_cta1_url' => ['nullable', 'string', 'max:500'],
                'cooperation_data.hero_cta2_label' => ['nullable', 'string', 'max:80'],
                'cooperation_data.sectors_heading' => ['nullable', 'string', 'max:200'],
                'cooperation_data.sectors_subtitle' => ['nullable', 'string', 'max:400'],
                'cooperation_data.sectors' => ['nullable', 'array'],
                'cooperation_data.sectors.*.icon' => ['nullable', 'string', 'max:100'],
                'cooperation_data.sectors.*.color' => ['nullable', 'string', 'max:30'],
                'cooperation_data.sectors.*.title' => ['nullable', 'string', 'max:120'],
                'cooperation_data.sectors.*.text' => ['nullable', 'string', 'max:600'],
                'cooperation_data.sectors.*.tag1' => ['nullable', 'string', 'max:80'],
                'cooperation_data.sectors.*.tag2' => ['nullable', 'string', 'max:80'],
                'cooperation_data.sectors.*.tag3' => ['nullable', 'string', 'max:80'],
                'cooperation_data.forms_heading' => ['nullable', 'string', 'max:200'],
                'cooperation_data.forms_subtitle' => ['nullable', 'string', 'max:400'],
                'cooperation_data.forms' => ['nullable', 'array'],
                'cooperation_data.forms.*.icon' => ['nullable', 'string', 'max:100'],
                'cooperation_data.forms.*.title' => ['nullable', 'string', 'max:120'],
                'cooperation_data.forms.*.text' => ['nullable', 'string', 'max:600'],
                'cooperation_data.stats' => ['nullable', 'array'],
                'cooperation_data.stats.*.value' => ['nullable', 'string', 'max:30'],
                'cooperation_data.stats.*.label' => ['nullable', 'string', 'max:80'],
                'cooperation_data.cta_heading' => ['nullable', 'string', 'max:200'],
                'cooperation_data.cta_text' => ['nullable', 'string', 'max:600'],
                'cooperation_data.cta_button_label' => ['nullable', 'string', 'max:80'],
                'cooperation_data.cta_button_url' => ['nullable', 'string', 'max:500'],
                'cooperation_data.under_construction' => ['nullable', 'boolean'],
                'cooperation_data.form_enabled' => ['nullable', 'boolean'],
                'cooperation_data.form_recipient' => ['nullable', 'email', 'max:200'],
                'cooperation_data.form_title' => ['nullable', 'string', 'max:150'],
                'cooperation_data.form_subtitle' => ['nullable', 'string', 'max:400'],
                'cooperation_data.form_confirmation' => ['nullable', 'string', 'max:400'],
            ],
            'bip_move' => [
                'bip_move_url' => ['nullable', 'url', 'max:255'],
                'bip_move_note' => ['nullable', 'string', 'max:2000'],
            ],
            'faq' => [
                'faq_intro' => ['nullable', 'string', 'max:2000'],
                'faq_items' => ['nullable', 'array'],
                'faq_items.*.question' => ['nullable', 'string', 'max:255'],
                'faq_items.*.answer' => ['nullable', 'string', 'max:20000'],
            ],
            'training_institution' => [
                'training_manager_name' => ['nullable', 'string', 'max:255'],
                'training_manager_title' => ['nullable', 'string', 'max:255'],
                'training_ris_number' => ['nullable', 'string', 'max:100'],
                'training_bur_number' => ['nullable', 'string', 'max:100'],
                'training_extra_info' => ['nullable', 'string', 'max:10000'],
                'training_bur_note' => ['nullable', 'string', 'max:2000'],
            ],
            'legacy' => [
                'legacy_name' => ['nullable', 'string', 'max:255'],
                'legacy_intro' => ['nullable', 'string', 'max:2000'],
            ],
            'brand_assets' => [
                'brand_brandbook_url' => ['nullable', 'url', 'max:500'],
                'brand_sections' => ['nullable', 'array'],
                'brand_sections.*.title' => ['nullable', 'string', 'max:120'],
                'brand_sections.*.key' => ['nullable', 'string', 'max:80'],
            ],
        ];
    }
}
