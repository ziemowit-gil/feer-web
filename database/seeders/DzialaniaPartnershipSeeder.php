<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie „Dostępność w praktyce — wdrożenia i konsulting” (partnerstwa, bez otwartego naboru).
 *
 * Pola z pierwotnego szkicu przeniesione na model działania (Project):
 *  - recruitment_note → wyróżniona sekcja własna (bez naboru, kontakt bezpośredni),
 *  - scope / principles → sekcje własne „Zakres współpracy” i „Zasady współpracy”
 *    (formularz w panelu obsługuje do 3 sekcji — więcej by zginęło przy edycji),
 *  - for_whom (lista) → krótkie „Dla kogo” + sekcja „Dla kogo” w treści,
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
            ['slug' => 'dla-instytucji'],
            ['name' => 'Dla instytucji i organizacji', 'order' => (int) Category::max('order') + 1],
        );

        $p = fn (string $text) => '<p>'.e($text).'</p>';
        $items = fn (array $rows) => '<ul>'.collect($rows)->map(fn ($r) => '<li><strong>'.e($r['title']).'</strong> — '.e($r['body']).'</li>')->implode('').'</ul>';

        $forWhom = [
            ['title' => 'Instytucje i partnerzy społeczni', 'body' => 'Podmioty szukające zaufanego, dyskretnego partnera do praktycznego wdrażania standardów dostępności.'],
            ['title' => 'Organizacje pozarządowe', 'body' => 'Fundacje i stowarzyszenia potrzebujące wsparcia przy dostosowywaniu działań i infrastruktury do potrzeb wszystkich odbiorców.'],
        ];

        $content = $p('Tworzenie otwartego, włączającego środowiska to fundament naszej codziennej pracy. W FEER skupiamy się na realnej praktyce — rozwijamy wewnętrzne standardy i procedury, a wypracowaną wiedzę przekuwamy we współpracę z instytucjami i organizacjami.')
            .$p('Po prostu współdziałamy z różnymi instytucjami, organizacjami i partnerami społecznymi — na różnych zasadach i w różnym zakresie, zależnie od potrzeb i kontekstu. Nie ma tu jednego szablonu: raz to wspólny projekt, raz doradztwo, raz audyt.')
            .$p('Wierzymy, że budowanie dostępności: cyfrowej, architektonicznej oraz informacyjno-komunikacyjnej, przynosi najlepsze efekty wtedy, gdy opiera się na zaufaniu i solidnej pracy u podstaw. Nie ogłaszamy naborów — partnerstwa nawiązujemy stopniowo, w oparciu o relacje i wspólne wartości.')
            .'<h2>Dla kogo</h2>'.$items($forWhom);

        $scope = [
            ['title' => 'Współpraca wdrożeniowa', 'body' => 'Pomoc w praktycznym wprowadzaniu standardów dostępności i procedur u partnerów oraz zaprzyjaźnionych instytucjach.'],
            ['title' => 'Tajemniczy klient', 'body' => 'Przeprowadzamy audyty metodą tajemniczego klienta, sprawdzając dostępność usług, przestrzeni i obsługi z perspektywy osoby z niepełnosprawnością. Wyniki przekazujemy w formie rzetelnego raportu z rekomendacjami.'],
            ['title' => 'Wewnętrzne standardy i konsulting', 'body' => 'Dzielimy się naszym doświadczeniem z zakresu dostępności (WCAG) oraz dobrych praktyk organizacyjnych.'],
            ['title' => 'Dostępność informacyjno-komunikacyjna', 'body' => 'Wsparcie przy tworzeniu dokumentów, procedur i materiałów w formacie łatwym do czytania i rozumienia (ETR).'],
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
                'title' => 'Dostępność w praktyce — wdrożenia i konsulting',
                'category_id' => $category->id,
                'excerpt' => 'Wdrożenia dostępności, audyty metodą tajemniczego klienta i doradztwo — wyłącznie w ramach partnerstw.',
                'for_whom' => 'Instytucje, partnerzy społeczni i organizacje pozarządowe',
                'content' => $content,
                'why' => 'Dostępność wciąż jest traktowana jak obowiązek formalny, a nie realna wartość. Tymczasem najtrwalsze zmiany zachodzą wtedy, gdy organizacje przechodzą przez ten proces z kimś, kto rozumie zarówno przepisy, jak i ludzką perspektywę. My to rozumiemy — bo sami jesteśmy fundacją i sami wdrażamy to, o czym mówimy.',
                'kind' => 'project',
                'is_paid' => false,
                'paid_info_show' => false,
                'custom_sections' => [
                    [
                        'title' => 'Współpraca tylko w ramach partnerstw',
                        'content' => $p('To działanie realizujemy wyłącznie w ramach partnerstw. Nie prowadzimy otwartego naboru — współpracę nawiązujemy bezpośrednio, na podstawie wzajemnego zaufania i dopasowania do misji fundacji. Jeśli chcesz nawiązać kontakt, napisz do nas.'),
                        'featured' => true,
                    ],
                    ['title' => 'Zakres współpracy', 'content' => $items($scope), 'featured' => false],
                    [
                        'title' => 'Zasady współpracy',
                        'content' => '<ol>'.collect($principles)->map(fn ($r) => '<li><strong>'.e($r['title']).'</strong> — '.e($r['body']).'</li>')->implode('').'</ol>',
                        'featured' => false,
                    ],
                ],
                'main_cta' => ['label' => 'Napisz do nas', 'url' => '/kontakt'],
                'is_published' => true,
            ],
        );
    }
}
