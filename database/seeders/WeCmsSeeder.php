<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie „weCMS” — nasz system do stron organizacji, na którym działa feer.org.pl.
 * Wersja zapowiedzi: krótko o tym, dlaczego powstał i czym jest; pełny opis, wersje
 * i warunki wdrożenia dojdą niebawem (wyróżniona ramka „Szczegóły już wkrótce”).
 *
 * Podstrony w menu bocznym: „O CMS”, „Funkcjonalności”, „Wersje” (darmowa i płatna, wg config/editions.php),
 * „Demo” (zapowiedź), „Wymagania” (zgodne z instalatorem i deploy.sh), „Zamówienie” z formularzem
 * zamówienia (kreator formularzy, powiadomienia na ziemowit.gil@feer.org.pl; formularz zakładany raz —
 * zmiany z panelu nie są nadpisywane).
 * Kategoria „Wsparcie dla organizacji” (podział według formy). Idempotentny (updateOrCreate po slugu).
 * Uruchomienie: php artisan db:seed --class=WeCmsSeeder --force
 */
class WeCmsSeeder extends Seeder
{
    public const FORM_SLUG = 'zamowienie-wecms';

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
                // Bez zajawki i „Dla kogo” — powtarzały się w nagłówku każdej podstrony.
                'excerpt' => null,
                'for_whom' => null,
                'content' => $p('<strong>Ta strona — feer.org.pl — działa na weCMS.</strong>')
                    .$p('Stworzyliśmy weCMS, bo mieliśmy dość WordPressa: ciągłych aktualizacji wtyczek, płatnych dodatków i motywów, które trzeba łatać, żeby były dostępne. To nasze własne narzędzie, rozwijane w Fundacji FEER.')
                    .$p('weCMS to <strong>system do zarządzania treścią</strong> — panel, w którym redakcja prowadzi stronę, razem z modułami i całą logiką pod spodem. Nie chodzi o sam wygląd, który widzi odwiedzający: szablon strony każdej organizacji piszemy indywidualnie.')
                    .$p('Stawiamy na <strong>czysty, płaski design</strong>, w którym treść jest najważniejsza, <strong>dostępność wbudowaną w system</strong> (projektowaną pod WCAG 2.2 AA) oraz <strong>prawie zerowe koszty</strong>: wersja darmowa wystarcza do prowadzenia strony i działa na zwykłym, tanim hostingu.')
                    .$p('Darowizny online, newsletter, formularze czy BIP są częścią systemu, a nie dodatkami z różnych źródeł.'),
                'kind' => 'project',
                'is_paid' => false,
                'custom_sections' => [[
                    'title' => 'Szczegóły już wkrótce',
                    'content' => $p('Przygotowujemy pełny opis weCMS: funkcje, wersję darmową i płatną oraz zasady wdrożenia dla innych organizacji. Jeśli chcesz dowiedzieć się więcej już teraz, napisz do nas.'),
                    'featured' => true,
                ]],
                'sections_as_tabs' => false,
                'sections_nav' => 'sidebar',
                // Bez przycisku w nagłówku — zaproszenie do kontaktu jest w treści.
                'main_cta' => null,
                'is_published' => true,
            ],
        );

        $project = Project::where('slug', 'wecms')->firstOrFail();
        $this->orderForm();
        $list = fn (array $items) => '<ul>'.collect($items)->map(fn ($i) => '<li>'.$i.'</li>')->implode('').'</ul>';

        $subpages = [
            'o-cms' => ['O CMS',
                $p('<strong>Ta strona działa na weCMS</strong> — wszystko, co tu widzisz, prowadzimy w naszym własnym systemie.')
                .$p('weCMS powstał w Fundacji Edukacji Empatii Rozwoju „FEER”, która na co dzień pracuje na rzecz dostępności i osób z niepełnosprawnościami. Zbudowaliśmy go, bo mieliśmy dość WordPressa i jego wtyczek. Wersję podstawową pisaliśmy od maja do października 2026 roku — i będziemy go dalej rozwijać.')
                .'<h2>System do zarządzania treścią, nie gotowy wygląd</h2>'
                .$p('weCMS to <strong>CMS — system do zarządzania treścią</strong>: panel redakcji, moduły (aktualności, darowizny, formularze, BIP i inne), uprawnienia, historia zmian, dostępność i integracje. To, co widzi odwiedzający — <strong>szablon strony — piszemy indywidualnie dla każdej organizacji</strong>, zgodnie z jej identyfikacją wizualną.')
                .$p('Dzięki temu dwie strony na weCMS nie wyglądają tak samo, a każda korzysta z tego samego, sprawdzonego zaplecza.')
                .'<h2>Na czym nam zależało</h2>'
                .$list([
                    '<strong>Czysty design</strong> — płaski, czytelny układ w kolorach marki, bez ozdobników utrudniających czytanie.',
                    '<strong>Dostępność od pierwszego dnia</strong> — system projektowany pod WCAG 2.2 AA, kontrast tekstu co najmniej 4,5:1, pełna obsługa klawiaturą i czytnikami ekranu.',
                    '<strong>Prawie zerowe koszty</strong> — wersja darmowa wystarcza do prowadzenia strony, bez płatnych motywów i wtyczek.',
                    '<strong>Wszystko w jednym</strong> — darowizny, newsletter, formularze czy BIP są częścią systemu, a nie dodatkami z różnych źródeł.',
                    '<strong>Proste prowadzenie</strong> — panel po polsku, z którego korzysta redakcja bez wiedzy technicznej.',
                ])],
            'funkcjonalnosci' => ['Funkcjonalności',
                $p('weCMS to kompletne zaplecze strony organizacji: od drzewa stron i edytorów, przez aktualności i moduły, po dostępność, pracę zespołową i integracje. Poniżej krótki przegląd — szczegóły każdego obszaru znajdziesz w podstronach w menu bocznym.')
                .$list([
                    '<strong>Strony i struktura</strong> — kilkadziesiąt typów stron, 7 układów, drzewo działu, poziomy dostępu, publikacja z wyprzedzeniem.',
                    '<strong>Edytory i redakcja</strong> — edytor klasyczny i blokowy, historia zmian, biblioteka mediów z audytem opisów zdjęć.',
                    '<strong>Praca zespołowa i bezpieczeństwo</strong> — role, grupy, akceptacja treści, zadania, logowanie dwuetapowe.',
                    '<strong>Aktualności</strong> — komunikaty z datą ważności, testy narzędzi, układy wpisu, RSS.',
                    '<strong>Moduły</strong> — BIP, newsletter, sklep, darowizny, wolontariat, formularze i inne.',
                    '<strong>Dostępność</strong> — pasek dostępności, ETR, skaner WCAG, deklaracja i zgłaszanie barier.',
                    '<strong>Wygląd i nawigacja</strong> — indywidualny szablon, mega menu, strona główna z sekcjami, sieć witryn.',
                    '<strong>Integracje i zaplecze</strong> — Przelewy24, SZO, Microsoft 365, harmonogram, kontrola stanu, eksport treści.',
                ])],
            'wersje' => ['Wersje',
                $p('weCMS ma <strong>wersję darmową</strong>, która wystarcza do prowadzenia strony organizacji, i <strong>wersję płatną</strong> — dla organizacji, które potrzebują płatności online, większej skali albo pracy zespołowej.')
                .$p('Dostępność, bezpieczeństwo i eksport treści są zawsze w wersji darmowej.')
                .'<table><caption>Porównanie wersji weCMS</caption><thead><tr><th scope="col">Obszar</th><th scope="col">Darmowa</th><th scope="col">Płatna</th></tr></thead><tbody><tr><th scope="row">Strony, aktualności, menu, biblioteka mediów, galeria, FAQ</th><td>Tak</td><td>Tak</td></tr><tr><th scope="row">Wszystkie funkcje dostępności, logowanie dwuetapowe, eksport treści</th><td>Tak</td><td>Tak</td></tr><tr><th scope="row">Szablony witryny</th><td>Domyślny i NGO</td><td>Wszystkie</td></tr><tr><th scope="row">Darowizny i sklep (Przelewy24)</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Newsletter, kreator formularzy, ankiety, kampanie</th><td>—</td><td>Tak</td></tr><tr><th scope="row">BIP, wolontariat, zgłoszenia współpracy</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Wiele witryn, grupy użytkowników, zadania, szablony treści, import DOCX</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Skaner WCAG stron, wyszukiwanie martwych linków</th><td>—</td><td>Tak</td></tr><tr><th scope="row">Pomoc techniczna</th><td>Dokumentacja</td><td>Zgłoszenia przez Helpdesk</td></tr></tbody></table>'
                .'<h2>Gdy licencja wygaśnie</h2>'
                .$p('Strona działa dalej. Treści z modułów płatnych zostają widoczne dla odwiedzających, a w panelu można je przeglądać — wyłączają się tylko nowe wpisy i akcje, takie jak wysyłka newslettera czy przyjmowanie płatności.')],
            'demo' => ['Demo',
                $p('<strong>Wersja demonstracyjna weCMS będzie dostępna niebawem.</strong>')
                .$p('Przygotowujemy instalację, w której będzie można zajrzeć do panelu redakcji i zobaczyć, jak prowadzi się stronę w weCMS — bez instalowania czegokolwiek. Gdy demo będzie gotowe, link pojawi się w tym miejscu.')
                .$p('Jeśli chcesz zobaczyć system wcześniej, napisz do nas — pokażemy go na spotkaniu online.')],
            'wymagania' => ['Wymagania',
                $p('weCMS działa na zwykłym hostingu współdzielonym albo na własnym serwerze. Instalator w przeglądarce sam sprawdza, czy serwer spełnia wymagania.')
                .'<h2>Serwer</h2>'.$list([
                    '<strong>PHP 8.4</strong> lub nowszy',
                    'Rozszerzenia PHP: PDO (SQLite lub MySQL), OpenSSL, Mbstring, Tokenizer, XML, Fileinfo oraz GD albo Imagick',
                    'Baza danych: <strong>SQLite</strong> (bez osobnego serwera) albo <strong>MySQL</strong>',
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
            'zamowienie' => ['Zamówienie',
                $p('Chcesz prowadzić stronę swojej organizacji na weCMS? Wypełnij formularz — odezwiemy się, żeby omówić potrzeby, wersję i szablon. Wysłanie formularza niczego jeszcze nie zamawia ani nie zobowiązuje do zapłaty.')
                .'[formularz:'.self::FORM_SLUG.']'],
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

        // Szczegóły funkcjonalności — podstrony „Funkcjonalności” (rozwijane w menu bocznym).
        $features = Page::where('slug', 'wecms-funkcjonalnosci')->firstOrFail();
        $h = fn (string $t) => '<h2>'.$t.'</h2>';
        $details = [
            'strony' => ['Strony i struktura',
                $p('Strony układają się w drzewo, które przeciągasz myszą albo przestawiasz z klawiatury. Każda strona ma typ (co zawiera), układ (jak wygląda) i poziom dostępu (kto ją widzi).')
                .$h('Typy stron').$list([
                    '<strong>Treść i informacja:</strong> standardowa, FAQ, dokumenty (grupy linków do plików i stron), słownik pojęć z indeksem liter, poradnik krok po kroku (poziomy trudności), regulamin z datą obowiązywania i spisem treści.',
                    '<strong>Organizacja:</strong> „O organizacji” z sekcjami do włączania i układania (wstęp, słowo od fundatora, liczby, wartości, historia, zespół, galeria, partnerzy, „My w mediach”), profil osoby, zespół, partnerzy i sponsorzy, strefa marki z osobnymi loginami.',
                    '<strong>Zapytanie ofertowe:</strong> termin składania ofert, kryteria, zmiany, wynik i status (przyjmowanie ofert, ocena, rozstrzygnięte, unieważnione).',
                    '<strong>Oferta i edukacja:</strong> oferta/usługa, cennik, studium przypadku, instytucja szkoleniowa, rekrutacja do projektu ze statusem naboru (wkrótce, trwa, ciągły, lista rezerwowa, wstrzymany, zakończony).',
                    '<strong>Kontakt i formularze:</strong> kontakt (formularz, dane, spotkania), formularz, harmonogram zajęć.',
                    '<strong>Nawigacja:</strong> strona z kafelkami, siatka kafelków z treścią, przekierowanie, własny link w menu, nagłówek w menu, strona wewnętrzna, strefa współpracownika.',
                ])
                .$h('7 układów strony').$list([
                    'Domyślny z nawigacją boczną, szeroki, z kolorowym banerem, landing z dużym nagłówkiem, portal (zdjęcie, kolumna boczna, galeria, kontakt), strona działu (slider, wpisy, nawigacja po podstronach) i minimalny bez nagłówka i stopki.',
                    'Sześć stylów nawigacji po podstronach: drzewo boczne, akordeon, panel boczny, zakładki nad treścią, drzewo działu do 5 poziomów i nawigacja kafelkowa.',
                ])
                .$h('Kto widzi stronę').$list([
                    'Publiczna, jak strona nadrzędna, chroniona hasłem, tylko dla zalogowanych przez Microsoft 365, tylko dla użytkowników panelu albo dla wybranych grup.',
                ])
                .$h('Publikacja').$list([
                    'Publikacja z wyprzedzeniem — strona pojawia się sama o ustalonej godzinie; podgląd szkicu przez link ważny 14 dni.',
                    '„Strona w przygotowaniu” jako pełny ekran albo pasek z komunikatem; wyłączenie strony z podaniem powodu na publicznej liście wyłączeń.',
                    'Oznaczenie treści jako archiwalnej, ukrycie na mapie strony, blokada edycji, strony systemowe chronione przed usunięciem.',
                    'Przypomnienia e-mail o terminach: koniec naboru za 3 dni, brak wyniku zapytania ofertowego tydzień po terminie i inne — każde wysyłane raz.',
                ])
                .$h('Dodatki do każdej strony').$list([
                    'Galeria i slider ze zdjęć strony (z tytułem, tekstem i przyciskiem na slajdach), panel zapisu na newsletter, kafelki.',
                    'Wersja łatwa do czytania (ETR), pola SEO z podglądem wyniku wyszukiwania i licznikiem znaków.',
                ])],
            'edytory' => ['Edytory i redakcja',
                $h('Edytor klasyczny').$list([
                    'TinyMCE z tabelami, akordeonami, wyszukiwaniem i zamianą, licznikiem słów i sprawdzaniem dostępności treści.',
                    'Menu „Wstaw”: przycisk CTA, notatka i ostrzeżenie, układ dwóch kolumn, dostępna tabela, galeria, formularz, kafelki, blok wielokrotnego użytku, tekst tylko dla czytników ekranu.',
                    'Linki wybierane z listy stron, aktualności i plików; import z plików Word (DOCX) i TXT.',
                    'Krótkie kody wstawiające do treści m.in. formularze, kafelki, klauzule RODO, listę naborów i zapytań ofertowych.',
                ])
                .$h('Edytor blokowy').$list([
                    'Bloki: akapit z wyróżnieniem, nagłówki z kotwicą, lista, cytat, zdjęcie (z opisem albo jako dekoracja, cztery szerokości), galeria, przyciski, ramki, kolumny, rozwijane sekcje, tabela z podpisem, separator, plik, osadzenie filmu.',
                    'Filmy z YouTube i Vimeo osadzane w trybie bez śledzenia odwiedzających.',
                ])
                .$h('Bezpieczna praca na treściach').$list([
                    'Historia zmian stron i aktualności z porównaniem wersji i przywracaniem.',
                    'Blokada równoczesnej edycji — system ostrzega, gdy ktoś inny edytuje ten sam element.',
                    'Edycja tekstów bezpośrednio na stronie, z tą samą kontrolą poprawności co w panelu.',
                    'Kosz z przywracaniem, szablony treści do wielokrotnego użycia.',
                ])
                .$h('Biblioteka mediów').$list([
                    'Foldery, tagi, autorzy; operacje zbiorcze: usuwanie, archiwizacja, przenoszenie; eksport i import paczek ZIP.',
                    'Zdjęcia z Unsplash z podpisem autora, import z OneDrive.',
                    'Audyt opisów alternatywnych: lista zdjęć bez opisu z możliwością uzupełnienia od razu.',
                ])],
            'zespol' => ['Praca zespołowa i bezpieczeństwo',
                $h('Role i uprawnienia').$list([
                    'Role: administrator, edytor treści, koordynator, redaktor PR i edytor BIP — każda z dostępem tylko do potrzebnych modułów.',
                    'Grupy użytkowników z listą dostępnych modułów, prawem do akceptacji, ograniczeniem do własnych treści albo do wybranych gałęzi drzewa stron.',
                ])
                .$h('Akceptacja treści').$list([
                    'Osoby bez prawa akceptacji wysyłają treść „do zatwierdzenia”; kolejka obejmuje strony i aktualności.',
                    'Zatwierdzenie albo odrzucenie z podaniem powodu, powiadomienia dla autora, przypomnienie co godzinę o treściach czekających ponad 12 godzin.',
                ])
                .$h('Organizacja pracy').$list([
                    'Zadania na tablicy (do zrobienia, w trakcie, zrobione) z priorytetem, terminem i przypomnieniem dzień wcześniej.',
                    'Kalendarz redakcyjny aktualności, centrum powiadomień, dziennik zdarzeń z filtrami, wyszukiwarka poleceń w panelu.',
                ])
                .$h('Logowanie').$list([
                    'Logowanie dwuetapowe: aplikacja z kodami albo klucz YubiKey.',
                    'Logowanie przez Microsoft 365 (także tryb „tylko Microsoft 365”) i Google; osobna strefa współpracownika z zaproszeniami.',
                ])],
            'aktualnosci' => ['Aktualności',
                $list([
                    '<strong>Typy wpisów:</strong> zwykły wpis; komunikat z datą ważności, który po niej sam trafia do archiwum; test narzędzia z plusami, minusami i oceną zgodności z WCAG 2.2 AA.',
                    '<strong>Układy wpisu:</strong> baner, duży baner, zdjęcie obok tekstu, bez zdjęcia, portal z kolumną boczną; punkt ostrości zdjęcia przy kadrowaniu.',
                    'Kategorie, tagi, kolorystyka wpisu według grupy docelowej.',
                    'Szybka edycja na liście, klonowanie, operacje zbiorcze, eksport do CSV, ostrzeżenie o podobnym tytule już w trakcie pisania.',
                    'Archiwum i kanał RSS (także dla jednej kategorii), wersja ETR, historia zmian.',
                ])],
            'moduly' => ['Moduły',
                $p('Moduły włączasz w ustawieniach — wyłączony moduł znika z panelu i ze strony.')
                .$h('Przejrzystość').$list([
                    '<strong>BIP:</strong> dokumenty w ustawowych kategoriach, rejestr zmian, instrukcja korzystania, osobne menu; może działać jako osobna witryna albo kierować do zewnętrznego BIP.',
                ])
                .$h('Ludzie').$list([
                    '<strong>Wolontariat:</strong> oferty według sześciu pytań (komu pomagamy, zadania, miejsce, czas, korzyści, jak się zgłosić), termin i automatyczne archiwum.',
                    '<strong>Landing:</strong> strona zapisu z prelegentami, agendą, własnymi polami formularza i eksportem zgłoszeń.',
                    '<strong>Zgłoszenia współpracy</strong> ze skrzynką odpowiedzi.',
                ])
                .$h('Pieniądze').$list([
                    '<strong>Darowizny:</strong> płatność przez Przelewy24, dane do przelewu i druk przelewu w PDF, lista ostatnich wpłat z opcją anonimowości, zgoda na newsletter.',
                    '<strong>Sklep:</strong> koszyk, kody rabatowe (procentowe lub kwotowe, z terminem i limitem użyć), płatność Przelewy24, link do pobrania wysyłany po opłaceniu.',
                    '<strong>Kampanie zbiórkowe:</strong> cel i zebrana kwota, daty, link do wpłat.',
                ])
                .$h('Komunikacja').$list([
                    '<strong>Newsletter:</strong> zapis z potwierdzeniem, rejestr zgód, listy i segmenty, edytor wiadomości, kampanie cykliczne, wysyłka e-mail, web push i SMS, statystyki otwarć i kliknięć.',
                    '<strong>Kreator formularzy:</strong> kilkanaście typów pól (w tym ocena 1–5 i numer KRS uzupełniający dane organizacji), kroki, wysyłka e-mailem lub do innego systemu, eksport zgłoszeń.',
                    '<strong>Ankiety</strong> na stronie głównej, <strong>podcasty</strong> (także odcinki dla subskrybentów).',
                ])
                .$h('Strona główna i treści stałe').$list([
                    'Slajder z hasłami i czasem wyświetlania, galeria, szybkie akcje, paski z tekstem i przyciskami, partnerzy, oś czasu historii, banery z harmonogramem i statystyką kliknięć, FAQ.',
                ])],
            'dostepnosc' => ['Dostępność',
                $p('Dostępność jest wbudowana w system, a nie dodawana na końcu. weCMS jest projektowany pod WCAG 2.2 na poziomie AA.')
                .$list([
                    '<strong>Pasek dostępności dla odwiedzających:</strong> wielkość tekstu, wysoki kontrast, tryb czarno-żółty, skala szarości, krój bezszeryfowy, odstępy między literami i wierszami, podkreślenie linków, mocniejszy fokus, zatrzymanie animacji.',
                    '<strong>Kontrast:</strong> kolory wybrane przez redakcję system sam koryguje tak, by tekst miał co najmniej 4,5:1.',
                    '<strong>Wersje łatwe do czytania (ETR)</strong> stron i aktualności.',
                    '<strong>Deklaracja dostępności</strong> i formularz zgłaszania barier z listą zgłoszeń w panelu.',
                    '<strong>Skaner WCAG</strong> wskazanych stron: język, tytuł, nagłówki, opisy zdjęć, opisy linków i pól formularzy, przyciski bez tekstu, kolejność tabulacji, punkty orientacyjne, podejrzenia za niskiego kontrastu.',
                    '<strong>Wyszukiwanie martwych linków</strong> z odnośnikiem do miejsca, w którym trzeba je poprawić.',
                    'Audyt opisów zdjęć w bibliotece mediów, karuzele z pauzą, ochrona przed spamem bez obrazkowych CAPTCHA (pytanie tekstowe).',
                ])],
            'wyglad' => ['Wygląd i nawigacja',
                $list([
                    '<strong>Indywidualny szablon:</strong> wygląd każdej organizacji piszemy osobno; na start są szablony bazowe dla fundacji, federacji i urzędów.',
                    '<strong>Nagłówek:</strong> kilka układów, m.in. z paskiem marki i z paskiem informacyjnym urzędu; kolor główny i trzy kolory dodatkowe.',
                    '<strong>Strona główna:</strong> sekcje (slajder, aktualności, ankieta i szybkie akcje, galeria, wpisy z Substack) układane przeciąganiem.',
                    '<strong>Menu:</strong> główne, w stopce i BIP; menu podstron budowane automatycznie; mega menu w trzech rozmiarach, z obrazkiem, własnymi linkami i przyciskami.',
                    '<strong>Sieć witryn:</strong> wiele witryn (np. ośrodków) w jednej instalacji, każda z własną domeną; przełącznik witryny w panelu.',
                    '<strong>Dla wyszukiwarek:</strong> przekierowania z licznikiem i importem z CSV, mapa strony XML i dla ludzi, wyszukiwarka w serwisie z wyróżnieniem trafień.',
                    'Baner zgody na cookies, powiadomienia web push, tryb serwisowy z komunikatem.',
                ])],
            'integracje' => ['Integracje i zaplecze',
                $list([
                    '<strong>Przelewy24</strong> dla darowizn i sklepu, z automatycznym potwierdzaniem płatności.',
                    '<strong>SZO:</strong> przekazywanie zgłoszeń z formularzy, rejestr darowizn, klauzule RODO importowane codziennie, komunikaty w strefie współpracownika.',
                    '<strong>Poczta:</strong> serwer, SMTP albo Microsoft 365, z wysyłką testową i edytowalnymi szablonami wiadomości.',
                    '<strong>Ochrona przed spamem:</strong> wbudowane zabezpieczenia i opcjonalnie CleanTalk; wyszukiwarka danych organizacji po numerze KRS.',
                    '<strong>Harmonogram zadań:</strong> archiwizacja, przypomnienia, płatności, synchronizacja z SZO — wykonywane automatycznie co kilka minut, co godzinę albo raz dziennie.',
                    '<strong>Kontrola stanu:</strong> baza, pamięć podręczna, dysk, kolejka, harmonogram i połączenia — w panelu i jednym poleceniem na serwerze.',
                    '<strong>Przenośność:</strong> eksport i import treści paczką ZIP między instalacjami.',
                    '<strong>Wdrożenia:</strong> skrypt z kopią baz, trybem serwisowym, migracjami i kontrolą po wdrożeniu; instalator w przeglądarce dla nowych instalacji.',
                ])],
        ];
        // Elementy dedykowane tylko dla FEER (działania, sprawozdania, oferty pracy, wydarzenia, mapa pomocy,
        // materiały, blog) nie są opisywane jako część weCMS — usuń dawną podstronę „Działania i projekty”.
        Page::where('slug', 'wecms-funkcje-dzialania')->delete();

        $order = 1;
        foreach ($details as $suffix => [$title, $html]) {
            Page::updateOrCreate(
                ['slug' => 'wecms-funkcje-'.$suffix],
                [
                    'title' => $title,
                    'type' => 'standard',
                    'content' => $html,
                    'parent_id' => $features->id,
                    'is_published' => true,
                    'order' => $order++,
                ],
            );
        }

        $this->screenshots();
    }

    /**
     * Zrzuty ekranu feer.org.pl (strona działa na weCMS) pod treścią podstron — pliki w
     * database/seeders/assets/wecms/, podpinane jako zdjęcia podstrony (z opisem alternatywnym
     * i podpisem). Przy każdym uruchomieniu zrzuty są podmieniane, bez duplikatów.
     */
    private function screenshots(): void
    {
        $dir = database_path('seeders/assets/wecms');
        $map = [
            'wecms-o-cms' => [
                ['panel-pulpit.jpg', 'Pulpit panelu weCMS z podsumowaniem treści serwisu', 'Panel redakcji — pulpit'],
                ['strona-glowna.jpg', 'Strona główna feer.org.pl z sliderem i skrótami', 'Strona główna feer.org.pl — działa na weCMS'],
            ],
            'wecms-funkcje-strony' => [
                ['panel-drzewo-stron.jpg', 'Drzewo stron w panelu z kartą wybranej strony i listą podstron', 'Drzewo stron — przeciąganie, podstrony, szybkie akcje'],
                ['panel-edycja-strony.jpg', 'Formularz edycji strony z zakładkami Treść, Typ i układ, Publikacja, SEO, Pliki, Galeria i ETR', 'Edycja strony'],
                ['strona-z-menu-bocznym.jpg', 'Strona z menu bocznym podstron', 'Menu boczne podstron na stronie'],
            ],
            'wecms-funkcje-edytory' => [
                ['panel-edycja-strony.jpg', 'Edytor klasyczny z menu Wstaw, Wstaw link, Import i sprawdzaniem dostępności', 'Edytor klasyczny i przejście do edytora blokowego'],
                ['panel-biblioteka-mediow.jpg', 'Biblioteka mediów z folderami i operacjami zbiorczymi', 'Biblioteka mediów'],
            ],
            'wecms-funkcje-zespol' => [
                ['panel-kalendarz.jpg', 'Kalendarz redakcyjny w panelu', 'Kalendarz redakcyjny'],
                ['panel-dziennik.jpg', 'Dziennik zdarzeń z filtrami', 'Dziennik zdarzeń — kto i kiedy co zmienił'],
            ],
            'wecms-funkcje-aktualnosci' => [
                ['panel-edycja-aktualnosci.jpg', 'Formularz edycji aktualności z zakładkami', 'Edycja aktualności w panelu'],
                ['aktualnosci.jpg', 'Lista aktualności z wyróżnionym wpisem i kategoriami', 'Lista aktualności na stronie'],
                ['artykul.jpg', 'Pojedyncza aktualność', 'Strona wpisu'],
            ],
            'wecms-funkcje-moduly' => [
                ['panel-newsletter.jpg', 'Pulpit newslettera z kampaniami, subskrybentami i statystykami', 'Newsletter — kampanie e-mail, push i SMS'],
                ['panel-kreator-formularzy.jpg', 'Kreator formularzy z typami pól i podglądem', 'Kreator formularzy — pole KRS uzupełnia dane organizacji'],
                ['darowizny.jpg', 'Strona darowizny online z kwotami i danymi do przelewu', 'Darowizny online (Przelewy24)'],
                ['formularz.jpg', 'Wieloetapowy formularz zgłoszenia z krokami i polem numeru KRS z przyciskiem Pobierz dane z KRS', 'Formularz z kreatora na stronie — kroki i pobieranie danych z KRS'],
                ['bip.jpg', 'Strona główna Biuletynu Informacji Publicznej z wyszukiwarką i menu dokumentów', 'BIP — osobna witryna w tej samej instalacji'],
            ],
            'wecms-funkcje-dostepnosc' => [
                ['panel-skaner-wcag.jpg', 'Skaner WCAG w panelu', 'Skaner WCAG wskazanej strony'],
                ['panel-audyt-alt.jpg', 'Audyt opisów alternatywnych zdjęć w bibliotece mediów', 'Audyt opisów zdjęć'],
                ['pasek-dostepnosci.jpg', 'Rozwinięty pasek dostępności z wielkością tekstu, odstępami, kontrastem, trybem czarno-żółtym i szarością', 'Pasek dostępności dla odwiedzających'],
                ['deklaracja-dostepnosci.jpg', 'Deklaracja dostępności generowana z ustawień serwisu', 'Deklaracja dostępności'],
            ],
            'wecms-funkcje-wyglad' => [
                ['mega-menu.jpg', 'Rozwinięte mega menu z kategoriami i przyciskami', 'Mega menu na stronie'],
                ['panel-menu.jpg', 'Edycja menu nawigacji w panelu', 'Menu nawigacji w panelu'],
                ['panel-moduly.jpg', 'Ustawienia strony w panelu z sekcjami ustawień', 'Ustawienia strony'],
                ['wyszukiwarka.jpg', 'Wyniki wyszukiwania z filtrami zakresu i wynikami pogrupowanymi według typu treści', 'Wyszukiwarka serwisu'],
            ],
        ];

        foreach ($map as $slug => $shots) {
            $page = Page::where('slug', $slug)->first();
            if (! $page) {
                continue;
            }

            $page->images()->get()->each->delete();
            $figures = '';
            foreach ($shots as $i => [$file, $alt, $caption]) {
                if (! is_file("{$dir}/{$file}")) {
                    continue;
                }
                $image = $page->images()->create(['alt' => $alt, 'caption' => $caption, 'order' => $i + 1]);
                $image->addMedia("{$dir}/{$file}")->preservingOriginal()->toMediaCollection('image');
                // Ścieżka względna (/storage/...), niezależna od domeny i portu instalacji.
                $src = parse_url((string) $image->fresh()->image_url, PHP_URL_PATH);
                $figures .= '<figure><img src="'.e($src).'" alt="'.e($alt).'" loading="lazy"><figcaption>'.e($caption).'</figcaption></figure>';
            }

            $base = preg_replace('~<h2>Jak to wygląda</h2>.*$~s', '', (string) $page->content);
            $page->update(['content' => $figures !== '' ? $base.'<h2>Jak to wygląda</h2>'.$figures : $base]);
        }

    }

    /** Formularz zamówienia weCMS (kreator formularzy). Zakładany raz — zmiany z panelu zostają. */
    private function orderForm(): void
    {
        if (FormDefinition::withTrashed()->where('slug', self::FORM_SLUG)->exists()) {
            return;
        }

        $field = fn (string $uid, string $label, string $type, array $extra = []) => $extra + [
            'uid' => $uid, 'label' => $label, 'type' => $type, 'required' => false,
            'placeholder' => null, 'options' => null, 'help_text' => null,
        ];

        FormDefinition::create([
            'title' => 'Zamówienie weCMS',
            'slug' => self::FORM_SLUG,
            'description' => 'Zgłoszenie chęci wdrożenia weCMS w organizacji.',
            'is_active' => true,
            'fields' => [
                $field('wcmsstp1', 'Organizacja i kontakt', 'step', ['help_text' => 'Kto zamawia i jak się z Tobą skontaktować?']),
                $field('wcmsname', 'Imię i nazwisko', 'text', ['required' => true, 'placeholder' => 'np. Anna Kowalska']),
                $field('wcmskrs0', 'Numer KRS organizacji', 'krs', ['help_text' => 'Opcjonalnie — po wpisaniu uzupełnimy nazwę i stronę organizacji z rejestru.']),
                $field('wcmsorg0', 'Nazwa organizacji', 'text', ['required' => true, 'placeholder' => 'np. Fundacja Otwarte Drzwi', 'krs_fill' => 'nazwa']),
                $field('wcmstype', 'Rodzaj organizacji', 'select', ['required' => true, 'options' => 'Fundacja, Stowarzyszenie, Federacja lub sieć organizacji, Gmina lub urząd, Inna']),
                $field('wcmsmail', 'Adres e-mail', 'email', ['required' => true, 'placeholder' => 'np. anna@fundacja.org.pl']),
                $field('wcmstel0', 'Numer telefonu', 'tel', ['help_text' => 'Opcjonalnie.']),
                $field('wcmswww0', 'Obecna strona organizacji', 'text', ['placeholder' => 'np. https://fundacja.org.pl', 'krs_fill' => 'www', 'help_text' => 'Jeśli macie stronę — pomożemy przenieść treści.']),

                $field('wcmsstp2', 'Zamówienie', 'step', ['help_text' => 'Czego potrzebujecie? Jeśli nie wiesz — zaznacz „Nie wiem”, doradzimy.']),
                $field('wcmsver0', 'Wersja weCMS', 'radio', ['required' => true, 'options' => 'Darmowa, Płatna, Nie wiem — prosimy o doradzenie']),
                $field('wcmsmods', 'Których modułów potrzebujecie?', 'checkboxes', ['options' => 'BIP, Newsletter, Darowizny online, Sklep, Wolontariat, Kreator formularzy, Wiele witryn (np. ośrodki)', 'help_text' => 'Zaznacz wszystkie, które Was interesują.']),
                $field('wcmstpl0', 'Szablon strony', 'radio', ['required' => true, 'options' => 'Mamy identyfikację wizualną (logo, kolory), Potrzebujemy pomocy z wyglądem, Nie wiem', 'help_text' => 'Szablon piszemy indywidualnie dla każdej organizacji.']),
                $field('wcmsmove', 'Przeniesienie treści ze starej strony', 'radio', ['options' => 'Tak, Nie, Nie mamy jeszcze strony']),
                $field('wcmswhen', 'Kiedy strona powinna ruszyć?', 'text', ['placeholder' => 'np. do końca stycznia']),
                $field('wcmsnote', 'Dodatkowe informacje', 'textarea', ['placeholder' => 'Czego oczekujecie od strony? Ile osób będzie ją prowadzić?']),

                $field('wcmsstp3', 'Zgoda', 'step'),
                $field('wcmsrodo', 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu obsługi zamówienia weCMS i kontaktu w tej sprawie.', 'consent', ['required' => true]),
            ],
            'settings' => [
                'notification_email' => 'ziemowit.gil@feer.org.pl',
                'notification_subject' => 'Nowe zamówienie weCMS',
                'confirmation_message' => 'Dziękujemy! Otrzymaliśmy zgłoszenie — odezwiemy się w ciągu 3 dni roboczych, żeby omówić wdrożenie weCMS.',
                'delivery' => 'email',
                'reply_to_submitter' => true,
                'send_copy_to_submitter' => true,
                'submitter_copy_message' => 'Dziękujemy za zgłoszenie. Poniżej kopia przesłanych informacji.',
            ],
        ]);
    }
}
