<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Zakłada dział „Dostępność" jako jedną rozwijaną grupę podstron w menu
 * (strona-rodzic + podstrony potomne), wzorowany na strukturze
 * https://wupkrakow.praca.gov.pl/dostepnosc.
 *
 * Strona-rodzic „Dostępność" ma show_in_menu = true, więc po opublikowaniu
 * pojawia się w menu głównym jako rozwijane menu z podstronami (mechanizm
 * partials/nav-page-dropdown — top-level strona z opublikowanymi dziećmi).
 *
 * Każda strona dostaje też wersję ETR (tekst łatwy do czytania) — na froncie
 * pojawia się przełącznik „Włącz wersję ETR" (partials/etr-toggle).
 *
 * Dane organizacji (nazwa, adres, e-maile) są wpisane na stałe zgodnie z
 * ustawieniami FEER. NIEUZUPEŁNIONE pozostają dane, których nie wolno zmyślać:
 * numer telefonu, imię i nazwisko koordynatora oraz WSZYSTKIE fakty o budynku
 * (winda, podjazd, parking, toaleta), tłumaczu PJM i pętli indukcyjnej — to
 * oświadczenia o dostępności, które muszą być prawdziwe. Uzupełnij pola [ ... ].
 *
 * Strony powstają jako SZKICE (is_published = false). Po wypełnieniu pozostałych
 * pól opublikuj je w panelu — dopiero wtedy pojawią się w menu, w bocznej
 * nawigacji działu i w mapie strony.
 *
 * Dostępność cyfrowa nie jest duplikowana — podstrona odsyła do istniejącej
 * Deklaracji dostępności (/deklaracja-dostepnosci) wraz z formularzem zgłaszania
 * barier.
 *
 * Uruchomienie:  php artisan db:seed --class=AccessibilitySubpagesSeeder
 * (na serwerze:  php85 artisan db:seed --class=AccessibilitySubpagesSeeder --force)
 *
 * Gdy dział już istnieje, seeder jest pomijany. Dodaj --force, aby go NADPISAĆ:
 * istniejące strony, ich wersje ETR i pozycja menu zostaną usunięte, a dział
 * wygenerowany od nowa (uwaga: kasuje ręczne edycje). Na produkcji --force jest
 * i tak wymagane przez db:seed, więc każde uruchomienie tam nadpisuje dział.
 */
class AccessibilitySubpagesSeeder extends Seeder
{
    private const PARENT_SLUG = 'dostepnosc';

    /** Dane organizacji FEER (za ustawieniami serwisu). */
    private const ORG = 'Fundacja FEER';
    private const ADDR = 'ul. Barbackiego 28, 33-300 Nowy Sącz';
    private const EMAIL = 'kontakt@feer.org.pl';
    private const ACCESS_EMAIL = 'dostepnosc@feer.org.pl';

