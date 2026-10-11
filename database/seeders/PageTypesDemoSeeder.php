<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Przykładowa strona każdego typu z Page::types(), z treściami w realiach FEER
 * (dostępność cyfrowa, osoby z niepełnosprawnościami). Wszystkie trafiają pod
 * stronę „Przykłady typów stron" (/przyklady-typow-stron, kafelki podstron),
 * poza menu. Seeder jest idempotentny — slugi „przyklad-…".
 *
 *   php artisan db:seed --class=PageTypesDemoSeeder
 *
 * Strona typu „Kontakt" jest publikowana tylko, gdy istnieje już inna
 * opublikowana strona kontaktowa — /kontakt kieruje na najstarszą stronę tego
 * typu, więc przykład nie może jej przejąć. Inaczej zostaje szkicem (podgląd z panelu).
 */
class PageTypesDemoSeeder extends Seeder
{
    private ?int $parentId = null;

    private int $order = 0;

    public function run(): void
    {
        $parent = Page::updateOrCreate(['slug' => 'przyklady-typow-stron'], [
            'title' => 'Przykłady typów stron',
            'type' => 'standard',
            'is_published' => true,
            'show_in_menu' => false,
            'side_nav_style' => 'tiles',
            'content' => '<p>Po jednej przykładowej stronie każdego typu dostępnego w panelu. Otwórz stronę, żeby zobaczyć układ, a w panelu — jakie pola ma dany typ.</p>',
        ]);
        $this->parentId = $parent->id;

        $this->standard();
        $this->event();
        $this->schedule();
        $this->about();
        $this->faq();
        $this->trainingInstitution();
        $this->bipMove();
        $this->internal();
        $this->internalHub();
        $this->linksHub();
        $this->tilesGrid();
        $this->cooperation();
        $this->legacy();
        $this->brandAssets();
        $this->contact();
        $this->service();
        $this->guide();
        $this->glossary();
        $this->caseStudy();
        $this->pricing();
        $this->team();
        $this->documents();
        $this->regulation();
        $this->redirect();
        $this->reports();
        $this->partners();
        $this->recruitment();
        $this->tender();

        // Listy stron typów „Rekrutacja do projektu" i „Zapytanie ofertowe" (shortcody z filtrem i archiwum).
        $this->page('lista-naborow', 'standard', [
            'title' => 'Przykład: lista naborów',
            'content' => '<p>Wszystkie nabory do naszych projektów. Domyślnie widzisz nabory aktualne — zakończone znajdziesz w archiwum.</p><p>[nabory]</p>',
        ]);
        $this->page('lista-zapytan-ofertowych', 'standard', [
            'title' => 'Przykład: lista zapytań ofertowych',
            'content' => '<p>Zapytania ofertowe prowadzone zgodnie z zasadą konkurencyjności. Zakończone postępowania znajdziesz w archiwum.</p><p>[zapytania-ofertowe]</p>',
        ]);

        $this->sectionTemplate();
    }

