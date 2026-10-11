<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Starsze typy stron (oferta, poradnik, słownik, studium, cennik, zespół, dokumenty,
 * regulamin, przekierowanie, sprawozdania, partnerzy) w płaskim stylu jak „Rekrutacja":
 * bez etykiet i obramowań, tylko kolory firmowe, a tekst nigdy w kolorze text-brand
 * (#1e6dff na białym = 4,49:1 — za mało dla WCAG 1.4.3).
 */
class TypedPagesStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    /** Przykładowe dane każdego typu (jak w PageTypesDemoSeeder). */
    private function pages(): array
    {
        return [
            'service' => [
                'content' => '<p>Sprawdzamy stronę ręcznie i narzędziami.</p>',
                'type_data' => [
                    'lead' => 'Dowiedz się, co blokuje użytkowników.',
                    'audience' => [['text' => 'Urzędy'], ['text' => 'Organizacje pozarządowe']],
                    'benefits' => [['icon' => 'fa-solid fa-list-check', 'title' => 'Raport z priorytetami', 'text' => 'Wiesz, co naprawić najpierw.']],
                    'steps' => [['title' => 'Rozmowa', 'text' => 'Ustalamy zakres.'], ['title' => 'Audyt', 'text' => '2–4 tygodnie.']],
                    'cta_label' => 'Zapytaj o wycenę', 'cta_url' => '/kontakt',
                    'contact_name' => 'Anna Kowalska', 'contact_email' => 'audyty@example.org', 'contact_phone' => '500 000 000',
                ],
            ],
            'guide' => [
                'type_data' => [
                    'lead' => 'Jak zrobić dostępny PDF.',
                    'time_estimate' => '20 minut', 'level' => 'basic',
                    'requirements' => [['text' => 'Microsoft Word 2016']],
                    'steps' => [
                        ['title' => 'Użyj stylów nagłówków', 'text' => 'Nagłówek 1, 2, 3.', 'tip' => 'Okienko Nawigacja pokaże strukturę.'],
                        ['title' => 'Zapisz jako PDF', 'text' => 'Zaznacz znaczniki struktury.'],
                    ],
                    'summary' => 'Taki PDF ma strukturę.',
                ],
            ],
            'glossary' => [
                'type_data' => [
                    'lead' => 'Pojęcia prostym językiem.',
                    'terms' => [
                        ['term' => 'Czytnik ekranu', 'definition' => 'Program, który odczytuje treść ekranu.'],
                        ['term' => 'WCAG', 'definition' => 'Wytyczne dostępności.', 'related' => 'Czytnik ekranu'],
                    ],
                ],
            ],
            'case_study' => [
                'type_data' => [
                    'lead' => 'Jak gmina poprawiła zgodność z WCAG.',
                    'client' => 'Gmina', 'sector' => 'Samorząd', 'period' => '2026',
                    'challenge' => 'Strona nie działała z klawiatury.', 'solution' => 'Audyt i szkolenia.',
                    'results' => [['value' => '95%', 'label' => 'zgodności'], ['value' => '12', 'label' => 'redaktorów']],
                    'quote' => 'Mieszkańcy załatwiają wszystko sami.', 'quote_author' => 'Sekretarz gminy',
                    'cta_label' => 'Porozmawiajmy', 'cta_url' => '/kontakt',
                ],
            ],
            'pricing' => [
                'type_data' => [
                    'lead' => 'Ceny orientacyjne.',
                    'price_rows' => [
                        ['group' => 'Audyty', 'item' => 'Audyt strony', 'price' => 'od 2 500 zł', 'note' => 'z raportem'],
                        ['group' => 'Szkolenia', 'item' => 'Szkolenie (4 h)', 'price' => '1 800 zł', 'note' => 'do 15 osób'],
                    ],
                    'payment_info' => 'Faktura z 14-dniowym terminem płatności.',
                    'cta_label' => 'Zapytaj o ofertę', 'cta_url' => '/kontakt',
                ],
            ],
            'team' => [
                'type_data' => [
                    'lead' => 'Osoby, które prowadzą audyty.',
                    'members' => [['name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'text' => 'Audytorka WCAG.', 'email' => 'anna@example.org']],
                ],
            ],
            'documents' => [
                'type_data' => [
                    'lead' => 'Wzory i materiały.',
                    'docs' => [
                        ['group' => 'Wzory', 'title' => 'Wzór deklaracji', 'url' => '/storage/przyklad/deklaracja.docx', 'note' => 'DOCX'],
                        ['group' => 'Wzory', 'title' => 'Lista kontrolna', 'url' => '/storage/przyklad/lista.pdf'],
                        ['group' => 'Odnośniki', 'title' => 'WCAG 2.2', 'url' => 'https://www.w3.org/TR/WCAG22/'],
                    ],
                ],
            ],
            'regulation' => [
                'content' => '<h2>§ 1. Postanowienia ogólne</h2><p>Zasady.</p><h2>§ 2. Zgłoszenia</h2><p>Formularz.</p><h2>§ 3. Dostępność</h2><p>PJM i ETR.</p>',
                'type_data' => [
                    'lead' => 'Zasady udziału w szkoleniach.', 'valid_from' => '2026-01-01', 'version' => '2.0',
                    'versions' => [['label' => 'Wersja 1.0', 'url' => '/storage/regulamin-1.pdf']],
                ],
            ],
            'redirect' => [
                'type_data' => ['target_url' => '/aktualnosci', 'redirect_mode' => 'notice', 'redirect_message' => 'Treść jest teraz w Aktualnościach.', 'redirect_label' => 'Przejdź do aktualności'],
            ],
            'reports' => [
                'content' => '<p>Tabela sprawozdań.</p>',
                'type_data' => ['lead' => 'Sprawozdania merytoryczne i finansowe.'],
            ],
            'partners' => [
                'type_data' => [
                    'lead' => 'Dziękujemy za wsparcie.',
                    'partners_layout' => 'cards',
                    'partner_groups' => [['title' => 'Partnerzy merytoryczni'], ['title' => 'Sponsorzy']],
                    'partners' => [
                        ['group' => 'Partnerzy merytoryczni', 'name' => 'Uczelnia', 'note' => 'Badania', 'url' => 'https://example.org'],
                        ['group' => 'Sponsorzy', 'name' => 'Firma', 'note' => 'Sponsor szkoleń'],
                    ],
                    'partners_cta_label' => 'Zostań partnerem', 'partners_cta_url' => '/kontakt',
                ],
            ],
        ];
    }

    /** HTML samego widoku typu (element z data-page-type) — bez nagłówka i stopki serwisu. */
    private function typedSection(string $html, string $type): string
    {
        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $node = (new \DOMXPath($doc))->query('//*[@data-page-type="'.$type.'"]')->item(0);
        $this->assertNotNull($node, "Brak sekcji data-page-type=\"{$type}\"");

        return $doc->saveHTML($node);
    }

    private function assertFlatStyle(string $section, string $label): void
    {
        preg_match_all('/\s:?class="([^"]*)"/', $section, $m);
        $classes = implode(' ', $m[1]);

        // Kontrast: text-brand (#1e6dff) na jasnym tle ma 4,49:1 — dozwolony tylko text-brand-dark.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])text-brand(?![-\w])/', $classes, "{$label}: text-brand");
        // Bez etykiet (pigułek).
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])rounded-full(?![-\w])/', $classes, "{$label}: rounded-full");
        // Bez obramowań i linii podziału.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])border(?:-[\w\/.\[\]#-]+)?(?![-\w])/', $classes, "{$label}: border");
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])divide-/', $classes, "{$label}: divide");
        // Obwódki tylko jako fokus klawiatury.
        foreach (preg_split('/\s+/', $classes) as $token) {
            if (preg_match('/(^|:)ring(-|$)/', $token)) {
                $this->assertMatchesRegularExpression('/^focus(-visible|-within)?:/', $token, "{$label}: {$token}");
            }
        }
        // Tylko kolory firmowe (bez palety Tailwinda poza szarościami).
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(?:[\w-]+:)?(?:text|bg|border|ring|from|to|via|fill|stroke)-(?:green|amber|blue|red|purple|yellow|orange|emerald|indigo|sky|teal|rose|pink|violet)-\d/', $classes, "{$label}: kolor spoza palety");
        // Bez obramowań w CSS osadzonym w widoku.
        $this->assertDoesNotMatchRegularExpression('/border\s*:\s*\d/i', $section, "{$label}: CSS border");
        $this->assertDoesNotMatchRegularExpression('/border-(?:top|bottom|left|right)\s*:/i', $section, "{$label}: CSS border-*");
    }

    public function test_starsze_typy_stron_sa_plaskie_i_kontrastowe(): void
    {
        foreach (['default', 'feer'] as $template) {
            SiteSetting::current()->update(['site_template' => $template]);
            \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
            \Illuminate\Support\Facades\Cache::flush();

            foreach ($this->pages() as $type => $attrs) {
                $slug = str_replace('_', '-', $type).'-'.$template;
                Page::create($attrs + ['title' => 'Strona '.$type, 'slug' => $slug, 'type' => $type, 'is_published' => true]);

                $html = $this->get('/'.$slug)->assertOk()->getContent();
                $section = $this->typedSection($html, $type);
                $label = "{$type} ({$template})";

                $this->assertStringContainsString('Strona '.$type, $section, $label);
                $this->assertFlatStyle($section, $label);
                $this->assertSame($template === 'feer', str_contains($section, 'h-1 w-14 bg-brand'), "{$label}: akcent FEER");
            }
        }
    }

    public function test_dwie_kolumny_z_karta_po_stronie_i_dane_typow(): void
    {
        foreach ($this->pages() as $type => $attrs) {
            Page::create($attrs + ['title' => 'Strona '.$type, 'slug' => str_replace('_', '-', $type), 'type' => $type, 'is_published' => true]);
        }

        // Oferta: przycisk i kontakt w kolumnie bocznej, kroki jako zwykła numeracja.
        $service = $this->typedSection($this->get('/service')->assertOk()->getContent(), 'service');
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_22rem]', $service);
        $this->assertStringContainsString('rounded-md bg-brand-light', $service);
        $this->assertStringContainsString('Zapytaj o wycenę', $service);
        $this->assertStringContainsString('mailto:audyty@example.org', $service);
        $this->assertStringContainsString('2. Audyt', $service);
        $this->assertStringNotContainsString('>Oferta<', $service);

        // Poradnik: metryka, spis kroków, odhaczanie w localStorage.
        $guide = $this->typedSection($this->get('/guide')->assertOk()->getContent(), 'guide');
        $this->assertStringContainsString('20 minut', $guide);
        $this->assertStringContainsString('Spis kroków', $guide);
        $this->assertStringContainsString('href="#krok-2"', $guide);
        $this->assertStringContainsString('guide-progress-', $guide);
        $this->assertStringContainsString('aria-pressed', $guide);

        // Słownik: indeks liter i kotwice haseł, powiązane hasło jako link.
        $glossary = $this->typedSection($this->get('/glossary')->assertOk()->getContent(), 'glossary');
        $this->assertStringContainsString('href="#litera-c"', $glossary);
        $this->assertStringContainsString('id="czytnik-ekranu"', $glossary);
        $this->assertStringContainsString('href="#czytnik-ekranu"', $glossary);

        // Studium: metryka w karcie, efekty bez białego tekstu na #1e6dff.
        $case = $this->typedSection($this->get('/case-study')->assertOk()->getContent(), 'case_study');
        $this->assertStringContainsString('Samorząd', $case);
        $this->assertStringContainsString('95%', $case);
        $this->assertStringNotContainsString('bg-brand p-5 text-white', $case);

        // Cennik: tabela z nagłówkami i podpisem, „Jak płacić" w kolumnie bocznej.
        $pricing = $this->typedSection($this->get('/pricing')->assertOk()->getContent(), 'pricing');
        $this->assertStringContainsString('<th scope="row"', $pricing);
        $this->assertStringContainsString('<caption class="sr-only">Cennik: Audyty</caption>', $pricing);
        $this->assertStringContainsString('Jak płacić', $pricing);

        // Dokumenty: rodzaj pliku zwykłym tekstem, link zewnętrzny z ostrzeżeniem.
        $docs = $this->typedSection($this->get('/documents')->assertOk()->getContent(), 'documents');
        $this->assertStringContainsString('DOCX', $docs);
        $this->assertStringNotContainsString('dl-type', $docs);
        $this->assertStringContainsString('rel="noopener"', $docs);
        $this->assertStringContainsString('(otwiera się w nowej karcie)', $docs);

        // Regulamin: data, wersja, spis treści i poprzednie wersje w kolumnie bocznej.
        $reg = $this->typedSection($this->get('/regulation')->assertOk()->getContent(), 'regulation');
        $this->assertStringContainsString('1 stycznia 2026', $reg);
        $this->assertStringContainsString('Spis treści', $reg);
        $this->assertStringContainsString('Wersja 1.0', $reg);

        // Partnerzy: karty, przycisk „Zostań partnerem".
        $partners = $this->typedSection($this->get('/partners')->assertOk()->getContent(), 'partners');
        $this->assertStringContainsString('Uczelnia', $partners);
        $this->assertStringContainsString('Zostań partnerem', $partners);
        $this->assertStringNotContainsString('pn-logo', $partners);
    }
}
