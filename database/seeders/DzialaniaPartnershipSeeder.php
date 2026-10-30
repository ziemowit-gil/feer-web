<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie „Dostępność w praktyce — współpraca i partnerstwa” (różne formy współpracy, bez otwartego naboru).
 *
 * Pola z pierwotnego szkicu przeniesione na model działania (Project) i jego podstrony:
 *  - description, why → „O projekcie” (treść + „Dlaczego to robimy”),
 *  - recruitment_note → wyróżniona ramka w „O projekcie” (bez naboru, kontakt bezpośredni),
 *  - scope / principles / for_whom → podstrony w menu bocznym działania:
 *    „Zakres współpracy”, „Inne formy współpracy” (wsparcie innych podmiotów), „Wsparcie dla osób fizycznych”,
 *    „Zasady współpracy”, „Dla kogo”,
 *  - kategoria „Wsparcie dla organizacji” (podział według formy, jak ProjectCategoriesByFormSeeder);
 *    pusta kategoria „Dla instytucji i organizacji” z pierwszej wersji seedera jest usuwana,
 *  - is_free = null (partnerstwo) → działanie, nie usługa odpłatna; bez informacji o płatności.
 *
 * Idempotentny (updateOrCreate po slugu). Uruchomienie:
 *   php artisan db:seed --class=DzialaniaPartnershipSeeder --force
 */
class DzialaniaPartnershipSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'wsparcie-dla-organizacji'],
            ['name' => 'Wsparcie dla organizacji', 'order' => (int) Category::max('order') + 1],
        );

        $p = fn (string $text) => '<p>'.e($text).'</p>';
        $items = fn (array $rows) => '<ul>'.collect($rows)->map(fn ($r) => '<li><strong>'.e($r['title']).'</strong> — '.e($r['body']).'</li>')->implode('').'</ul>';

        $forWhom = [
            ['title' => 'Instytucje i partnerzy społeczni', 'body' => 'Podmioty szukające zaufanego, dyskretnego partnera do praktycznego wdrażania standardów dostępności.'],
            ['title' => 'Organizacje pozarządowe', 'body' => 'Fundacje i stowarzyszenia potrzebujące wsparcia przy dostosowywaniu działań i infrastruktury do potrzeb wszystkich odbiorców.'],
            ['title' => 'Osoby fizyczne', 'body' => 'Osoby prywatne z własną inicjatywą na rzecz dostępności, włączenia społecznego osób z niepełnosprawnościami i wzajemnego zrozumienia.'],
        ];

        $content = $p('Tworzenie otwartego, włączającego środowiska to fundament naszej codziennej pracy. W FEER skupiamy się na realnej praktyce — rozwijamy wewnętrzne standardy i procedury, a wypracowaną wiedzę przekuwamy we współpracę z instytucjami i organizacjami.')
            .$p('Po prostu współdziałamy z różnymi instytucjami, organizacjami i partnerami społecznymi — na różnych zasadach i w różnym zakresie, zależnie od potrzeb i kontekstu. Nie ma tu jednego szablonu: raz to wspólny projekt, raz doradztwo, raz audyt.')
            .$p('Wierzymy, że budowanie dostępności: cyfrowej, architektonicznej oraz informacyjno-komunikacyjnej, przynosi najlepsze efekty wtedy, gdy opiera się na zaufaniu i solidnej pracy u podstaw. Nie ogłaszamy naborów — partnerstwa nawiązujemy stopniowo, w oparciu o relacje i wspólne wartości.')
            .$p('Wspieramy też inne podmioty w ich działaniach — o ile są zbieżne z celami naszej fundacji i widzimy w nich interes społeczny.');

        $scope = [
            ['title' => 'Współpraca wdrożeniowa', 'body' => 'Pomoc w praktycznym wprowadzaniu standardów dostępności i procedur u partnerów oraz zaprzyjaźnionych instytucjach.'],
            ['title' => 'Tajemniczy klient', 'body' => 'Przeprowadzamy audyty metodą tajemniczego klienta, sprawdzając dostępność usług, przestrzeni i obsługi z perspektywy osoby z niepełnosprawnością. Wyniki przekazujemy w formie rzetelnego raportu z rekomendacjami.'],
            ['title' => 'Wewnętrzne standardy i konsulting', 'body' => 'Dzielimy się naszym doświadczeniem z zakresu dostępności (WCAG) oraz dobrych praktyk organizacyjnych.'],
            ['title' => 'Dostępność informacyjno-komunikacyjna', 'body' => 'Wsparcie przy tworzeniu dokumentów, procedur i materiałów w formacie łatwym do czytania i rozumienia (ETR).'],
            ['title' => 'Konsulting w zakresie nowoczesnych technologii w NGO', 'body' => 'Doradzamy organizacjom pozarządowym w wyborze i wdrażaniu nowoczesnych narzędzi cyfrowych — od pracy zespołowej w chmurze i bezpieczeństwa danych po rozwiązania wspierające dostępność — tak, by technologia realnie wspierała misję, a nie ją komplikowała.'],
            ['title' => 'Rzetelne doradztwo', 'body' => 'Oparte na zaufaniu konsultacje, bez zbędnej teorii i medialnego rozgłosu.'],
        ];

        $principles = [
            ['title' => 'Indywidualnie', 'body' => 'Zakres wdrożenia i zasady współpracy dopasowujemy do realnych potrzeb oraz skali instytucji.'],
            ['title' => 'Przewidywalnie', 'body' => 'Jasno określone warunki i przejrzyste zasady rozliczeń.'],
            ['title' => 'Bez niespodzianek', 'body' => 'Zero ukrytych kosztów i pełna rzetelność na każdym etapie prac.'],
        ];

        Project::updateOrCreate(
            ['slug' => 'dostepnosc-w-praktyce'],
            [
                'title' => 'Dostępność w praktyce — współpraca i partnerstwa',
                'category_id' => $category->id,
                'excerpt' => 'Współpracujemy z instytucjami i organizacjami na różnych zasadach — od audytów i doradztwa po wsparcie inicjatyw zbieżnych z naszą misją. Wyłącznie w ramach partnerstw.',
                'for_whom' => 'Instytucje, partnerzy społeczni, organizacje pozarządowe i osoby fizyczne',
                'content' => $content,
                'why' => 'Dostępność wciąż jest traktowana jak obowiązek formalny, a nie realna wartość. Tymczasem najtrwalsze zmiany zachodzą wtedy, gdy organizacje przechodzą przez ten proces z kimś, kto rozumie zarówno przepisy, jak i ludzką perspektywę. My to rozumiemy — bo sami jesteśmy fundacją i sami wdrażamy to, o czym mówimy.',
                'kind' => 'project',
                'is_paid' => false,
                'paid_info_show' => false,
                'custom_sections' => [[
                    'title' => 'Współpraca tylko w ramach partnerstw',
                    'content' => $p('To działanie realizujemy wyłącznie w ramach partnerstw. Nie prowadzimy otwartego naboru — współpracę nawiązujemy bezpośrednio, na podstawie wzajemnego zaufania i dopasowania do misji fundacji. Jeśli chcesz nawiązać kontakt, napisz do nas.'),
                    'featured' => true,
                ]],
                'sections_as_tabs' => false,
                'sections_nav' => 'sidebar',
                'main_cta' => null, // bez niebieskiego przycisku „Napisz do nas” w kolumnie bocznej (kontakt jest w ramce „Masz pytania?”)
                'is_published' => true,
            ],
        );

        $project = Project::where('slug', 'dostepnosc-w-praktyce')->firstOrFail();

        // Pierwsza wersja seedera tworzyła kategorię „Dla instytucji i organizacji” — po przeniesieniu
        // działania usuwamy ją, ale tylko gdy jest pusta (żadne działanie, także archiwalne, jej nie używa).
        Category::where('slug', 'dla-instytucji')
            ->whereDoesntHave('projects', fn ($q) => $q->withoutGlobalScopes())
            ->get()
            ->each(fn (Category $empty) => $empty->delete());

        // Podstrony działania w menu bocznym (tryb „zakładka”), w tej kolejności.
        $subpages = [
            'zakres' => ['Zakres współpracy', $items($scope)],
            'inne-formy' => ['Inne formy współpracy',
                $p('Poza wdrożeniami i doradztwem wspieramy inne podmioty w ich działaniach — jeśli są zbieżne z celami naszej fundacji i widzimy w nich interes społeczny. Formę wsparcia dobieramy do sytuacji, na przykład:')
                .'<ul>'.collect([
                    'partnerstwo w projektach i inicjatywach społecznych,',
                    'wsparcie merytoryczne i eksperckie — dostępność, nowoczesne technologie, prawa osób z niepełnosprawnościami,',
                    'konsultacja materiałów, wydarzeń i usług pod kątem dostępności,',
                    'dzielenie się wiedzą, materiałami edukacyjnymi i doświadczeniem,',
                    'promowanie wartościowych inicjatyw w naszych kanałach.',
                ])->map(fn ($item) => '<li>'.e($item).'</li>')->implode('').'</ul>'
                .$p('Każdą prośbę rozpatrujemy indywidualnie. Jeśli Twoja inicjatywa jest bliska naszej misji, napisz do nas.')],
            'osoby-fizyczne' => ['Wsparcie dla osób fizycznych',
                $p('W różny sposób wspieramy także inicjatywy osób fizycznych — na rzecz dostępności, włączenia społecznego osób z niepełnosprawnościami (OzN) oraz lepszego wzajemnego zrozumienia. Nie trzeba być organizacją, żeby zmieniać otoczenie — często wszystko zaczyna się od jednej osoby.')
                .$p('Możemy pomóc na przykład przy:')
                .'<ul>'.collect([
                    'lokalnych inicjatywach na rzecz dostępności przestrzeni, usług lub informacji,',
                    'akcjach i wydarzeniach przybliżających codzienność osób z niepełnosprawnościami,',
                    'przygotowaniu materiałów w formie dostępnej lub łatwej do czytania i rozumienia (ETR),',
                    'poszukiwaniu rozwiązań, wiedzy i kontaktów potrzebnych do realizacji pomysłu.',
                ])->map(fn ($item) => '<li>'.e($item).'</li>')->implode('').'</ul>'
                .$p('Wsparcie dopasowujemy do pomysłu i naszych możliwości — tak jak w przypadku instytucji, kierujemy się zbieżnością z misją fundacji i interesem społecznym. Napisz do nas i opowiedz o swojej inicjatywie.')
                .'<p><strong>Ważne:</strong> nie prowadzimy subkont dla osób indywidualnych ani zbiórek na cele zdrowotne — leczenie, rehabilitację czy zakup sprzętu medycznego. Takich próśb nie jesteśmy w stanie zrealizować.</p>'],
            'zasady' => ['Zasady współpracy', '<ol>'.collect($principles)->map(fn ($r) => '<li><strong>'.e($r['title']).'</strong> — '.e($r['body']).'</li>')->implode('').'</ol>'],
            'dla-kogo' => ['Dla kogo', $items($forWhom)],
        ];
        $order = 1;
        foreach ($subpages as $suffix => [$title, $html]) {
            Page::updateOrCreate(
                ['slug' => 'dostepnosc-w-praktyce-'.$suffix],
                [
                    'title' => $title,
                    'type' => 'standard',
                    'content' => $html,
                    'project_id' => $project->id,
                    'project_display' => 'tab',
                    'is_published' => true,
                    'order' => $order++,
                ],
            );
        }
    }
}