    public function run(): void
    {
        $exists = Page::withTrashed()->where('slug', self::PARENT_SLUG)->exists();
        $force = (bool) $this->command->option('force');

        if ($exists && ! $force) {
            $this->command->warn('Podstrona o slugu "'.self::PARENT_SLUG.'" już istnieje — seeder pominięty.');
            $this->command->warn('Dodaj --force, aby usunąć istniejący dział i wygenerować go od nowa (UWAGA: kasuje też ręczne edycje tych stron).');

            return;
        }

        if ($exists && $force) {
            $this->purgeExisting();
            $this->command->warn('--force: usunięto istniejący dział „Dostępność" (strony, wersje ETR i pozycję menu). Generuję od nowa.');
        }

        // Strona-rodzic „Dostępność" — nagłówek rozwijanego menu (show_in_menu).
        $parent = Page::create([
            'title'            => 'Dostępność',
            'slug'             => self::PARENT_SLUG,
            'type'             => 'standard',
            'content'          => $this->parentContent(),
            'tiles'            => $this->parentTiles(),
            'is_published'     => false,
            'show_in_menu'     => true, // nagłówek rozwijanej grupy podstron w menu głównym
            'show_side_nav'    => true,
            'order'            => 0,
            'meta_title'       => 'Dostępność — informacje dla osób ze szczególnymi potrzebami',
            'meta_description' => 'Dostępność architektoniczna, informacyjno-komunikacyjna i cyfrowa. Wniosek o zapewnienie dostępności, procedura odwoławcza i kontakt do koordynatora ds. dostępności.',
        ]);

        $this->attachEtr($parent, $this->parentEtr());

        $children = [
            [
                'title'   => 'Dostępność architektoniczna',
                'slug'    => 'dostepnosc-architektoniczna',
                'content' => $this->architecturalContent(),
                'meta'    => 'Opis dostępności architektonicznej siedziby FEER: dojście, parking, wejście, komunikacja wewnątrz, toalety, pies asystujący.',
                'etr'     => $this->architecturalEtr(),
            ],
            [
                'title'   => 'Dostępność informacyjno-komunikacyjna',
                'slug'    => 'dostepnosc-komunikacyjna',
                'content' => $this->communicationContent(),
                'meta'    => 'Tłumacz PJM, pętla indukcyjna, tekst łatwy do czytania (ETR), dostępne formaty dokumentów i możliwe formy kontaktu.',
                'etr'     => $this->communicationEtr(),
            ],
            [
                'title'   => 'Dostępność cyfrowa',
                'slug'    => 'dostepnosc-cyfrowa',
                'content' => $this->digitalContent(),
                'meta'    => 'Deklaracja dostępności cyfrowej zgodna z WCAG 2.1 AA oraz sposób zgłaszania problemów technicznych.',
                'etr'     => $this->digitalEtr(),
            ],
            [
                'title'   => 'Wniosek o zapewnienie dostępności i procedura odwoławcza',
                'slug'    => 'wniosek-o-dostepnosc',
                'content' => $this->requestContent(),
                'meta'    => 'Jak złożyć wniosek o zapewnienie dostępności, ustawowe terminy, dostęp alternatywny i prawo do skargi do Prezesa PFRON.',
                'etr'     => $this->requestEtr(),
            ],
            [
                'title'   => 'Koordynator do spraw dostępności',
                'slug'    => 'koordynator-dostepnosci',
                'content' => $this->coordinatorContent(),
                'meta'    => 'Dane kontaktowe koordynatora do spraw dostępności i zakres jego zadań.',
                'etr'     => $this->coordinatorEtr(),
            ],
        ];

        foreach ($children as $i => $child) {
            $page = Page::create([
                'title'            => $child['title'],
                'slug'             => $child['slug'],
                'type'             => 'standard',
                'parent_id'        => $parent->id,
                'content'          => $child['content'],
                'is_published'     => false,
                'show_in_menu'     => false, // pozycja rozwijanego menu bierze się z relacji rodzic→dzieci
                'show_side_nav'    => true,
                'order'            => $i + 1,
                'meta_title'       => $child['title'],
                'meta_description' => $child['meta'],
            ]);

            $this->attachEtr($page, $child['etr']);
        }

        // Pozycja menu głównego „Dostępność" → /dostepnosc. Link do opublikowanej
        // strony z podstronami automatycznie staje się rozwijanym menu
        // (NavItem::linkedPage + partials/nav-link-dropdown). Tworzymy ją jako
        // NIEAKTYWNĄ (is_active = false), bo strony są szkicami — aktywuj ją, gdy
        // opublikujesz dział, aby menu nie prowadziło do ukrytych stron.
        if (! NavItem::where('location', 'main')->where('url', '/'.self::PARENT_SLUG)->exists()) {
            NavItem::create([
                'label'     => 'Dostępność',
                'type'      => 'link',
                'url'       => '/'.self::PARENT_SLUG,
                'location'  => 'main',
                'is_active' => false,
                'order'     => 65, // między „Kontakt" (60) a „Wesprzyj" (70)
            ]);
        }

        $this->command->info('Utworzono rozwijaną grupę „Dostępność": 1 strona-rodzic + '.count($children).' podstrony (jako szkice), każda z wersją ETR.');
        $this->command->info('Wpisano dane FEER (nazwa, adres, e-maile). Nieuzupełnione: telefon, koordynator i fakty o budynku/PJM/pętli — pola [ ... ].');
        $this->command->info('Dodano pozycję menu „Dostępność" (nieaktywną) → /'.self::PARENT_SLUG.'.');
        $this->command->warn('Uzupełnij pozostałe pola [w nawiasach], opublikuj strony (Strony → Dostępność) i aktywuj pozycję menu „Dostępność".');
    }

