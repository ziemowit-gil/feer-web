<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów działań (projektów) pod kątem naturalnego języka: bez zdań-wypełniaczy, kontrastów „nie X, tylko Y”,
 * słów reklamowych i myślników jako spójników. Fakty bez zmian.
 *
 * Seeder jest bezpieczny do ponownego uruchomienia: zmienia tylko działania, w których treść nadal zawiera
 * pierwotny fragment (`$marker`), więc nie nadpisze tekstu poprawionego później w panelu.
 *
 *  - działania z edytora blokowego (weCMS, Edukacja w social media) dostają nowe `blocks` i `content` z BlockRenderer,
 *  - „Szkolenia … - odpłatne” dziedziczą treść i zajawkę po „Szkolenia …”, więc zmieniamy tylko działanie nadrzędne.
 *
 * Uruchomienie:
 *   php artisan db:seed --class=ProjectTextsHumanizedSeeder --force
 */
class ProjectTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $p = fn (string $text) => '<p>'.e($text).'</p>';
        $paragraph = fn (string $text) => ['type' => 'paragraph', 'attrs' => ['text' => $text, 'align' => 'left', 'lead' => false]];

        $updates = [
            'wecms' => [
                'marker' => 'Nie chodzi o sam wygląd',
                'blocks' => [
                    $paragraph('<strong>Ta strona, feer.org.pl, działa na weCMS.</strong>'),
                    $paragraph('Stworzyliśmy weCMS, bo mieliśmy dość WordPressa: ciągłych aktualizacji wtyczek, płatnych dodatków i motywów, które trzeba łatać, żeby były dostępne. To nasze własne narzędzie, rozwijane w Fundacji FEER.'),
                    $paragraph('weCMS to <strong>system do zarządzania treścią</strong>: panel, w którym redakcja prowadzi stronę, razem z modułami i logiką pod spodem. Wygląd strony dla odwiedzającego to osobna sprawa, bo szablon każdej organizacji piszemy indywidualnie.'),
                    $paragraph('System ma <strong>czysty, płaski design</strong> z treścią na pierwszym planie i <strong>dostępność wbudowaną od początku</strong> (projektowaną pod WCAG 2.2 AA). Wersja darmowa wystarcza do prowadzenia strony i działa na zwykłym, tanim hostingu, więc <strong>koszty są prawie zerowe</strong>.'),
                    $paragraph('Darowizny online, newsletter, formularze i BIP są wbudowane w system.'),
                ],
            ],
            'edukacja-w-social-media' => [
                'marker' => 'To właśnie tam trafia informacja',
                'blocks' => [
                    $paragraph('Z mediów społecznościowych korzystają miliony ludzi, w każdym wieku. Informacja trafia tam szybko i bez barier wejścia, dlatego FEER jest w nich aktywny: chcemy, żeby wiedza o dostępności, prawach osób z niepełnosprawnościami i technologiach wspomagających była tam, gdzie są ludzie.'),
                    $paragraph('Edukujemy przystępnie: obalamy mity o niepełnosprawności i dostępności, pokazujemy praktyczne rozwiązania i odpowiadamy na pytania, które wiele osób boi się zadać wprost.'),
                ],
            ],
            'szkolenia-informatyka-programowanie-tyfloinformatyka' => [
                'marker' => 'Naszym celem jest rozwijanie logicznego myślenia',
                'excerpt' => 'Indywidualne i kameralne zajęcia z informatyki, programowania (Python, PHP) i tyfloinformatyki dla dzieci i młodzieży, także niewidomych i słabowidzących.',
                'content' => '<p>Prowadzimy indywidualne i kameralne zajęcia z <strong>informatyki, programowania (Python, PHP, podstawy sieci i algorytmów) oraz tyfloinformatyki</strong> dla dzieci i młodzieży.</p>'
                    .$p('Uczymy logicznego myślenia i samodzielnego korzystania z technologii. Metodę nauczania dopasowujemy do potrzeb i tempa uczestnika oraz do stopnia dysfunkcji wzroku, jeśli występuje.'),
            ],
            'dostepnosc-w-praktyce' => [
                'marker' => 'Po prostu współdziałamy',
                'content' => $p('Tworzenie otwartego, włączającego środowiska jest podstawą naszej codziennej pracy. W FEER rozwijamy wewnętrzne standardy i procedury, a zdobytą wiedzę wykorzystujemy we współpracy z instytucjami i organizacjami.')
                    .$p('Współpracujemy z instytucjami, organizacjami i partnerami społecznymi na różnych zasadach, zależnie od potrzeb i kontekstu. Raz to wspólny projekt, raz doradztwo, raz audyt.')
                    .$p('Dostępność cyfrowa, architektoniczna i informacyjno-komunikacyjna daje najlepsze efekty, gdy opiera się na zaufaniu i solidnej pracy u podstaw. Nie ogłaszamy naborów. Partnerstwa nawiązujemy stopniowo, na podstawie relacji i wspólnych wartości.')
                    .$p('Wspieramy też inne podmioty, o ile ich działania są zbieżne z celami fundacji i mają interes społeczny.'),
            ],
            'szkolenia-z-obslugi-komputera-i-sprzetu-wspomagajacego' => [
                'marker' => 'Oferujemy profesjonalne szkolenia',
                'content' => $p('Szkolimy z obsługi komputera, urządzeń mobilnych oraz specjalistycznego sprzętu i oprogramowania dla osób z dysfunkcją wzroku. Zajęcia są indywidualne i dopasowane do potrzeb uczestnika, a ich celem jest samodzielność cyfrowa.')
                    .'<h2>Zakres wsparcia</h2>'
                    .'<ul>'
                    .'<li><strong>Kursy komputerowe:</strong> nauka obsługi systemów operacyjnych dla osób niewidomych i słabowidzących.</li>'
                    .'<li><strong>Urządzenia mobilne:</strong> obsługa smartfonów i tabletów, w tym produktów Apple.</li>'
                    .'<li><strong>Sprzęt specjalistyczny:</strong> konfiguracja czytników książek, powiększalników i innych urządzeń wspomagających.</li>'
                    .'<li><strong>Oprogramowanie:</strong> instalacja i konfiguracja czytników ekranu oraz programów powiększających.</li>'
                    .'</ul>',
            ],
            'wsparcie-ngo' => [
                'marker' => 'Prowadzenie organizacji pozarządowej wiąże się',
                'content' => $p('Sprawy techniczne nie powinny odciągać organizacji od jej misji. Nie musisz być specjalistą, żeby w pełni korzystać z możliwości cyfrowych w swoim NGO. Zajmujemy się infrastrukturą i codziennymi narzędziami, a przy tym dbamy o bezpieczeństwo.'),
            ],
            'materialy-edukacyjne' => [
                'marker' => 'Nie musisz być ekspertem',
                'content' => $p('Udostępniamy bezpłatnie materiały edukacyjne dla wszystkich, którzy uczą dzieci i młodzież: w szkole, w domu, na zajęciach dodatkowych i podczas wspólnego czasu z rodziną.')
                    .$p('To głównie prezentacje i karty pracy oraz kilka gotowych scenariuszy zajęć. Przygotowaliśmy je z myślą o potrzebach nauczycieli, rodziców i opiekunów, więc łatwo z nich korzystać. Wystarczy chęć porozmawiania z dzieckiem lub uczniami o tolerancji, różnorodności i szacunku do drugiego człowieka. Wszystkie materiały są darmowe.'),
            ],
            'akademia-dostepnosci-w-ngo' => [
                'marker' => 'Powstał po to, żeby dostępność przestała',
                'content' => '<p><strong>Akademia Dostępności to bezpłatny projekt dla organizacji pozarządowych działających na rzecz mieszkańców Krakowa.</strong> Ma pomóc NGO w sprawach dostępności, które dziś wywołują stres i niepewność.</p>'
                    .$p('W ramach Akademii dostaniesz wiedzę, praktyczne narzędzia i wsparcie ekspertów, także osób z niepełnosprawnościami. Przeprowadzimy Cię krok po kroku przez przepisy, pokażemy, jak opisać dostępność w ofercie i jak wdrożyć ją w codziennych działaniach organizacji.')
                    .$p('Wiele organizacji traci punkty w konkursach ofert, bo opisuje dostępność ogólnikowo lub błędnie. Nikt wcześniej nie pokazał im, jak zrobić to poprawnie.')
                    .$p('Zaplanowaliśmy cztery webinary online, panel stacjonarny, konsultacje indywidualne i stałą grupę wsparcia na Facebooku. Łącznie to 12 godzin webinarów i 30 godzin konsultacji indywidualnych, bezpłatnie dla organizacji pozarządowych działających na rzecz mieszkańców Krakowa.'),
            ],
        ];

        foreach ($updates as $slug => $update) {
            $project = Project::withoutGlobalScopes()->where('slug', $slug)->first();

            if (! $project) {
                $this->command?->warn("Pominięto {$slug}: brak działania.");

                continue;
            }

            if (! str_contains(html_entity_decode((string) $project->content), $update['marker'])) {
                $this->command?->line("Pominięto {$slug}: treść była już zmieniana.");

                continue;
            }

            if (isset($update['blocks'])) {
                $blocks = BlockRenderer::normalize($update['blocks']);
                $project->blocks = $blocks;
                $project->content = BlockRenderer::render($blocks);
            } else {
                $project->content = $update['content'];
            }

            if (isset($update['excerpt'])) {
                $project->excerpt = $update['excerpt'];
            }

            $project->save();
            $this->command?->info("Zmieniono {$slug}.");
        }
    }
}