    /**
     * Szablon wizualny „Strona działu": strona z podstronami (menu działu po prawej),
     * aktualnościami i — jeśli lokalnie są zdjęcia — sliderem.
     */
    private function sectionTemplate(): void
    {
        $section = $this->page('strona-dzialu', 'standard', [
            'title' => 'Przykład: strona działu (szablon)',
            'page_template' => 'section',
            'layout_data' => ['news_category_id' => null, 'news_count' => 4, 'slider' => true],
            'content' => '<h2>O dziale</h2><p>Dział dostępności cyfrowej prowadzi audyty, szkolenia i konsultacje. Szablon „Strona działu” pokazuje slider ze zdjęć strony, aktualności i menu podstron działu.</p>',
        ]);

        foreach (['Audyty dostępności', 'Szkolenia', 'Konsultacje', 'Materiały do pobrania', 'Kontakt z działem'] as $i => $title) {
            Page::updateOrCreate(['slug' => 'przyklad-strona-dzialu-'.\Illuminate\Support\Str::slug($title)], [
                'title' => $title,
                'type' => 'standard',
                'parent_id' => $section->id,
                'is_published' => true,
                'show_in_menu' => false,
                'order' => $i + 1,
                'content' => '<p>Podstrona działu „'.$title.'”.</p>',
            ]);
        }

        // Slider: zdjęcia strony z plików dostępnych lokalnie (bez nich slider się nie pokazuje).
        if ($section->images()->exists()) {
            return;
        }
        $candidates = array_filter([
            storage_path('app/public/1/biuro.jpg'),
            storage_path('app/public/1/hero_0.jpg'),
            storage_path('app/public/1/f1.jpg'),
        ], 'is_file');
        foreach (array_values($candidates) as $i => $file) {
            $image = $section->images()->create(['alt' => 'Zdjęcie przykładowe '.($i + 1).' — zespół fundacji przy pracy', 'caption' => $i === 0 ? 'Przykładowy podpis slajdu' : null, 'order' => $i]);
            $image->addMedia($file)->preservingOriginal()->toMediaCollection('image');
        }
    }

    /** Zapis strony przykładu (pod stroną-rodzicem, poza menu, kolejno). */
    private function page(string $slug, string $type, array $attributes): Page
    {
        return Page::updateOrCreate(['slug' => 'przyklad-'.$slug], $attributes + [
            'type' => $type,
            'parent_id' => $this->parentId,
            'is_published' => true,
            'show_in_menu' => false,
            'order' => ++$this->order,
        ]);
    }