    /**
     * Usuwa istniejący dział „Dostępność" (dla trybu --force): stronę-rodzica,
     * jej podstrony, ich wersje ETR oraz pozycję menu. Obejmuje też rekordy
     * miękko usunięte (withTrashed) i twardo kasuje (forceDelete), aby slug
     * został zwolniony i można było utworzyć dział od nowa.
     */
    private function purgeExisting(): void
    {
        $parentId = Page::withTrashed()->where('slug', self::PARENT_SLUG)->value('id');

        $pages = Page::withTrashed()
            ->where('id', $parentId)
            ->orWhere('parent_id', $parentId)
            ->get();

        $ids = $pages->pluck('id')->all();

        if (! empty($ids)) {
            \App\Models\EtrContent::where('etrable_type', Page::class)
                ->whereIn('etrable_id', $ids)
                ->delete();

            Page::withTrashed()->whereIn('id', $ids)->forceDelete();
        }

        NavItem::where('location', 'main')->where('url', '/'.self::PARENT_SLUG)->delete();
    }

    /**
     * Dołącza włączoną wersję ETR (tekst łatwy do czytania) do strony.
     *
     * @param  array{title:string,summary:string,content:string}  $etr
     */
    private function attachEtr(Page $page, array $etr): void
    {
        $page->etr()->create([
            'is_enabled'  => true,
            'etr_title'   => $etr['title'],
            'etr_summary' => $etr['summary'],
            'etr_content' => $etr['content'],
        ]);
    }

    // ------------------------------------------------------------------
    // Kafelki strony-rodzica (siatka linków do podstron + Deklaracji).
    // Renderują się nad treścią huba (partials/_tiles-grid). Ikony to klasy
    // Bootstrap Icons (partial dokleja prefiks „bi").
    // ------------------------------------------------------------------

    /** @return list<array{label:string,url:string,icon:string}> */
    private function parentTiles(): array
    {
        return [
            ['label' => 'Dostępność architektoniczna', 'url' => '/dostepnosc-architektoniczna', 'icon' => 'bi-building'],
            ['label' => 'Dostępność informacyjno-komunikacyjna', 'url' => '/dostepnosc-komunikacyjna', 'icon' => 'bi-chat-dots'],
            ['label' => 'Dostępność cyfrowa', 'url' => '/dostepnosc-cyfrowa', 'icon' => 'bi-laptop'],
            ['label' => 'Wniosek o zapewnienie dostępności', 'url' => '/wniosek-o-dostepnosc', 'icon' => 'bi-file-earmark-text'],
            ['label' => 'Koordynator ds. dostępności', 'url' => '/koordynator-dostepnosci', 'icon' => 'bi-person-lines-fill'],
            ['label' => 'Deklaracja dostępności', 'url' => '/deklaracja-dostepnosci', 'icon' => 'bi-universal-access'],
        ];
    }

    // ------------------------------------------------------------------
    // Treść pełna (HTML)
    // ------------------------------------------------------------------

    private function parentContent(): string
    {
        $org = self::ORG;
        $addr = self::ADDR;
        $email = self::EMAIL;

        return <<<HTML
<p><strong>{$org}</strong> dokłada wszelkich starań, aby nasza siedziba, strona internetowa oraz sposób obsługi były dostępne dla każdej osoby — niezależnie od jej sprawności, wieku czy sposobu komunikowania się. Chcemy, aby każdy mógł samodzielnie i na równych zasadach korzystać z naszych usług.</p>
<p>Jeśli napotkasz barierę w kontakcie z nami — poinformuj nas. Wspólnie znajdziemy rozwiązanie. Wybierz temat z kafelków powyżej.</p>

<h2>Szybki kontakt</h2>
<ul>
<li><strong>Telefon:</strong> [numer telefonu]</li>
<li><strong>E-mail:</strong> <a href="mailto:{$email}">{$email}</a></li>
<li><strong>Adres:</strong> {$addr}</li>
</ul>
HTML;
    }

