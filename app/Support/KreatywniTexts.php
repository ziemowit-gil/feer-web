<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Treści strony głównej szablonu „kreatywni” (wizytówka fundacji „Kreatywni na co dzień”, wzór:
 * warsztatydladziecikrakow.pl) w jednym rejestrze. Kolejność: nadpisanie witryny (site_settings.kreatywni_texts)
 * → wartość domyślna z DEFS. Formularz w panelu (Ustawienia → Szablon) buduje się z tego samego rejestru, więc każdy
 * nowy tekst od razu jest edytowalny.
 *
 * Typy pól: line (jedna linia), text (akapity, pusta linia = nowy akapit), url (adres), list (jedna pozycja w wierszu,
 * części rozdzielone „|”, np. „Tytuł | opis”). Ukryte sekcje: klucz `_hidden` (lista kluczy z SECTIONS).
 */
final class KreatywniTexts
{
    /** Sekcje strony głównej w kolejności wyświetlania: klucz => [nazwa w panelu, opis]. */
    public const SECTIONS = [
        'hero' => ['Slider na górze', 'Slajd 1: nazwa, hasło, przyciski i obrazek (domyślnie logo). Slajd 2: „Naszą misją jest” z punktami misji.'],
        'mission' => ['Obszary działania', 'Krótki wstęp i cztery obszary działania.'],
        'social' => ['Social media', 'Duże karty z profilami z Ustawienia → Media i BIP. Bez ustawionych profili sekcja się nie pokazuje.'],
        'offer' => ['Oferta warsztatów', 'Lista warsztatów z ceną i przyciskiem do zapytania o termin.'],
        'why' => ['Dlaczego my', 'Wyróżniki i opis, jak pracujemy.'],
        'gallery' => ['Galeria', 'Zdjęcia z modułu Galeria (do 8). Bez zdjęć sekcja się nie pokazuje.'],
        'team' => ['Zespół', 'Osoby prowadzące warsztaty.'],
        'reviews' => ['Opinie', 'Opinie uczestników.'],
        'support' => ['Jak wesprzeć', '1,5% podatku, darowizny, warsztaty odpłatne.'],
        'contact' => ['Kontakt', 'Telefon i e-mail z Ustawienia → Kontakt oraz przycisk do formularza.'],
    ];

    /** Sekcje ukryte, dopóki redakcja ich nie włączy (wizytówka ma być krótka; zespół jest na stronie „O nas”). */
    public const DEFAULT_HIDDEN = ['team'];

