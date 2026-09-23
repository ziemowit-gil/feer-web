<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Zakłada dział „Informacje dla osób ze szczególnymi potrzebami" jako zestaw
 * podstron (strona-rodzic + podstrony potomne), wzorowany na strukturze
 * https://wupkrakow.praca.gov.pl/dostepnosc.
 *
 * Strony powstają jako SZKICE (is_published = false), bo treść zawiera pola do
 * uzupełnienia w nawiasach [ ... ] (adresy, dane koordynatora, opisy budynków).
 * Po wypełnieniu placeholderów opublikuj je w panelu — dopiero wtedy pojawią się
 * w bocznej nawigacji działu i w mapie strony.
 *
 * Dostępność cyfrowa nie jest duplikowana — podstrona odsyła do istniejącej
 * Deklaracji dostępności (/deklaracja-dostepnosci) wraz z formularzem zgłaszania
 * barier.
 *
 * Uruchomienie:  php artisan db:seed --class=AccessibilitySubpagesSeeder
 * (na serwerze:  php85 artisan db:seed --class=AccessibilitySubpagesSeeder)
 */
class AccessibilitySubpagesSeeder extends Seeder
{
    private const PARENT_SLUG = 'dostepnosc';

    public function run(): void
    {
        if (Page::withTrashed()->where('slug', self::PARENT_SLUG)->exists()) {
            $this->command->warn('Podstrona o slugu "'.self::PARENT_SLUG.'" już istnieje — seeder pominięty. Usuń ją, aby wygenerować dział od nowa.');

            return;
        }

        $parent = Page::create([
            'title'            => 'Informacje dla osób ze szczególnymi potrzebami',
            'slug'             => self::PARENT_SLUG,
            'type'             => 'standard',
            'content'          => $this->parentContent(),
            'is_published'     => false,
            'show_in_menu'     => false, // włącz w panelu, jeśli chcesz link w menu głównym
            'show_side_nav'    => true,
            'order'            => 0,
            'meta_title'       => 'Dostępność — informacje dla osób ze szczególnymi potrzebami',
            'meta_description' => 'Dostępność architektoniczna, informacyjno-komunikacyjna i cyfrowa. Wniosek o zapewnienie dostępności, procedura odwoławcza i kontakt do koordynatora ds. dostępności.',
        ]);

        $children = [
            [
                'title'   => 'Dostępność architektoniczna',
                'slug'    => 'dostepnosc-architektoniczna',
                'content' => $this->architecturalContent(),
                'meta'    => 'Opis dostępności architektonicznej naszych budynków: dojście, parking, wejście, komunikacja wewnątrz, toalety, pies asystujący.',
            ],
            [
                'title'   => 'Dostępność informacyjno-komunikacyjna',
                'slug'    => 'dostepnosc-komunikacyjna',
                'content' => $this->communicationContent(),
                'meta'    => 'Tłumacz PJM, pętla indukcyjna, tekst łatwy do czytania (ETR), dostępne formaty dokumentów i możliwe formy kontaktu.',
            ],
            [
                'title'   => 'Dostępność cyfrowa',
                'slug'    => 'dostepnosc-cyfrowa',
                'content' => $this->digitalContent(),
                'meta'    => 'Deklaracja dostępności cyfrowej zgodna z WCAG 2.1 AA oraz sposób zgłaszania problemów technicznych.',
            ],
            [
                'title'   => 'Wniosek o zapewnienie dostępności i procedura odwoławcza',
                'slug'    => 'wniosek-o-dostepnosc',
                'content' => $this->requestContent(),
                'meta'    => 'Jak złożyć wniosek o zapewnienie dostępności, ustawowe terminy, dostęp alternatywny i prawo do skargi do Prezesa PFRON.',
            ],
            [
                'title'   => 'Koordynator do spraw dostępności',
                'slug'    => 'koordynator-dostepnosci',
                'content' => $this->coordinatorContent(),
                'meta'    => 'Dane kontaktowe koordynatora do spraw dostępności i zakres jego zadań.',
            ],
        ];

        foreach ($children as $i => $child) {
            Page::create([
                'title'            => $child['title'],
                'slug'             => $child['slug'],
                'type'             => 'standard',
                'parent_id'        => $parent->id,
                'content'          => $child['content'],
                'is_published'     => false,
                'show_in_menu'     => false,
                'show_side_nav'    => true,
                'order'            => $i + 1,
                'meta_title'       => $child['title'],
                'meta_description' => $child['meta'],
            ]);
        }

        $this->command->info('Utworzono dział „Dostępność": 1 strona-rodzic + '.count($children).' podstrony (jako szkice).');
        $this->command->warn('Uzupełnij pola [w nawiasach], a następnie opublikuj strony w panelu: Strony → Informacje dla osób ze szczególnymi potrzebami.');
    }