    private function architecturalContent(): string
    {
        $addr = self::ADDR;

        return <<<HTML
<p>Poniżej opisujemy, jak przygotowana jest nasza siedziba na przyjęcie osób ze szczególnymi potrzebami.</p>

<h2>Budynek: {$addr}</h2>

<h3>Otoczenie i dojście</h3>
<ul>
<li>Do budynku prowadzi [chodnik / utwardzona droga] o szerokości umożliwiającej przejazd wózkiem.</li>
<li>Najbliższy przystanek [autobusowy / tramwajowy] znajduje się w odległości około [liczba] metrów.</li>
<li>[Opisz oznaczenia dotykowe lub kontrastowe, jeśli występują.]</li>
</ul>

<h3>Miejsca parkingowe</h3>
<ul>
<li>[Przed budynkiem znajduje się [liczba] wyznaczone miejsce parkingowe dla osób z niepełnosprawnością, oznaczone kopertą i znakiem.] — LUB — [Przy budynku nie ma wydzielonego miejsca parkingowego dla osób z niepełnosprawnością.]</li>
</ul>

<h3>Wejście do budynku</h3>
<ul>
<li>Wejście główne znajduje się od strony [ulicy / opis].</li>
<li>Do wejścia prowadzi [podjazd / pochylnia o nachyleniu zgodnym z przepisami] — LUB — [tylko schody z poręczą].</li>
<li>Drzwi wejściowe są [szerokie na … cm / otwierane automatycznie / wymagają pomocy — opisz].</li>
<li>[Informacja o dzwonku przywoławczym lub domofonie przy wejściu, jeśli jest.]</li>
</ul>

<h3>Komunikacja wewnątrz budynku</h3>
<ul>
<li>Obsługa osób ze szczególnymi potrzebami odbywa się na [parterze / piętrze].</li>
<li>[W budynku znajduje się winda: rozmiary, oznaczenia w alfabecie Braille'a, komunikaty głosowe] — LUB — [W budynku nie ma windy.]</li>
<li>Korytarze mają szerokość [umożliwiającą / nieumożliwiającą] minięcie się dwóch wózków.</li>
<li>[Informacja o oznaczeniach kierunkowych, kontrastowych, piktogramach.]</li>
</ul>

<h3>Toaleta</h3>
<ul>
<li>[Na poziomie / piętrze znajduje się toaleta dostosowana do potrzeb osób z niepełnosprawnością: uchwyty, przestrzeń manewrowa, przycisk przywoławczy] — LUB — [Budynek nie posiada dostosowanej toalety.]</li>
</ul>

<h3>Pies asystujący</h3>
<ul>
<li>Do budynku można wejść z psem asystującym oraz psem przewodnikiem.</li>
</ul>
HTML;
    }

    private function communicationContent(): string
    {
        $org = self::ORG;
        $addr = self::ADDR;
        $email = self::EMAIL;

        return <<<HTML
<p>Oferujemy różne sposoby kontaktu, abyś mógł/mogła porozumieć się z nami w wygodnej dla siebie formie.</p>

<h2>Kontakt</h2>
<p>Możesz skontaktować się z nami:</p>
<ul>
<li><strong>telefonicznie:</strong> [numer telefonu],</li>
<li><strong>e-mailem:</strong> <a href="mailto:{$email}">{$email}</a>,</li>
<li><strong>listownie:</strong> {$org}, {$addr},</li>
<li><strong>osobiście</strong> w siedzibie: {$addr}.</li>
</ul>

<h2>Tłumacz polskiego języka migowego (PJM)</h2>
<ul>
<li>Osoby głuche lub słabosłyszące mogą skorzystać z pomocy tłumacza polskiego języka migowego (PJM).</li>
<li><strong>Tłumacz online (na miejscu, przez wideopołączenie):</strong> [dostępny zawsze w godzinach pracy / dostępny po wcześniejszym zgłoszeniu / niedostępny].</li>
<li><strong>Tłumacz stacjonarnie:</strong> chęć skorzystania zgłoś co najmniej [liczba] dni roboczych wcześniej, na adres <a href="mailto:{$email}">{$email}</a> lub telefonicznie [numer telefonu].</li>
<li>Możesz również skorzystać z pomocy osoby przybranej — dowolnej osoby pełnoletniej, którą sam/sama wybierzesz do pomocy w załatwieniu sprawy.</li>
</ul>

<h2>Pętla indukcyjna</h2>
<ul>
<li>[W miejscu: punkt obsługi / sala — dostępna jest pętla indukcyjna ułatwiająca kontakt osobom korzystającym z aparatów słuchowych.] — LUB — [Nie dysponujemy pętlą indukcyjną.]</li>
</ul>

<h2>Informacja w tekście łatwym do czytania (ETR)</h2>
<ul>
<li>Najważniejsze informacje w tym dziale przygotowaliśmy w tekście łatwym do czytania i zrozumienia (ETR). Włączysz je przyciskiem „Włącz wersję ETR" na górze każdej strony działu.</li>
</ul>

<h2>Dokumenty w dostępnych formatach</h2>
<ul>
<li>Na Twoją prośbę udostępnimy informacje w formie, która będzie dla Ciebie dostępna — np. jako dokument odczytywalny maszynowo, wydruk powiększony lub odczyt treści przez pracownika.</li>
</ul>
HTML;
    }

    private function digitalContent(): string
    {
        $org = self::ORG;
        $accessEmail = self::ACCESS_EMAIL;

        return <<<HTML
<h2>Deklaracja zgodności</h2>
<p><strong>{$org}</strong> zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z przepisami ustawy z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.</p>
<p>Nasz serwis dąży do zgodności ze standardem <strong>WCAG 2.1 na poziomie AA</strong>. Obecnie serwis jest <strong>częściowo zgodny</strong> — trwają prace nad uzupełnieniem brakujących elementów. Pełną, aktualną informację o poziomie zgodności, dacie publikacji i przeglądu oraz o ewentualnych wyłączeniach znajdziesz w oficjalnej deklaracji dostępności.</p>

<p><a href="/deklaracja-dostepnosci"><strong>Przejdź do Deklaracji dostępności</strong></a></p>

<h2>Zgłaszanie problemów z dostępnością cyfrową</h2>
<p>Jeśli napotkasz stronę, dokument lub funkcję, która jest dla Ciebie niedostępna, skorzystaj z <a href="/deklaracja-dostepnosci">formularza zgłaszania barier</a> dostępnego w Deklaracji dostępności lub napisz na adres <a href="mailto:{$accessEmail}">{$accessEmail}</a>.</p>
<p>W zgłoszeniu podaj:</p>
<ul>
<li>adres strony, na której wystąpił problem,</li>
<li>opis, na czym polega trudność,</li>
<li>swoje dane kontaktowe.</li>
</ul>
<p>Zgodnie z ustawą odpowiemy bez zbędnej zwłoki, najpóźniej w ciągu 7 dni od otrzymania zgłoszenia.</p>
HTML;
    }

    private function requestContent(): string
    {
        return <<<'HTML'
<p>Zgodnie z art. 29–32 ustawy z dnia 19 lipca 2019 r. o zapewnianiu dostępności osobom ze szczególnymi potrzebami, każda osoba ze szczególnymi potrzebami (lub jej przedstawiciel ustawowy) ma prawo wystąpić do nas z żądaniem zapewnienia dostępności.</p>

<h2>Kto może złożyć wniosek</h2>
<ul>
<li>Osoba ze szczególnymi potrzebami lub jej przedstawiciel ustawowy — po wykazaniu interesu faktycznego.</li>
</ul>

<h2>Co powinien zawierać wniosek</h2>
<ul>
<li>dane kontaktowe osoby zgłaszającej,</li>
<li>wskazanie bariery utrudniającej lub uniemożliwiającej dostępność (architektonicznej lub informacyjno-komunikacyjnej),</li>
<li>wskazanie sposobu kontaktu,</li>
<li>w przypadku dostępu alternatywnego — wskazanie preferowanego sposobu zapewnienia informacji.</li>
</ul>

<h2>Sposób i terminy rozpatrzenia</h2>
<ul>
<li>Zapewniamy dostępność bez zbędnej zwłoki, <strong>nie później niż w terminie 14 dni</strong> od dnia złożenia wniosku.</li>
<li>Jeżeli dotrzymanie tego terminu nie jest możliwe, poinformujemy Cię o przyczynach opóźnienia oraz o nowym terminie — <strong>nie dłuższym niż 2 miesiące</strong> od dnia złożenia wniosku.</li>
<li>Jeżeli zapewnienie dostępności w zakresie określonym we wniosku nie jest możliwe, zaproponujemy <strong>dostęp alternatywny</strong> (art. 7 ustawy) — np. wsparcie pracownika, kontakt telefoniczny lub inną możliwą formę.</li>
</ul>

<h2>Skarga na brak dostępności</h2>
<ul>
<li>Gdy odmówimy zapewnienia dostępności albo w Twojej ocenie zaproponowany dostęp alternatywny jest niewystarczający, możesz <strong>złożyć skargę do Prezesa Zarządu Państwowego Funduszu Rehabilitacji Osób Niepełnosprawnych (PFRON)</strong>.</li>
<li>Do skargi stosuje się przepisy Kodeksu postępowania administracyjnego.</li>
</ul>

<p><strong>Adres do skargi:</strong><br>
Państwowy Fundusz Rehabilitacji Osób Niepełnosprawnych<br>
al. Jana Pawła II 13, 00-828 Warszawa</p>
HTML;
    }

    private function coordinatorContent(): string
    {
        $org = self::ORG;
        $addr = self::ADDR;
        $accessEmail = self::ACCESS_EMAIL;

        return <<<HTML
<p>W <strong>{$org}</strong> wyznaczyliśmy osobę odpowiedzialną za koordynację działań na rzecz dostępności.</p>

<ul>
<li><strong>Imię i nazwisko:</strong> [imię i nazwisko koordynatora]</li>
<li><strong>Stanowisko:</strong> Koordynator ds. dostępności</li>
<li><strong>E-mail:</strong> <a href="mailto:{$accessEmail}">{$accessEmail}</a></li>
<li><strong>Telefon:</strong> [numer telefonu]</li>
<li><strong>Adres do korespondencji:</strong> {$org}, {$addr}</li>
</ul>

<p>Do zadań koordynatora należy m.in. wsparcie osób ze szczególnymi potrzebami w dostępie do naszych usług, przygotowanie planu działania na rzecz poprawy dostępności oraz monitorowanie dostępności naszej instytucji.</p>
HTML;
    }

    // ------------------------------------------------------------------
    // Wersje ETR (tekst łatwy do czytania) — krótkie zdania, prosty język.
    // etr_summary = wprowadzenie w ramce; etr_content = akapity (pusta linia
    // rozdziela akapity, każdy akapit to osobna myśl).
    // ------------------------------------------------------------------

    /** @return array{title:string,summary:string,content:string} */
    private function parentEtr(): array
    {
        $email = self::EMAIL;

        return [
            'title'   => 'Dostępność',
            'summary' => 'Ta strona jest napisana w prosty sposób. Mówi o tym, jak do nas trafić i jak się z nami kontaktować.',
            'content' => <<<TXT
Chcemy, aby każdy mógł z nas korzystać. Nie ważne, czy masz niepełnosprawność, czy nie.

W tym dziale są takie strony:

Dostępność architektoniczna – jak wygląda nasz budynek.

Dostępność informacyjno-komunikacyjna – jak możesz się z nami porozumieć.

Dostępność cyfrowa – nasza strona internetowa.

Wniosek o dostępność – co zrobić, gdy coś jest dla Ciebie trudne.

Koordynator do spraw dostępności – osoba, która Ci pomoże.

Potrzebujesz pomocy? Zadzwoń: [numer telefonu].

Możesz też napisać e-mail: {$email}.
TXT,
        ];
    }

    /** @return array{title:string,summary:string,content:string} */
    private function architecturalEtr(): array
    {
        $addr = self::ADDR;

        return [
            'title'   => 'Dostępność architektoniczna',
            'summary' => 'Tu piszemy, jak wygląda nasz budynek i jak do niego wejść.',
            'content' => <<<TXT
Nasz budynek jest pod adresem: {$addr}.

Przed budynkiem [jest / nie ma] miejsca do parkowania dla osoby z niepełnosprawnością.

Do wejścia można dojść [podjazdem / tylko schodami z poręczą].

[W budynku jest winda.] — albo — [W budynku nie ma windy.]

[W budynku jest toaleta dla osoby z niepełnosprawnością.] — albo — [W budynku nie ma takiej toalety.]

Możesz wejść z psem asystującym.

Potrzebujesz pomocy? Zadzwoń: [numer telefonu]. Pomożemy Ci.
TXT,
        ];
    }

    /** @return array{title:string,summary:string,content:string} */
    private function communicationEtr(): array
    {
        $addr = self::ADDR;
        $email = self::EMAIL;

        return [
            'title'   => 'Jak się z nami porozumieć',
            'summary' => 'Tu piszemy, jak możesz się z nami skontaktować.',
            'content' => <<<TXT
Możesz do nas zadzwonić: [numer telefonu].

Możesz napisać e-mail: {$email}.

Możesz przyjść do nas osobiście: {$addr}.

Jesteś osobą głuchą lub słabo słyszącą? Możesz skorzystać z tłumacza języka migowego (PJM).

Chcesz tłumacza na miejscu? Powiedz nam o tym wcześniej, co najmniej [liczba] dni przed wizytą.

Możesz przyjść z osobą, która Ci pomoże. To może być ktoś dorosły, komu ufasz.

Możemy przygotować dla Ciebie informacje w łatwej formie.
TXT,
        ];
    }

    /** @return array{title:string,summary:string,content:string} */
    private function digitalEtr(): array
    {
        $accessEmail = self::ACCESS_EMAIL;

        return [
            'title'   => 'Dostępność cyfrowa',
            'summary' => 'Tu piszemy o naszej stronie internetowej.',
            'content' => <<<TXT
Staramy się, aby nasza strona była łatwa w obsłudze dla każdego.

Nasza strona spełnia zasady dostępności. Te zasady to WCAG.

Coś na stronie jest dla Ciebie trudne albo nie działa? Napisz do nas.

Napisz na e-mail: {$accessEmail}.

Możesz też wypełnić formularz na stronie „Deklaracja dostępności".

Odpowiemy Ci najszybciej, jak możemy. Najpóźniej w ciągu 7 dni.
TXT,
        ];
    }

    /** @return array{title:string,summary:string,content:string} */
    private function requestEtr(): array
    {
        return [
            'title'   => 'Wniosek o dostępność',
            'summary' => 'Tu piszemy, co zrobić, gdy coś jest dla Ciebie trudne albo niedostępne.',
            'content' => <<<'TXT'
Masz prawo poprosić nas o pomoc, gdy coś jest dla Ciebie trudne.

Na przykład: nie możesz wejść do budynku albo nie rozumiesz pisma.

Napisz do nas albo zadzwoń. Powiedz, co jest dla Ciebie trudne. Zostaw swój kontakt.

Pomożemy Ci najszybciej, jak możemy. Najpóźniej w ciągu 14 dni.

Czasem potrzebujemy więcej czasu. Wtedy Ci o tym powiemy. Poczekasz najwyżej 2 miesiące.

Jeśli nie możemy czegoś zmienić, znajdziemy inny sposób, aby Ci pomóc.

Nie jesteś zadowolony z naszej pomocy? Możesz napisać skargę do urzędu PFRON.

PFRON to urząd, który pomaga osobom z niepełnosprawnością.
TXT,
        ];
    }

    /** @return array{title:string,summary:string,content:string} */
    private function coordinatorEtr(): array
    {
        $addr = self::ADDR;
        $accessEmail = self::ACCESS_EMAIL;

        return [
            'title'   => 'Koordynator do spraw dostępności',
            'summary' => 'Koordynator to osoba, która pomaga w sprawach dostępności.',
            'content' => <<<TXT
Ta osoba pomoże Ci, gdy coś jest dla Ciebie trudne.

Imię i nazwisko: [imię i nazwisko koordynatora].

Telefon: [numer telefonu].

E-mail: {$accessEmail}.

Adres: {$addr}.

Możesz do niej zadzwonić albo napisać.
TXT,
        ];
    }
}