    /**
     * Klucz => [sekcja, etykieta w panelu, typ pola, wartość domyślna].
     */
    public const DEFS = [
        // Powitanie
        'hero_kicker' => ['hero', 'Napis nad nagłówkiem (puste = nazwa witryny)', 'line', ''],
        'hero_title' => ['hero', 'Nagłówek strony głównej (H1)', 'line', 'Warsztaty kreatywne i twórcze'],
        'hero_text' => ['hero', 'Tekst pod nagłówkiem', 'text', 'Prowadzi je terapeuta zajęciowy. Przyjeżdżamy do Ciebie — Kraków i okolice.'],
        'hero_points' => ['hero', 'Krótkie hasła pod tekstem (jedno w wierszu)', 'list', "Z dojazdem do placówki\nMateriały w cenie\nDla dzieci, dorosłych i seniorów"],
        'hero_button' => ['hero', 'Główny przycisk — napis', 'line', 'Zobacz relacje z naszych działań'],
        'hero_button_url' => ['hero', 'Główny przycisk — adres (puste = Facebook z ustawień)', 'url', ''],
        'hero_button2' => ['hero', 'Drugi przycisk — napis', 'line', 'Oferta warsztatów'],
        'hero_button2_url' => ['hero', 'Drugi przycisk — adres', 'url', '#oferta'],
        'hero_image' => ['hero', 'Obrazek (adres z Multimediów, puste = logo)', 'url', ''],
        'hero_image_alt' => ['hero', 'Opis obrazka dla czytników (puste = obrazek dekoracyjny)', 'line', ''],

        // Slajd 2: misja
        'mission_title' => ['hero', 'Slajd 2 — nagłówek (puste pole = tekst domyślny; slajd znika, gdy nie ma punktów)', 'line', 'Naszą misją jest'],
        'mission_items' => ['hero', 'Slajd 2 — punkty misji (jeden w wierszu)', 'list', "Realizacja warsztatów terapeutycznych m.in. w szpitalach i Domach Pomocy Społecznej.\nZakup i dystrybucja darów rzeczowych dla osób znajdujących się w trudnej sytuacji życiowej.\nRozwijanie kreatywności oraz poczucia sprawstwa poprzez różnorodne warsztaty tematyczne."],
        // Obszary działania
        'areas_title' => ['mission', 'Nagłówek (H2)', 'line', 'W czym pomagamy'],
        'mission_text' => ['mission', 'Tekst przed obszarami', 'text', 'Wspieramy dzieci, dorosłych i seniorów w czterech obszarach.'],
        'areas' => ['mission', 'Obszary działania (Tytuł | krótki opis)', 'list', "Zdrowie psychiczne i fizyczne | Mniej stresu, lepsza koncentracja i sprawność dłoni.\nRozwój i kreatywność | Nowe techniki rękodzieła i twórczy czas.\nIntegracja społeczna | Spotkania i współpraca zamiast samotności.\nEdukacja ekologiczna | Zero waste i drugie życie rzeczy."],

        // Oferta
        'offer_title' => ['offer', 'Nagłówek (H2)', 'line', 'Warsztaty tematyczne z dojazdem'],
        'offer_text' => ['offer', 'Tekst wstępu', 'text', 'Przyjeżdżamy do szkół, przedszkoli, domów kultury, świetlic i na festyny.'],
        'offer_card_title' => ['offer', 'Karta cenowa — nazwa', 'line', 'Warsztaty mobilne'],
        'offer_price' => ['offer', 'Karta cenowa — cena', 'line', 'od 30 zł'],
        'offer_price_unit' => ['offer', 'Karta cenowa — jednostka', 'line', 'za osobę'],
        'offer_price_note' => ['offer', 'Karta cenowa — dopisek przy cenie', 'line', 'zależna od liczby osób oraz lokalizacji'],
        'offer_features' => ['offer', 'Karta cenowa — korzyści (jedna w wierszu)', 'list', "Dojazd do Twojej placówki\nWszystkie materiały w cenie\nProgram dopasowany do wieku\nZaświadczenie o niekaralności"],
        'offer_items' => ['offer', 'Warsztaty (Tytuł | grupa, np. Boże Narodzenie; opisy są na stronie Oferta)', 'list', "Laleczki zapachowe | Przez cały rok\nBiżuteria z filcu | Przez cały rok\nKolaż botaniczny | Przez cały rok\nLaleczki ze sznurka | Przez cały rok\nJęzyk migowy | Przez cały rok\nŚwiece sojowe | Przez cały rok\nMiody smakowe | Przez cały rok\nLaleczki motanki | Przez cały rok\nPerfumy | Przez cały rok\nKule musujące | Przez cały rok\nMydełka glicerynowe | Przez cały rok\nChoinkowe bonsai | Boże Narodzenie\nDekorowanie bombek | Boże Narodzenie\nWianki wielkanocne | Wielkanoc\nKompozycje kwiatowe | Wielkanoc\nDekorowanie jajek gipsowych | Wielkanoc"],
        'offer_button' => ['offer', 'Przycisk — napis', 'line', 'Zamów warsztaty'],
        'offer_button_url' => ['offer', 'Przycisk — adres (puste = strona kontaktu)', 'url', ''],

        // Dlaczego my
        'why_title' => ['why', 'Nagłówek (H2)', 'line', 'Co nas wyróżnia?'],
        'why_items' => ['why', 'Wyróżniki (Tytuł | krótki opis)', 'list', "Mobilność | Dojeżdżamy do Twojej placówki.\nMateriały w cenie | Przywozimy wszystko, co potrzebne.\nDla każdego wieku | Program dopasowany do grupy.\nBezpieczeństwo | Mamy zaświadczenie o niekaralności."],
        'how_title' => ['why', 'Nagłówek „Jak pracujemy” (H3)', 'line', 'Jak pracujemy?'],
        'how_text' => ['why', 'Tekst „Jak pracujemy” (puste = bez ramki)', 'text', ''],

        // Social media
        'social_title' => ['social', 'Nagłówek (H2)', 'line', 'Zobacz, co ostatnio tworzyliśmy'],
        'social_text' => ['social', 'Tekst', 'text', 'Relacje z warsztatów i zdjęcia prac publikujemy na bieżąco w mediach społecznościowych.'],

        // Galeria
        'gallery_title' => ['gallery', 'Nagłówek (H2)', 'line', 'Z naszych warsztatów'],

        // Zespół
        'team_title' => ['team', 'Nagłówek (H2)', 'line', 'Poznaj nas'],
        'team_text' => ['team', 'Tekst', 'text', 'Wierzymy, że każdy ma w sobie ogromny potencjał — wystarczy stworzyć mu przestrzeń, by mógł go odkrywać.'],
        'team_items' => ['team', 'Osoby (Imię i nazwisko | rola | krótki opis)', 'list', "Anna Mossór-Koper | Terapeuta zajęciowy, koordynator zajęć | Prowadzi warsztaty rozwijające motorykę, sensorykę oraz sferę emocjonalną. Stawia na kreatywność, wzrost poczucia sprawstwa i kształtowanie zdrowych nawyków.\nSławomir Koper | Instruktor zajęć, nauczyciel języków obcych | Z zamiłowania ogrodnik i miłośnik pszczół. Stawia na naukę poprzez zabawę i zachęca uczestników do nauki języków."],

        // Opinie
        'reviews_title' => ['reviews', 'Nagłówek (H2)', 'line', 'Co mówią o nas uczestnicy?'],
        'reviews_items' => ['reviews', 'Opinie (Treść | podpis)', 'list', "Ogromny plus za mobilność, w końcu nie musimy wydawać pieniędzy na przejazdy :) | Maria\nByłam pod wrażeniem recyklingu tkanin i promowania praktyk zero waste. Dobra robota! | Magda\nWyjątkowe i inspirujące warsztaty. Polecam każdemu! | Justyna"],
        'reviews_button' => ['reviews', 'Przycisk — napis', 'line', 'Więcej opinii na Facebooku'],
        'reviews_url' => ['reviews', 'Przycisk — adres (puste = Facebook z ustawień)', 'url', ''],

        // Jak wesprzeć
        'support_title' => ['support', 'Nagłówek (H2)', 'line', 'Jak możesz wesprzeć nasze działania?'],
        'support_text' => ['support', 'Tekst', 'text', 'Dzięki Tobie prowadzimy bezpłatne zajęcia w szpitalach i Domach Pomocy Społecznej.'],
        'support_items' => ['support', 'Sposoby wsparcia (Tytuł | krótki opis | adres)', 'list', "1,5% podatku | Wpisz nas w zeznaniu PIT. | \nDarowizna | Możesz ją odliczyć od podatku. | \nZamów warsztaty | Z nich finansujemy pomoc. | #oferta"],

        // Kontakt
        'contact_title' => ['contact', 'Nagłówek (H2)', 'line', 'Masz pytania? Napisz do nas!'],
        'contact_text' => ['contact', 'Tekst', 'text', 'Ustalimy temat, termin i miejsce warsztatów.'],
        'contact_button' => ['contact', 'Przycisk do formularza — napis', 'line', 'Napisz wiadomość'],
    ];