    private function parentContent(): string
    {
        return <<<'HTML'
<p><strong>[Nazwa instytucji]</strong> dokłada wszelkich starań, aby nasza siedziba, strona internetowa oraz sposób obsługi były dostępne dla każdej osoby — niezależnie od jej sprawności, wieku czy sposobu komunikowania się. Chcemy, aby każdy mógł samodzielnie i na równych zasadach korzystać z naszych usług.</p>
<p>Jeśli napotkasz barierę w kontakcie z nami — poinformuj nas. Wspólnie znajdziemy rozwiązanie.</p>

<h2>W tym dziale</h2>
<ul>
<li><a href="/dostepnosc-architektoniczna">Dostępność architektoniczna</a> — jak przygotowane są nasze budynki (dojście, parking, wejście, windy, toalety).</li>
<li><a href="/dostepnosc-komunikacyjna">Dostępność informacyjno-komunikacyjna</a> — tłumacz PJM, pętla indukcyjna, tekst łatwy do czytania (ETR), dostępne formaty.</li>
<li><a href="/dostepnosc-cyfrowa">Dostępność cyfrowa</a> — zgodność ze standardem WCAG i zgłaszanie problemów z serwisem.</li>
<li><a href="/wniosek-o-dostepnosc">Wniosek o zapewnienie dostępności i procedura odwoławcza</a> — co zrobić, gdy nie zapewniamy pełnej dostępności.</li>
<li><a href="/koordynator-dostepnosci">Koordynator do spraw dostępności</a> — z kim się skontaktować.</li>
</ul>

<h2>Szybki kontakt</h2>
<ul>
<li><strong>Telefon:</strong> [numer telefonu]</li>
<li><strong>E-mail:</strong> [adres e-mail]</li>
<li><strong>Adres:</strong> [adres siedziby]</li>
</ul>
HTML;
    }