    private function standard(): void
    {
        // Strona zbudowana w edytorze blokowym — pokazuje bloki i od razu otwiera się w nim jako bloki.
        $blocks = BlockRenderer::normalize([
            ['type' => 'paragraph', 'attrs' => ['text' => 'Dostępność cyfrowa to sposób projektowania stron i dokumentów, dzięki któremu korzystają z nich <strong>wszyscy</strong> — także osoby niewidome, głuche czy z niepełnosprawnością intelektualną.', 'lead' => true]],
            ['type' => 'heading', 'attrs' => ['text' => 'Od czego zacząć', 'level' => 2]],
            ['type' => 'list', 'attrs' => ['ordered' => true, 'items' => ['Sprawdź kontrast tekstu (co najmniej 4,5:1).', 'Dodaj teksty alternatywne do obrazów.', 'Upewnij się, że wszystko działa z klawiatury.']]],
            ['type' => 'callout', 'attrs' => ['variant' => 'important'], 'inner' => [
                ['type' => 'paragraph', 'attrs' => ['text' => '<strong>Ważne:</strong> od 2025 roku wymagania dostępności obejmują też wiele usług komercyjnych (Europejski akt o dostępności).']],
            ]],
            ['type' => 'columns', 'attrs' => [], 'inner' => [
                ['type' => 'column', 'inner' => [['type' => 'heading', 'attrs' => ['text' => 'Dla instytucji', 'level' => 3]], ['type' => 'paragraph', 'attrs' => ['text' => 'Audyty WCAG, deklaracje dostępności i szkolenia dla zespołów.']]]],
                ['type' => 'column', 'inner' => [['type' => 'heading', 'attrs' => ['text' => 'Dla organizacji', 'level' => 3]], ['type' => 'paragraph', 'attrs' => ['text' => 'Wsparcie w przygotowaniu dostępnych dokumentów i wydarzeń.']]]],
            ]],
            ['type' => 'details', 'attrs' => ['summary' => 'Czym jest WCAG?'], 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'Międzynarodowe wytyczne dostępności treści internetowych, obecnie w wersji 2.2.']]]],
            ['type' => 'details', 'attrs' => ['summary' => 'Czy audyt jest płatny?'], 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'Dla organizacji pozarządowych prowadzimy audyty po kosztach.']]]],
            ['type' => 'table', 'attrs' => ['caption' => 'Poziomy zgodności WCAG', 'headerRow' => true, 'rows' => [['Poziom', 'Co oznacza'], ['A', 'Podstawowe wymagania'], ['AA', 'Poziom wymagany prawem'], ['AAA', 'Najwyższy poziom']]]],
            ['type' => 'quote', 'attrs' => ['text' => 'Dostępność nie jest dodatkiem. To jakość, z której korzystają wszyscy.', 'cite' => 'Zespół FEER']],
            ['type' => 'buttons', 'attrs' => ['items' => [['label' => 'Zamów audyt dostępności', 'url' => '/kontakt', 'variant' => 'brand']]]],
        ]);

        $this->page('standardowa', 'standard', [
            'title' => 'Przykład: strona standardowa (edytor blokowy)',
            'meta_description' => 'Przykładowa strona zbudowana w edytorze blokowym.',
            'blocks' => $blocks,
            'content' => BlockRenderer::render($blocks),
        ]);
    }

    private function event(): void
    {
        $this->page('wydarzenie', 'event', [
            'title' => 'Przykład: wydarzenie — webinar o dostępnych dokumentach',
            'content' => '<p>Pokażemy krok po kroku, jak przygotować dostępny dokument w Wordzie i PDF. Webinar z napisami na żywo i tłumaczem PJM.</p>',
            'event_mode' => 'online',
            'event_when' => now()->addWeeks(2)->setTime(10, 0),
            'event_location' => 'Online (Zoom)',
            'event_how_to_join' => 'Link do spotkania wyślemy mailem dzień przed webinarem.',
        ]);
    }

    private function schedule(): void
    {
        $this->page('harmonogram', 'schedule', [
            'title' => 'Przykład: harmonogram konsultacji',
            'content' => '<p>Bezpłatne konsultacje z dostępności cyfrowej dla organizacji pozarządowych.</p>',
            'schedule_items' => [
                ['date' => now()->addDays(7)->toDateString(), 'time' => '10:00', 'title' => 'Konsultacje: dostępne strony internetowe'],
                ['date' => now()->addDays(14)->toDateString(), 'time' => '10:00', 'title' => 'Konsultacje: dostępne dokumenty'],
                ['date' => now()->addDays(21)->toDateString(), 'time' => '12:00', 'title' => 'Konsultacje: dostępne wydarzenia'],
            ],
        ]);
    }

    private function about(): void
    {
        $about = $this->page('o-organizacji', 'about', [
            'title' => 'Przykład: o organizacji',
            'about_intro' => 'Fundacja działa na rzecz dostępności cyfrowej i włączania osób z niepełnosprawnościami w życie społeczne i zawodowe.',
            'about_motto' => 'Dostępność dla wszystkich.',
        ]);

        // Typ „O organizacji — osoba": podstrona osoby w zespole.
        Page::updateOrCreate(['slug' => 'przyklad-o-organizacji/osoba/anna-kowalska'], [
            'title' => 'Anna Kowalska',
            'type' => 'about_person',
            'parent_id' => $about->id,
            'is_published' => true,
            'show_in_menu' => false,
            'person_role' => 'Koordynatorka ds. dostępności',
            'person_bio' => 'Audytorka WCAG, prowadzi szkolenia z dostępnych dokumentów.',
            'person_email' => 'anna.kowalska@example.org',
            'order' => 1,
        ]);
    }

    private function faq(): void
    {
        $this->page('faq', 'faq', [
            'title' => 'Przykład: FAQ — audyt dostępności',
            'content' => '<p>Odpowiedzi na najczęstsze pytania o audyt dostępności.</p>',
            'faq_items' => [
                ['question' => 'Ile trwa audyt?', 'answer' => '<p>Zwykle od 2 do 4 tygodni, zależnie od wielkości serwisu.</p>'],
                ['question' => 'Co dostanę po audycie?', 'answer' => '<p>Raport z listą błędów, priorytetami i wskazówkami naprawy.</p>'],
                ['question' => 'Czy pomożecie poprawić błędy?', 'answer' => '<p>Tak — konsultujemy poprawki i sprawdzamy je ponownie.</p>'],
            ],
        ]);
    }

    private function trainingInstitution(): void
    {
        $this->page('instytucja-szkoleniowa', 'training_institution', [
            'title' => 'Przykład: instytucja szkoleniowa',
            'content' => '<p>Dane z Rejestru Instytucji Szkoleniowych i Bazy Usług Rozwojowych.</p>',
            'training_manager_name' => 'Jan Nowak',
            'training_manager_title' => 'Kierownik ds. szkoleń',
            'training_ris_number' => '2.12/00000/2026',
            'training_bur_number' => 'BUR-000000',
        ]);
    }

    private function bipMove(): void
    {
        $this->page('przeniesiono-do-bip', 'bip_move', [
            'title' => 'Przykład: przeniesiono do BIP',
            'bip_move_url' => '/bip',
            'bip_move_note' => 'Sprawozdania i dokumenty statutowe publikujemy w Biuletynie Informacji Publicznej.',
        ]);
    }

    private function internal(): void
    {
        $this->page('strona-wewnetrzna', 'internal', [
            'title' => 'Przykład: strona wewnętrzna (hasło: przyklad123)',
            'access_mode' => 'password',
            'access_password' => Hash::make('przyklad123'),
            'content' => '<p>Treść widoczna dopiero po podaniu hasła.</p>',
        ]);
    }

    private function internalHub(): void
    {
        $this->page('strefa-wewnetrzna', 'internal_hub', [
            'title' => 'Przykład: wewnętrzny panel współpracownika',
            'access_mode' => 'microsoft',
            'hub_intro' => 'Komunikaty i odnośniki dla zespołu po zalogowaniu kontem Microsoft 365.',
            'hub_links' => [
                ['label' => 'Wnioski urlopowe', 'url' => '#', 'icon' => 'fa-solid fa-umbrella-beach', 'description' => 'Formularz w systemie kadrowym'],
                ['label' => 'Szablony dokumentów', 'url' => '#', 'icon' => 'fa-solid fa-file-word', 'description' => 'Dostępne szablony Word i PowerPoint'],
            ],
        ]);
    }

    private function linksHub(): void
    {
        $this->page('kafelki-metro', 'links_hub', [
            'title' => 'Przykład: strona z kafelkami (metro)',
            'hub_intro' => 'Szybki dostęp do najważniejszych działów.',
            'hub_links' => [
                ['label' => 'Szkolenia', 'url' => '/dzialania', 'icon' => 'fa-solid fa-graduation-cap', 'color' => 'blue', 'description' => 'Kursy z dostępności cyfrowej'],
                ['label' => 'Audyty', 'url' => '/kontakt', 'icon' => 'fa-solid fa-magnifying-glass', 'color' => 'blue', 'description' => 'Sprawdzimy Twoją stronę'],
                ['label' => 'Aktualności', 'url' => '/aktualnosci', 'icon' => 'fa-solid fa-newspaper', 'color' => 'blue', 'description' => 'Co u nas słychać'],
            ],
        ]);
    }

    private function tilesGrid(): void
    {
        $this->page('siatka-kafelkow', 'tiles_grid', [
            'title' => 'Przykład: siatka kafelków z treścią',
            'content' => '<p>Kafelki z ikonami i treść pod nimi.</p>',
            'tiles_content_position' => 'below',
            'tiles' => [
                ['label' => 'Dostępne strony', 'url' => '/dzialania', 'icon' => 'fa-solid fa-globe'],
                ['label' => 'Dostępne dokumenty', 'url' => '/dzialania', 'icon' => 'fa-solid fa-file-lines'],
                ['label' => 'Dostępne wydarzenia', 'url' => '/dzialania', 'icon' => 'fa-solid fa-calendar-check'],
            ],
        ]);
    }

    private function cooperation(): void
    {
        $this->page('wspolpraca', 'wspolpraca', [
            'title' => 'Przykład: współpraca z FEER',
            'content' => '<p>Zapraszamy firmy, samorządy i uczelnie do wspólnych projektów na rzecz dostępności.</p>',
            'cooperation_data' => ['form_enabled' => false],
        ]);
    }

    private function legacy(): void
    {
        $this->page('jak-bylo', 'legacy', [
            'title' => 'Przykład: prezentacja tego, co było',
            'legacy_name' => 'Poprzednia strona fundacji',
            'legacy_intro' => 'Archiwalna prezentacja wcześniejszej wersji serwisu.',
        ]);
    }

    private function brandAssets(): void
    {
        $this->page('marka', 'brand_assets', [
            'title' => 'Przykład: marka — identyfikacja wizualna',
            'access_mode' => 'password',
            'content' => '<p>Logotypy, szablony i księga znaku. Dostęp przez indywidualne loginy (panel: Strony → Dostęp).</p>',
        ]);
    }

    private function contact(): void
    {
        $existing = Page::where('type', 'contact')->where('is_published', true)->where('slug', '!=', 'przyklad-kontakt')->exists();

        $this->page('kontakt', 'contact', [
            'title' => 'Przykład: kontakt',
            'is_published' => $existing,
        ]);
    }

    private function service(): void
    {
        $this->page('oferta', 'service', [
            'title' => 'Przykład: oferta — audyt dostępności strony',
            'content' => '<p>Sprawdzamy stronę ręcznie i narzędziami, z udziałem testerów z niepełnosprawnościami.</p>',
            'type_data' => [
                'lead' => 'Dowiedz się, co blokuje użytkowników Twojej strony, i jak to naprawić.',
                'audience' => [['text' => 'Urzędy i instytucje publiczne'], ['text' => 'Organizacje pozarządowe'], ['text' => 'Firmy objęte Europejskim aktem o dostępności']],
                'benefits' => [
                    ['icon' => 'fa-solid fa-list-check', 'title' => 'Raport z priorytetami', 'text' => 'Wiesz, co naprawić najpierw.'],
                    ['icon' => 'fa-solid fa-users', 'title' => 'Testy z użytkownikami', 'text' => 'Sprawdzają osoby, które na co dzień korzystają z czytników ekranu.'],
                ],
                'steps' => [['title' => 'Rozmowa', 'text' => 'Ustalamy zakres audytu.'], ['title' => 'Audyt', 'text' => '2–4 tygodnie.'], ['title' => 'Raport i konsultacje', 'text' => 'Omawiamy wyniki i poprawki.']],
                'cta_label' => 'Zapytaj o wycenę', 'cta_url' => '/kontakt',
                'contact_name' => 'Anna Kowalska', 'contact_email' => 'audyty@example.org',
            ],
        ]);
    }

    private function guide(): void
    {
        $this->page('poradnik', 'guide', [
            'title' => 'Przykład: poradnik — dostępny PDF w 5 krokach',
            'type_data' => [
                'lead' => 'Jak z dokumentu Word zrobić PDF, który odczyta czytnik ekranu.',
                'time_estimate' => '20 minut',
                'level' => 'basic',
                'requirements' => [['text' => 'Microsoft Word 2016 lub nowszy'], ['text' => 'Dokument źródłowy']],
                'steps' => [
                    ['title' => 'Użyj stylów nagłówków', 'text' => 'Śródtytuły oznacz stylami Nagłówek 1, 2, 3 — nie samym pogrubieniem.', 'tip' => 'Okienko Nawigacja pokaże strukturę dokumentu.'],
                    ['title' => 'Dodaj teksty alternatywne', 'text' => 'Kliknij obraz prawym przyciskiem → Edytuj tekst alternatywny.'],
                    ['title' => 'Sprawdź dostępność', 'text' => 'Recenzja → Sprawdź ułatwienia dostępu.'],
                    ['title' => 'Ustaw tytuł i język', 'text' => 'Plik → Informacje → Właściwości.'],
                    ['title' => 'Zapisz jako PDF', 'text' => 'Zapisz jako → PDF, zaznacz „Znaczniki struktury dokumentu”.'],
                ],
                'summary' => 'Taki PDF ma strukturę, opisy obrazów i poprawną kolejność czytania.',
            ],
        ]);
    }

    private function glossary(): void
    {
        $this->page('slownik', 'glossary', [
            'title' => 'Przykład: słownik pojęć dostępności',
            'type_data' => [
                'lead' => 'Najważniejsze pojęcia wyjaśnione prostym językiem.',
                'terms' => [
                    ['term' => 'Czytnik ekranu', 'definition' => 'Program, który odczytuje na głos treść ekranu osobom niewidomym.'],
                    ['term' => 'ETR', 'definition' => 'Tekst łatwy do czytania i rozumienia — dla osób z niepełnosprawnością intelektualną.'],
                    ['term' => 'PJM', 'definition' => 'Polski język migowy — naturalny język osób głuchych.'],
                    ['term' => 'WCAG', 'definition' => 'Wytyczne dostępności treści internetowych.', 'related' => 'Czytnik ekranu'],
                ],
            ],
        ]);
    }

    private function caseStudy(): void
    {
        $this->page('studium-przypadku', 'case_study', [
            'title' => 'Przykład: studium przypadku — dostępna strona gminy',
            'type_data' => [
                'lead' => 'Jak w trzy miesiące gmina poprawiła zgodność strony z WCAG z 40% do 95%.',
                'client' => 'Gmina (przykład)', 'sector' => 'Samorząd', 'period' => '2026',
                'challenge' => 'Strona nie działała z klawiatury, a dokumenty były skanami.',
                'solution' => 'Audyt, szkolenie redaktorów i nowy szablon dokumentów.',
                'results' => [['value' => '95%', 'label' => 'zgodności z WCAG 2.1 AA'], ['value' => '12', 'label' => 'przeszkolonych redaktorów']],
                'quote' => 'Pierwszy raz nasi mieszkańcy z niepełnosprawnościami mogą wszystko załatwić sami.',
                'quote_author' => 'Sekretarz gminy',
                'cta_label' => 'Porozmawiajmy o Twojej stronie', 'cta_url' => '/kontakt',
            ],
        ]);
    }

    private function pricing(): void
    {
        $this->page('cennik', 'pricing', [
            'title' => 'Przykład: cennik usług',
            'type_data' => [
                'lead' => 'Ceny orientacyjne dla organizacji pozarządowych.',
                'price_rows' => [
                    ['group' => 'Audyty', 'item' => 'Audyt strony do 20 podstron', 'price' => 'od 2 500 zł', 'note' => 'z raportem'],
                    ['group' => 'Audyty', 'item' => 'Audyt dokumentu PDF', 'price' => '150 zł', 'note' => 'do 20 stron'],
                    ['group' => 'Szkolenia', 'item' => 'Szkolenie dla zespołu (4 h)', 'price' => '1 800 zł', 'note' => 'do 15 osób'],
                ],
                'payment_info' => 'Faktura z 14-dniowym terminem płatności.',
                'cta_label' => 'Zapytaj o ofertę', 'cta_url' => '/kontakt',
            ],
        ]);
    }

    private function team(): void
    {
        $this->page('zespol', 'team', [
            'title' => 'Przykład: zespół',
            'type_data' => [
                'lead' => 'Osoby, które prowadzą audyty i szkolenia.',
                'members' => [
                    ['name' => 'Anna Kowalska', 'role' => 'Koordynatorka ds. dostępności', 'text' => 'Audytorka WCAG.', 'email' => 'anna@example.org'],
                    ['name' => 'Jan Nowak', 'role' => 'Trener', 'text' => 'Szkoli z dostępnych dokumentów.'],
                ],
            ],
        ]);
    }

    private function documents(): void
    {
        $this->page('dokumenty', 'documents', [
            'title' => 'Przykład: dokumenty do pobrania',
            'type_data' => [
                'lead' => 'Wzory i materiały w wersjach dostępnych.',
                'docs' => [
                    ['group' => 'Wzory', 'title' => 'Wzór deklaracji dostępności', 'url' => '/storage/przyklad/deklaracja.docx', 'note' => 'DOCX'],
                    ['group' => 'Wzory', 'title' => 'Lista kontrolna dostępnego dokumentu', 'url' => '/storage/przyklad/lista.pdf', 'note' => 'PDF dostępny'],
                    ['group' => 'Odnośniki', 'title' => 'Wytyczne WCAG 2.2 (W3C)', 'url' => 'https://www.w3.org/TR/WCAG22/'],
                ],
            ],
        ]);
    }

    private function regulation(): void
    {
        $this->page('regulamin', 'regulation', [
            'title' => 'Przykład: regulamin szkoleń',
            'content' => '<h2>§ 1. Postanowienia ogólne</h2><p>Regulamin określa zasady udziału w szkoleniach.</p><h2>§ 2. Zgłoszenia</h2><p>Zgłoszenia przyjmujemy przez formularz.</p><h2>§ 3. Dostępność</h2><p>Na życzenie zapewniamy tłumacza PJM i materiały w ETR.</p>',
            'type_data' => ['lead' => 'Zasady udziału w szkoleniach fundacji.', 'valid_from' => now()->startOfYear()->toDateString(), 'version' => '2.0'],
        ]);
    }

    private function redirect(): void
    {
        $this->page('przekierowanie', 'redirect', [
            'title' => 'Przykład: przekierowanie',
            'type_data' => ['target_url' => '/aktualnosci', 'redirect_mode' => 'notice', 'redirect_message' => 'Ta treść jest teraz w Aktualnościach.', 'redirect_label' => 'Przejdź do aktualności'],
        ]);
    }

    private function reports(): void
    {
        $this->page('sprawozdania', 'reports', [
            'title' => 'Przykład: sprawozdania roczne',
            'content' => '<p>Tabela sprawozdań z modułu Sprawozdania.</p>',
            'type_data' => ['lead' => 'Sprawozdania merytoryczne i finansowe.'],
        ]);
    }

    private function partners(): void
    {
        $this->page('partnerzy', 'partners', [
            'title' => 'Przykład: partnerzy i sponsorzy',
            'type_data' => [
                'lead' => 'Dziękujemy za wsparcie naszych działań.',
                'partners_layout' => 'cards',
                'partner_groups' => [['title' => 'Partnerzy merytoryczni'], ['title' => 'Sponsorzy']],
                'partners' => [
                    ['group' => 'Partnerzy merytoryczni', 'name' => 'Uczelnia (przykład)', 'note' => 'Badania nad dostępnością'],
                    ['group' => 'Sponsorzy', 'name' => 'Firma (przykład)', 'note' => 'Sponsor szkoleń'],
                ],
                'partners_cta_label' => 'Zostań partnerem', 'partners_cta_url' => '/kontakt',
            ],
        ]);
    }

    private function recruitment(): void
    {
        $this->page('rekrutacja', 'recruitment', [
            'title' => 'Przykład: rekrutacja — Praca bez barier',
            'content' => '<p>Projekt pomaga osobom z niepełnosprawnością wejść na rynek pracy: doradztwo, szkolenia i płatny staż.</p>',
            'type_data' => [
                'lead' => 'Szukamy 40 osób z niepełnosprawnością, które chcą znaleźć pracę. Udział jest bezpłatny.',
                'rec_starts_at' => now()->subDays(10)->toDateString(),
                'rec_ends_at' => now()->addDays(10)->format('Y-m-d').'T16:00',
                'rec_places' => '40 osób', 'rec_period' => 'styczeń – czerwiec 2027', 'rec_mode' => 'stacjonarnie i online', 'rec_cost' => 'Bezpłatnie',
                'audience' => [['text' => 'Osoby z orzeczeniem o niepełnosprawności'], ['text' => 'Osoby bez pracy w wieku 18–64 lata']],
                'requirements' => [['text' => 'Orzeczenie o niepełnosprawności'], ['text' => 'Pozostawanie bez zatrudnienia']],
                'bonus' => [['text' => 'Zamieszkanie na obszarze wiejskim', 'points' => '+5 pkt'], ['text' => 'Znaczny stopień niepełnosprawności', 'points' => '+3 pkt']],
                'benefits' => [['icon' => 'fa-solid fa-comments', 'title' => 'Doradztwo zawodowe', 'text' => 'Indywidualny plan działania'], ['icon' => 'fa-solid fa-briefcase', 'title' => 'Płatny staż', 'text' => '3 miesiące'], ],
                'steps' => [['title' => 'Wypełnij formularz', 'text' => 'Online, mailem albo w biurze.'], ['title' => 'Rozmowa', 'text' => 'Zadzwonimy w ciągu 5 dni roboczych.']],
                'docs' => [['title' => 'Regulamin rekrutacji', 'url' => '/storage/przyklad/regulamin-rekrutacji.pdf', 'note' => 'PDF dostępny']],
                'rec_accessibility' => 'Zgłoszenie przyjmiemy też telefonicznie i w PJM. Pomożemy wypełnić dokumenty.',
                'rec_faq' => [['q' => 'Czy udział jest płatny?', 'a' => 'Nie, udział jest bezpłatny.']],
                'cta_label' => 'Zgłoś się do projektu', 'cta_url' => '/kontakt',
                'contact_name' => 'Anna Kowalska', 'contact_email' => 'rekrutacja@example.org', 'contact_phone' => '500 000 000',
            ],
        ]);
    }

    private function tender(): void
    {
        $this->page('zapytanie-ofertowe', 'tender', [
            'title' => 'Przykład: zapytanie ofertowe — szkolenia z dostępności',
            'content' => '<p>Przedmiotem zamówienia jest przeprowadzenie 4 szkoleń (po 8 godzin) z dostępności cyfrowej dla 40 uczestników projektu.</p>',
            'type_data' => [
                'lead' => 'Szukamy wykonawcy szkoleń z dostępności cyfrowej.',
                'tender_number' => 'ZO/1/2026',
                'tender_published_at' => now()->subDays(5)->toDateString(),
                'tender_deadline' => now()->addDays(9)->format('Y-m-d').'T10:00',
                'tender_cpv' => '80500000-9 Usługi szkoleniowe', 'tender_place' => 'Nowy Sącz', 'tender_period' => 'do 31 marca 2027',
                'tender_bk_url' => 'https://bazakonkurencyjnosci.funduszeeuropejskie.gov.pl/',
                'requirements' => [['text' => 'Co najmniej 2 szkolenia z dostępności w ostatnich 3 latach']],
                'criteria' => [['name' => 'Cena', 'weight' => '60%', 'text' => 'najniższa cena / cena oferty × 60 pkt'], ['name' => 'Doświadczenie trenera', 'weight' => '40%', 'text' => '10 pkt za każde szkolenie ponad wymagane']],
                'tender_submission' => 'Ofertę składa się przez Bazę Konkurencyjności. Oferty złożone po terminie nie będą rozpatrywane.',
                'tender_exclusions' => 'Z udziału wykluczone są podmioty powiązane z Zamawiającym osobowo lub kapitałowo.',
                'docs' => [['title' => 'Zapytanie ofertowe nr 1/2026', 'url' => '/storage/przyklad/zapytanie.pdf', 'note' => 'PDF dostępny'], ['title' => 'Formularz oferty', 'url' => '/storage/przyklad/formularz-oferty.docx']],
                'notices' => [['date' => now()->subDays(2)->toDateString(), 'title' => 'Wyjaśnienie nr 1', 'text' => 'Szkolenia odbywają się stacjonarnie.']],
                'contact_name' => 'Jan Nowak', 'contact_email' => 'zamowienia@example.org',
            ],
        ]);
    }
}