    /** @var array<string, mixed> */
    private array $own;

    private function __construct(private readonly SiteSetting $settings)
    {
        $own = $settings->getAttribute('kreatywni_texts');
        $this->own = is_array($own) ? $own : [];
    }

    public static function for(?SiteSetting $settings = null): self
    {
        return new self($settings ?? SiteSetting::current());
    }

    /** Tekst: własny z panelu (gdy niepusty) albo domyślny. */
    public function get(string $key): string
    {
        $own = $this->own[$key] ?? null;
        if (is_string($own) && trim($own) !== '') {
            return trim($own);
        }

        return (string) (self::DEFS[$key][3] ?? '');
    }

    /** Tekst podzielony na akapity (pusta linia = nowy akapit). */
    public function paragraphs(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', $this->get($key)) ?: []), fn ($p) => $p !== ''));
    }

    /**
     * Lista: każdy niepusty wiersz to pozycja, części rozdzielone „|” (brakujące uzupełnione pustymi napisami).
     *
     * @return array<int, array<int, string>>
     */
    public function rows(string $key, int $parts = 1): array
    {
        $rows = [];
        foreach (preg_split('/\R/u', $this->get($key)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = array_map('trim', explode('|', $line, $parts));
            $rows[] = array_pad($cells, $parts, '');
        }

        return $rows;
    }

    /** Czy sekcja jest włączona (nie została ukryta w panelu). */
    public function shows(string $section): bool
    {
        return ! in_array($section, $this->hidden(), true);
    }

    /** @return array<int, string> */
    public function hidden(): array
    {
        if (! array_key_exists('_hidden', $this->own) || ! is_array($this->own['_hidden'])) {
            return self::DEFAULT_HIDDEN;
        }

        return array_values(array_intersect($this->own['_hidden'], array_keys(self::SECTIONS)));
    }

    /** Wartość zapisana w panelu (do formularza), bez domyślnej. */
    public function ownValue(string $key): string
    {
        $own = $this->own[$key] ?? null;

        return is_string($own) ? $own : '';
    }

    /**
     * Adres z pola typu url: „#kotwica”, ścieżka w witrynie (/oferta → adres w bieżącej witrynie) albo pełny http(s).
     * Inne schematy (javascript:, data:) są odrzucane.
     */
    public function url(string $key, ?string $fallback = null): ?string
    {
        return self::href($this->get($key)) ?? $fallback;
    }

    public static function href(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '#')) {
            return $value;
        }
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }
        if (str_starts_with($value, '/')) {
            return site_href($value);
        }
        if (preg_match('#^(mailto|tel):#i', $value)) {
            return $value;
        }

        return null;
    }

    /** Pola sekcji do formularza w panelu: klucz => [etykieta, typ, domyślna]. */
    public static function fields(string $section): array
    {
        $out = [];
        foreach (self::DEFS as $key => [$group, $label, $type, $default]) {
            if ($group === $section) {
                $out[$key] = [$label, $type, $default];
            }
        }

        return $out;
    }

    /** Reguły walidacji dla formularza ustawień. */
    public static function rules(string $prefix = 'kreatywni_texts'): array
    {
        $sections = 'in:'.implode(',', array_keys(self::SECTIONS));
        $rules = [
            $prefix => ['nullable', 'array'],
            $prefix.'._sections' => ['nullable', 'boolean'],
            $prefix.'._hidden' => ['nullable', 'array'], $prefix.'._hidden.*' => ['string', $sections],
            $prefix.'._shown' => ['nullable', 'array'], $prefix.'._shown.*' => ['string', $sections],
        ];
        foreach (self::DEFS as $key => [, , $type]) {
            $rules[$prefix.'.'.$key] = match ($type) {
                'line' => ['nullable', 'string', 'max:255'],
                'url' => ['nullable', 'string', 'max:500', 'not_regex:/^\s*(javascript|data|vbscript):/i'],
                default => ['nullable', 'string', 'max:8000'],
            };
        }

        return $rules;
    }

    /**
     * Czyści dane z formularza: tylko znane klucze, przycięte napisy; wartość równa domyślnej albo pusta nie jest
     * zapisywana (dzięki temu poprawka tekstu domyślnego w kodzie dociera do witryn, które go nie zmieniały).
     */
    public static function clean(mixed $texts): ?array
    {
        if (! is_array($texts)) {
            return null;
        }
        $out = [];
        foreach (self::DEFS as $key => [, , , $default]) {
            $value = $texts[$key] ?? null;
            if (! is_string($value)) {
                continue;
            }
            $value = trim(str_replace("\r\n", "\n", $value));
            if ($value !== '' && $value !== trim($default)) {
                $out[$key] = $value;
            }
        }
        // Formularz w panelu wysyła zaznaczone sekcje (`_shown`) i znacznik `_sections`; ukryte = pozostałe.
        // Zapisujemy listę tylko wtedy, gdy różni się od domyślnej (także pustą — „pokaż wszystko”).
        if (! empty($texts['_sections'])) {
            $hidden = array_values(array_diff(array_keys(self::SECTIONS), (array) ($texts['_shown'] ?? [])));
        } elseif (array_key_exists('_hidden', $texts)) {
            $hidden = array_values(array_intersect((array) $texts['_hidden'], array_keys(self::SECTIONS)));
        } else {
            $hidden = self::DEFAULT_HIDDEN;
        }
        $sorted = $hidden;
        $default = self::DEFAULT_HIDDEN;
        sort($sorted);
        sort($default);
        if ($sorted !== $default) {
            $out['_hidden'] = $hidden;
        }

        return $out === [] ? null : $out;
    }
}
