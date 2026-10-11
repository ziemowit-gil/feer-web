<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie odpłatne „Szkolenia z informatyki, programowania i tyfloinformatyki dla dzieci i młodzieży”.
 *
 * Szkic zapisywał Markdown do tabeli `pages` z polem `is_paid`, którego tam nie ma — tu jako działanie
 * (Project) typu „usługa wyłącznie odpłatna”:
 *  - informacja „Z tej usługi skorzystasz za opłatą” + link do zasad odpłatnej działalności pokazuje się sama,
 *  - opis → „O projekcie”, cennik → tabela cennika działania,
 *  - zakres i program zajęć oraz prowadzący → podstrony w menu bocznym,
 *  - kategoria „Szkolenia i warsztaty” (podział według formy).
 *
 * Cena szkolenia stacjonarnego w szkicu była do uzupełnienia — tu „do ustalenia” (zmiana w panelu: Cennik).
 * Idempotentny (updateOrCreate po slugu). Uruchomienie:
 *   php artisan db:seed --class=SzkolenieSeeder --force
 */
class SzkolenieSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'szkolenia-i-warsztaty'],
            ['name' => 'Szkolenia i warsztaty', 'order' => (int) Category::max('order') + 1],
        );

        $p = fn (string $html) => '<p>'.$html.'</p>';

        $project = Project::updateOrCreate(
            ['slug' => 'szkolenia-informatyka-programowanie-tyfloinformatyka'],
            [
                'title' => 'Szkolenia z informatyki, programowania i tyfloinformatyki dla dzieci i młodzieży',
                'category_id' => $category->id,
                'excerpt' => 'Indywidualne i kameralne zajęcia z informatyki, programowania (Python, PHP) i tyfloinformatyki dla dzieci i młodzieży — także niewidomych i słabowidzących.',
                'for_whom' => 'Dzieci i młodzież, w tym osoby niewidome i słabowidzące',
                'content' => $p('Oferujemy profesjonalne, indywidualne oraz kameralne zajęcia z zakresu <strong>informatyki, programowania (Python, PHP, podstawy sieci i algorytmów) oraz tyfloinformatyki</strong> dedykowane dzieciom i młodzieży.')
                    .$p('Naszym celem jest rozwijanie logicznego myślenia, pasji do technologii oraz pełnej samodzielności cyfrowej. Zapewniamy w pełni dostępną i przyjazną przestrzeń edukacyjną, dostosowując metodykę nauczania do indywidualnych potrzeb, tempa pracy oraz ewentualnego stopnia dysfunkcji wzroku uczestnika.'),
                'kind' => 'paid_offer',
                'is_paid' => true,
                'pricing' => [
                    ['item' => 'Szkolenie zdalne', 'note' => 'Godzina zegarowa (60 min)', 'price' => '85 zł'],
                    ['item' => 'Szkolenie stacjonarne', 'note' => 'Godzina zegarowa (60 min)', 'price' => 'do ustalenia'],
                ],
                'sections_as_tabs' => false,
                'sections_nav' => 'sidebar',
                'main_cta' => ['label' => 'Zapytaj o zajęcia', 'url' => '/kontakt'],
                'is_published' => true,
            ],
        );

        $subpages = [
            'program' => ['Zakres wsparcia i program zajęć',
                '<h2>Programowanie dla dzieci i młodzieży (Python i PHP)</h2><ul>'
                .'<li><strong>Python:</strong> nauka czystego pisania kodu od podstaw, budowanie logiki algorytmicznej, pętli, funkcji oraz tworzenie własnych prostych gier i skryptów.</li>'
                .'<li><strong>PHP i podstawy webowe:</strong> wprowadzenie do tworzenia stron internetowych, podstawy HTML/CSS oraz prosta obsługa baz danych.</li></ul>'
                .'<h2>Tyfloinformatyka i technologie wspomagające</h2><ul>'
                .'<li>Nauka obsługi komputera i urządzeń mobilnych przez dzieci niewidome i słabowidzące z użyciem czytników ekranu (NVDA, JAWS, VoiceOver) oraz oprogramowania powiększającego.</li>'
                .'<li>Konfiguracja i obsługa sprzętu brajlowskiego oraz powiększalników.</li></ul>'
                .'<h2>Ogólne podstawy informatyki</h2><ul>'
                .'<li>Architektura komputera, sprawne posługiwanie się systemami operacyjnymi (Windows, macOS) i bezwzrokowa obsługa klawiatury.</li>'
                .'<li>Bezpieczne korzystanie z Internetu, higiena cyfrowa oraz podstawowe narzędzia ułatwiające codzienne uczenie się.</li></ul>'],
            'prowadzacy' => ['Prowadzący',
                $p('Zajęcia prowadzi <strong>Ziemowit Gil</strong> — tyfloinformatyk, programista i edukator z wieloletnim doświadczeniem w pracy z dziećmi, młodzieżą oraz technologiami asystującymi.')],
        ];

        $order = 1;
        foreach ($subpages as $suffix => [$title, $html]) {
            Page::updateOrCreate(
                ['slug' => 'szkolenia-informatyka-'.$suffix],
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
