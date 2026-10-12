<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie „weCMS” — nasz system do stron organizacji, na którym działa feer.org.pl.
 * Wersja zapowiedzi: krótko o tym, dlaczego powstał i czym jest; pełny opis, wersje
 * i warunki wdrożenia dojdą niebawem (wyróżniona ramka „Szczegóły już wkrótce”).
 *
 * Podstrony w menu bocznym: „O CMS”, „Funkcjonalności”, „Wersje” (darmowa i płatna, wg config/editions.php),
 * „Wymagania” (zgodne z instalatorem i deploy.sh).
 * Kategoria „Wsparcie dla organizacji” (podział według formy). Idempotentny (updateOrCreate po slugu).
 * Uruchomienie: php artisan db:seed --class=WeCmsSeeder --force
 */
class WeCmsSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'wsparcie-dla-organizacji'],
            ['name' => 'Wsparcie dla organizacji', 'order' => (int) Category::max('order') + 1],
        );

        $p = fn (string $html) => '<p>'.$html.'</p>';

        Project::updateOrCreate(
            ['slug' => 'wecms'],
            [
                'title' => 'weCMS — system do stron organizacji społecznych',
                'nav_title' => 'weCMS',
                'category_id' => $category->id,
                'excerpt' => 'Nasze własne narzędzie do prowadzenia stron: czysty design, dostępność od pierwszego dnia i prawie zerowe koszty. Szczegóły już wkrótce.',
                'for_whom' => 'Fundacje, stowarzyszenia, federacje i samorządy',
                'content' => $p('<strong>Ta strona — feer.org.pl — działa na weCMS.</strong>')
                    .$p('Stworzyliśmy weCMS, bo mieliśmy dość WordPressa: ciągłych aktualizacji wtyczek, płatnych dodatków i motywów, które trzeba łatać, żeby były dostępne. To nasze własne narzędzie, rozwijane w Fundacji FEER.')
                    .$p('Stawiamy na <strong>czysty, płaski design</strong>, w którym treść jest najważniejsza, <strong>dostępność wbudowaną w system</strong> (projektowaną pod WCAG 2.2 AA) oraz <strong>prawie zerowe koszty</strong>: wersja darmowa wystarcza do prowadzenia strony i działa na zwykłym, tanim hostingu.')
                    .$p('Darowizny online, newsletter, formularze, BIP czy sprawozdania są częścią systemu, a nie dodatkami z różnych źródeł.'),
                'kind' => 'project',
                'is_paid' => false,
                'custom_sections' => [[
                    'title' => 'Szczegóły już wkrótce',
                    'content' => $p('Przygotowujemy pełny opis weCMS: funkcje, wersję darmową i płatną oraz zasady wdrożenia dla innych organizacji. Jeśli chcesz dowiedzieć się więcej już teraz, napisz do nas.'),
                    'featured' => true,
                ]],
                'sections_as_tabs' => false,
                'sections_nav' => 'sidebar',
                'main_cta' => ['label' => 'Napisz do nas', 'url' => '/kontakt'],
                'is_published' => true,
            ],
        );

        $project = Project::where('slug', 'wecms')->firstOrFail();
        $list = fn (array $items) => '<ul>'.collect($items)->map(fn ($i) => '<li>'.$i.'</li>')->implode('').'</ul>';

        $subpages = [
            'o-cms' => ['O CMS',
                $p('<strong>Ta strona działa na weCMS</strong> — wszystko, co tu widzisz: działania, aktualności, darowizny, materiały i formularze, prowadzimy w naszym własnym systemie.')
                .$p('weCMS powstał w Fundacji Edukacji Empatii Rozwoju „FEER”, która na co dzień pracuje na rzecz dostępności i osób z niepełnosprawnościami. Zbudowaliśmy go, bo mieliśmy dość WordPressa i jego wtyczek.')
                .'<h2>Na czym nam zależało</h2>'
                .$list([
                    '<strong>Czysty design</strong> — płaski, czytelny układ w kolorach marki, bez ozdobników utrudniających czytanie.',
                    '<strong>Dostępność od pierwszego dnia</strong> — system projektowany pod WCAG 2.2 AA, kontrast tekstu co najmniej 4,5:1, pełna obsługa klawiaturą i czytnikami ekranu.',
                    '<strong>Prawie zerowe koszty</strong> — wersja darmowa wystarcza do prowadzenia strony, bez płatnych motywów i wtyczek.',
                    '<strong>Wszystko w jednym</strong> — darowizny, newsletter, formularze czy BIP są częścią systemu, a nie dodatkami z różnych źródeł.',
                    '<strong>Proste prowadzenie</strong> — panel po polsku, z którego korzysta redakcja bez wiedzy technicznej.',
                ])],
            'funkcjonalnosci' => ['Funkcjonalności',
                '<h2>Treści i redakcja</h2>'.$list([
                    'Edytor klasyczny i blokowy, szablony treści, import z plików Word (DOCX)',
                    'Historia zmian, kosz, blokada równoczesnej edycji, akceptacja treści przed publikacją',
                    'Edycja tekstu bezpośrednio na stronie, zadania i kalendarz redakcyjny',
                    '32 typy stron — m.in. cennik, regulamin, rekrutacja, zapytanie ofertowe, przekierowanie',
                ])
                .'<h2>Działania i projekty</h2>'.$list([
                    'Strona działania z menu sekcji i podstronami, zdjęcie jako baner',
                    'Działania bezpłatne i odpłatne z cennikiem, archiwum zrealizowanych działań',
                ])
                .'<h2>Wsparcie finansowe i komunikacja</h2>'.$list([
                    'Darowizny online i sklep z materiałami (Przelewy24)',
                    'Newsletter, kreator formularzy, ankiety, powiadomienia web push, kanał RSS, podcasty',
                ])
                .'<h2>Przejrzystość organizacji</h2>'.$list([
                    'Biuletyn Informacji Publicznej, sprawozdania, oferty pracy, wolontariat',
                    'Klauzule RODO, partnerzy i sponsorzy, deklaracja dostępności i zgłaszanie barier',
                ])
                .'<h2>Dostępność</h2>'.$list([
                    'Pasek dostępności dla odwiedzających, wersje łatwe do czytania (ETR)',
                    'Skaner WCAG całego serwisu, wyszukiwanie martwych linków, pilnowanie opisów zdjęć',
                ])
                .'<h2>Wygląd, skala i integracje</h2>'.$list([
                    '9 szablonów witryny — m.in. dla fundacji, federacji i urzędów; zmiana bez utraty treści',
                    'Wiele witryn w jednej instalacji (np. ośrodki lub organizacje członkowskie)',
                    'Logowanie przez Microsoft 365 i Google, poczta przez Microsoft 365, integracja z SZO, Unsplash, OneDrive',
                ])],
            'wersje' => ['Wersje',
                $p('weCMS ma <strong>wersję darmową</strong>, która wystarcza do prowadzenia strony organizacji, i <strong>wersję płatną</strong> — dla organizacji, które potrzebują płatności online, większej skali albo pracy zespołowej.')
                .$p('Dostępność, bezpieczeństwo i eksport treści są zawsze w wersji darmowej.')
                .'<table><caption>Porównanie wersji weCMS</caption><thead><tr><th scope="col">Obszar</th><th scope="col">Darmowa</th><th scope="col">Płatna</th></tr></thead><tbody><tr><th scope="row">Strony, aktualności, menu, biblioteka mediów, galeria, FAQ, wydarzenia</th><td>Tak</td><td>Tak</td></tr><tr><th scope="row">Wszystkie funkcje dostępności, logowanie dwuetapowe, eksport treści</th><td>Tak</td><td>Tak</td></tr><tr><th scope="row">Szablony witryny</th><td>Domyślny i NGO</td><td>Wszystkie</td></tr><tr><th scope="row">Darowizny i sklep (Przelewy24)</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Newsletter, kreator formularzy, ankiety, kampanie</th><td>—</td><td>Tak</td></tr><tr><th scope="row">BIP, sprawozdania, oferty pracy, wolontariat, zgłoszenia współpracy</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Wiele witryn, grupy użytkowników, zadania, szablony treści, import DOCX</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Skaner WCAG serwisu, wyszukiwanie martwych linków</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Pomoc techniczna</th><td>Dokumentacja</td><td>Zgłoszenia przez Helpdesk</td></tr></tbody></table>'
                .'<h2>Gdy licencja wygaśnie</h2>'
                .$p('Strona działa dalej. Treści z modułów płatnych zostają widoczne dla odwiedzających, a w panelu można je przeglądać — wyłączają się tylko nowe wpisy i akcje, takie jak wysyłka newslettera czy przyjmowanie płatności.')],
            'wymagania' => ['Wymagania',
                $p('weCMS działa na zwykłym hostingu współdzielonym albo na własnym serwerze. Instalator w przeglądarce sam sprawdza, czy serwer spełnia wymagania.')
                .'<h2>Serwer</h2>'.$list([
                    '<strong>PHP 8.4</strong> lub nowszy',
                    'Rozszerzenia PHP: PDO (SQLite lub MySQL), OpenSSL, Mbstring, Tokenizer, XML, Fileinfo oraz GD albo Imagick',
                    'Baza danych: <strong>SQLite</strong> (bez osobnego serwera) albo <strong>MySQL</strong>',
                    'Zapis do katalogów <code>storage/</code> i <code>bootstrap/cache/</code> oraz do pliku <code>.env</code>',
                    'Zadanie cron uruchamiane co minutę (harmonogram: przypomnienia, płatności, archiwizacja)',
                    'Certyfikat SSL (HTTPS)',
                ])
                .'<h2>Instalacja i aktualizacje</h2>'.$list([
                    'Composer do instalacji zależności oraz Node.js do zbudowania stylów',
                    'Instalator w przeglądarce: sprawdzenie serwera, baza, konto administratora, wybór szablonu i modułów',
                    'Wersja płatna: klucz licencji przypisany do domeny',
                ])
                .'<h2>Po stronie redakcji</h2>'.$list([
                    'Aktualna przeglądarka — Chrome, Edge, Firefox lub Safari',
                    'Nie trzeba instalować żadnych programów ani znać HTML',
                ])],
        ];

        $order = 1;
        foreach ($subpages as $suffix => [$title, $html]) {
            Page::updateOrCreate(
                ['slug' => 'wecms-'.$suffix],
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