    private function architecturalContent(): string
    {
        return <<<'HTML'
<p>Poniżej opisujemy, jak przygotowane są nasze budynki na przyjęcie osób ze szczególnymi potrzebami. Jeśli prowadzisz obsługę w kilku lokalizacjach, powiel sekcję „Budynek" dla każdej z nich.</p>

<h2>Budynek: [Adres budynku / nazwa siedziby]</h2>

<h3>Otoczenie i dojście</h3>
<ul>
<li>Do budynku prowadzi [chodnik / utwardzona droga] o szerokości umożliwiającej przejazd wózkiem.</li>
<li>Najbliższy przystanek [autobusowy / tramwajowy] znajduje się w odległości około [liczba] metrów.</li>
<li>[Opisz oznaczenia dotykowe lub kontrastowe, jeśli występują.]</li>
</ul>

<h3>Miejsca parkingowe</h3>
<ul>
<li>Przed budynkiem znajduje się [liczba] wyznaczone miejsce parkingowe dla osób z niepełnosprawnością, oznaczone kopertą i znakiem.</li>
<li>Miejsce znajduje się w odległości około [liczba] metrów od wejścia.</li>
</ul>

<h3>Wejście do budynku</h3>
<ul>
<li>Wejście główne znajduje się od strony [ulicy / opis].</li>
<li>Do wejścia prowadzi [podjazd / pochylnia] o nachyleniu zgodnym z przepisami oraz schody z poręczą.</li>
<li>Drzwi wejściowe są [szerokie na … cm / otwierane automatycznie / wymagają pomocy — opisz].</li>
<li>[Informacja o dzwonku przywoławczym lub domofonie przy wejściu, jeśli jest.]</li>
</ul>

<h3>Komunikacja wewnątrz budynku</h3>
<ul>
<li>Obsługa osób ze szczególnymi potrzebami odbywa się na [parterze / piętrze].</li>
<li>[W budynku znajduje się winda: rozmiary, oznaczenia w alfabecie Braille'a, komunikaty głosowe] — LUB — [W budynku nie ma windy.]</li>
<li>Korytarze mają szerokość umożliwiającą minięcie się dwóch wózków.</li>
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
        return <<<'HTML'
<p>Oferujemy różne sposoby kontaktu, abyś mógł/mogła porozumieć się z nami w wygodnej dla siebie formie.</p>

<h2>Kontakt</h2>
<p>Możesz skontaktować się z nami:</p>
<ul>
<li><strong>telefonicznie:</strong> [numer telefonu],</li>
<li><strong>e-mailem:</strong> [adres e-mail],</li>
<li><strong>listownie:</strong> [adres do korespondencji],</li>
<li><strong>osobiście</strong> w siedzibie: [adres],</li>
<li><strong>przez SMS / komunikator [nazwa], jeśli dotyczy:</strong> [dane].</li>
</ul>

<h2>Tłumacz polskiego języka migowego (PJM)</h2>
<ul>
<li>Osoby głuche lub słabosłyszące mogą skorzystać z pomocy tłumacza polskiego języka migowego (PJM).</li>
<li><strong>Tłumacz online (na miejscu, przez wideopołączenie):</strong> dostępny [zawsze w godzinach pracy / po wcześniejszym zgłoszeniu].</li>
<li><strong>Tłumacz stacjonarnie:</strong> chęć skorzystania zgłoś co najmniej [liczba] dni roboczych wcześniej, na adres [e-mail] lub telefonicznie [numer].</li>
<li>Możesz również skorzystać z pomocy osoby przybranej — dowolnej osoby pełnoletniej, którą sam/sama wybierzesz do pomocy w załatwieniu sprawy.</li>
</ul>

<h2>Pętla indukcyjna</h2>
<ul>
<li>[W miejscu: punkt obsługi / sala — dostępna jest pętla indukcyjna ułatwiająca kontakt osobom korzystającym z aparatów słuchowych.] — LUB — [Nie dysponujemy pętlą indukcyjną.]</li>
</ul>

<h2>Informacja w tekście łatwym do czytania (ETR)</h2>
<ul>
<li>Najważniejsze informacje o naszej działalności przygotowaliśmy w tekście łatwym do czytania i zrozumienia (ETR): [link].</li>
</ul>

<h2>Dokumenty w dostępnych formatach</h2>
<ul>
<li>Na Twoją prośbę udostępnimy informacje w formie, która będzie dla Ciebie dostępna — np. jako dokument odczytywalny maszynowo, wydruk powiększony lub odczyt treści przez pracownika.</li>
</ul>
HTML;
    }

    private function digitalContent(): string
    {
        return <<<'HTML'
<h2>Deklaracja zgodności</h2>
<p><strong>[Nazwa instytucji]</strong> zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z przepisami ustawy z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.</p>
<p>Nasz serwis dąży do zgodności ze standardem <strong>WCAG 2.1 na poziomie AA</strong>. Pełną, aktualną informację o poziomie zgodności, dacie publikacji i przeglądu oraz o ewentualnych wyłączeniach znajdziesz w oficjalnej deklaracji dostępności.</p>

<p><a href="/deklaracja-dostepnosci"><strong>Przejdź do Deklaracji dostępności</strong></a></p>

<h2>Zgłaszanie problemów z dostępnością cyfrową</h2>
<p>Jeśli napotkasz stronę, dokument lub funkcję, która jest dla Ciebie niedostępna, skorzystaj z <a href="/deklaracja-dostepnosci">formularza zgłaszania barier</a> dostępnego w Deklaracji dostępności lub napisz na adres [adres e-mail koordynatora].</p>
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
        return <<<'HTML'
<p>W <strong>[Nazwa instytucji]</strong> wyznaczyliśmy osobę odpowiedzialną za koordynację działań na rzecz dostępności.</p>

<ul>
<li><strong>Imię i nazwisko:</strong> [Imię i nazwisko koordynatora]</li>
<li><strong>Stanowisko:</strong> [np. Koordynator ds. dostępności]</li>
<li><strong>E-mail:</strong> [adres e-mail]</li>
<li><strong>Telefon:</strong> [numer telefonu]</li>
<li><strong>Adres do korespondencji:</strong> [adres]</li>
</ul>

<p>Do zadań koordynatora należy m.in. wsparcie osób ze szczególnymi potrzebami w dostępie do naszych usług, przygotowanie planu działania na rzecz poprawy dostępności oraz monitorowanie dostępności naszej instytucji.</p>
HTML;
    }
}
