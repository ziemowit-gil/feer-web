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
        'hero' => ['Powitanie', 'Góra strony: nazwa, hasło, przyciski i obrazek (domyślnie logo).'],
        'mission' => ['Misja i obszary', 'Trzy punkty misji i cztery obszary działania.'],
        'social' => ['Social media', 'Duże karty z profilami z Ustawienia → Media i BIP. Bez ustawionych profili sekcja się nie pokazuje.'],
        'offer' => ['Oferta warsztatów', 'Lista warsztatów z ceną i przyciskiem do zapytania o termin.'],
        'why' => ['Dlaczego my', 'Wyróżniki i opis, jak pracujemy.'],
        'gallery' => ['Galeria', 'Zdjęcia z modułu Galeria (do 8). Bez zdjęć sekcja się nie pokazuje.'],
        'team' => ['Zespół', 'Osoby prowadzące warsztaty.'],
        'reviews' => ['Opinie', 'Opinie uczestników.'],
        'support' => ['Jak wesprzeć', '1,5% podatku, darowizny, warsztaty odpłatne.'],
        'contact' => ['Kontakt', 'Telefon i e-mail z Ustawienia → Kontakt oraz przycisk do formularza.'],
    ];

    /**
     * Klucz => [sekcja, etykieta w panelu, typ pola, wartość domyślna].
     */
    public const DEFS = [
        // Powitanie
        'hero_kicker' => ['hero', 'Napis nad nagłówkiem (puste = nazwa witryny)', 'line', ''],
        'hero_title' => ['hero', 'Nagłówek strony głównej (H1)', 'line', 'Warsztaty kreatywne i twórcze'],
        'hero_text' => ['hero', 'Tekst pod nagłówkiem', 'text', 'Prowadzi je terapeuta zajęciowy. Dojeżdżamy do szkół, przedszkoli, szpitali i domów pomocy społecznej w Krakowie i okolicach.'],
        'hero_points' => ['hero', 'Krótkie hasła pod tekstem (jedno w wierszu)', 'list', "Z dojazdem do placówki\nMateriały w cenie\nDla dzieci, dorosłych i seniorów"],
        'hero_button' => ['hero', 'Główny przycisk — napis', 'line', 'Zobacz relacje z naszych działań'],
        'hero_button_url' => ['hero', 'Główny przycisk — adres (puste = Facebook z ustawień)', 'url', ''],
        'hero_button2' => ['hero', 'Drugi przycisk — napis', 'line', 'Oferta warsztatów'],
        'hero_button2_url' => ['hero', 'Drugi przycisk — adres', 'url', '#oferta'],
        'hero_image' => ['hero', 'Obrazek (adres z Multimediów, puste = logo)', 'url', ''],
        'hero_image_alt' => ['hero', 'Opis obrazka dla czytników (puste = obrazek dekoracyjny)', 'line', ''],

        // Misja i obszary
        'mission_title' => ['mission', 'Nagłówek (H2)', 'line', 'Naszą misją jest'],
        'mission_items' => ['mission', 'Punkty misji (jeden w wierszu)', 'list', "Realizacja warsztatów terapeutycznych m.in. w szpitalach i Domach Pomocy Społecznej.\nZakup i dystrybucja darów rzeczowych dla osób znajdujących się w trudnej sytuacji życiowej.\nRozwijanie kreatywności oraz poczucia sprawstwa poprzez różnorodne warsztaty tematyczne."],
        'mission_text' => ['mission', 'Tekst przed obszarami', 'text', 'Twórcze działanie, kontakt z drugim człowiekiem i poczucie sprawczości potrafią realnie poprawić jakość życia — niezależnie od wieku czy stanu zdrowia. Wspieramy dzieci, dorosłych i seniorów, koncentrując się na czterech kluczowych obszarach.'],
        'areas' => ['mission', 'Obszary działania (Tytuł | opis)', 'list', "Poprawa zdrowia psychicznego i fizycznego | Prowadzimy zajęcia, które pomagają redukować stres, wzmacniać koncentrację, usprawniać motorykę i budować poczucie bezpieczeństwa.\nRozwój umiejętności i kreatywności | Uczymy nowych technik rękodzielniczych, rozwijamy wyobraźnię i pokazujemy, jak twórczo spędzać czas — niezależnie od ograniczeń.\nIntegracja społeczna | Tworzymy przestrzeń do spotkań, rozmów i współpracy. Wspólne działania budują relacje i przeciwdziałają samotności.\nEdukacja ekologiczna | Promujemy ideę zero waste, uczymy recyklingu i pokazujemy, jak nadawać rzeczom drugie życie."],

        // Oferta
        'offer_title' => ['offer', 'Nagłówek (H2)', 'line', 'Warsztaty tematyczne z dojazdem'],
        'offer_text' => ['offer', 'Tekst wstępu', 'text', 'Naszą misję realizujemy m.in. dzięki odpłatnym, mobilnym warsztatom w szkołach, przedszkolach, domach kultury, świetlicach, na festiwalach i wszędzie tam, gdzie są ludzie gotowi na inspirujące spotkanie.'],
        'offer_price' => ['offer', 'Cena', 'line', 'Cena od 30 zł za osobę'],
        'offer_price_note' => ['offer', 'Dopisek przy cenie', 'line', 'zależna od liczby osób oraz lokalizacji'],
        'offer_items' => ['offer', 'Warsztaty (Tytuł | opis)', 'list', "Laleczki zapachowe | Inspirujące warsztaty projektowania pachnących laleczek z naturalnym suszem.\nBiżuteria z filcu | Interaktywny kurs tworzenia unikatowej biżuterii z wełny czesankowej.\nKolaż botaniczny | Obrazy wprost z natury. Nie musisz mieć zdolności plastycznych, wystarczy rozbudzić wyobraźnię.\nLaleczki ze sznurka | Co potrafią makramowe supełki, czyli całkowicie zaplątane zajęcia ze sznurkiem w roli głównej.\nChoinkowe bonsai | Świąteczne warsztaty sadzenia oraz dekorowania bożonarodzeniowego mini drzewka.\nDekorowanie bombek | Magiczne zajęcia ozdabiania bombek różnymi metodami oraz technikami.\nWianki wielkanocne | Wiosenne zajęcia rozwijające zainteresowania z wykorzystaniem wielkanocnych dodatków.\nKompozycje kwiatowe | Wielkanocne tworzenie kompozycji z żywych kwiatów, przepełnione kolorami oraz wiosenną atmosferą.\nDekorowanie jajek gipsowych | Wiosenne zajęcia ozdabiania jajek metodą decoupage z wykorzystaniem suszonych kwiatów.\nJęzyk migowy | Wprowadzenie do języka migowego, nauka podstawowych zwrotów oraz alfabetu.\nŚwiece sojowe | Relaksujące sesje tworzenia dekoracyjnych świec z ekologicznych materiałów.\nKomponowanie miodów smakowych | Nauka poprzez doświadczanie produktów pszczelich. Stwórz miód z ulubionymi dodatkami.\nLaleczki motanki | Zajmujące warsztaty tworzenia tradycyjnych lalek słowiańskich w duchu zero waste.\nPerfumy | Pachnące zajęcia komponowania personalizowanych perfum z olejkami eterycznymi.\nKule musujące | Sensoryczna zabawa polegająca na przygotowaniu magicznych, buzujących w wodzie kul.\nMydełka glicerynowe | Kreatywne warsztaty tworzenia dekoracyjnych mydełek z naturalnych składników."],
        'offer_button' => ['offer', 'Przycisk — napis', 'line', 'Zapytaj o termin'],
        'offer_button_url' => ['offer', 'Przycisk — adres (puste = strona kontaktu)', 'url', ''],

        // Dlaczego my
        'why_title' => ['why', 'Nagłówek (H2)', 'line', 'Co nas wyróżnia?'],
        'why_items' => ['why', 'Wyróżniki (Tytuł | opis)', 'list', "Mobilność | Dojeżdżamy do Twojej placówki, firmy itp. Ty wybierasz miejsce, a my zajmujemy się resztą.\nMateriały w cenie | Zapewniamy wszystkie potrzebne materiały oraz narzędzia.\nIndywidualne podejście | Dopasowujemy program do wieku oraz umiejętności uczestników.\nRóżnorodność zajęć | Oferujemy warsztaty na każdą okazję: od dekoracji sezonowych przez zabawki aż po tworzenie miodu smakowego czy kosmetyków naturalnych.\nProfesjonalna kadra | Nasze warsztaty prowadzi terapeuta zajęciowy oraz lingwista z bogatym doświadczeniem.\nRozwój umiejętności | Trenujesz kreatywność, odnajdujesz w sobie pierwiastek twórczy, usprawniasz procesy myślowe, a przy okazji dobrze się bawisz!\nElastyczność terminów | Dopasowujemy termin i godzinę zajęć do Twoich potrzeb.\nBezpieczeństwo | Pracujemy zgodnie z aktualnymi standardami sanitarnymi, dbając o zdrowie uczestników. Posiadamy zaświadczenie o niekaralności."],
        'how_title' => ['why', 'Nagłówek „Jak pracujemy” (H3)', 'line', 'Jak pracujemy?'],
        'how_text' => ['why', 'Tekst „Jak pracujemy”', 'text', 'Prowadzimy warsztaty w grupach, indywidualnie oraz online, dostosowując się do potrzeb uczestników. Każde spotkanie oparte jest na kreatywności, interakcji i współpracy. Dbamy o przyjazną atmosferę, zachęcamy do eksperymentowania i odkrywania własnych talentów. Jesteśmy otwarci na nowe pomysły.'],

        // Social media
        'social_title' => ['social', 'Nagłówek (H2)', 'line', 'Zobacz, co ostatnio tworzyliśmy'],
        'social_text' => ['social', 'Tekst', 'text', 'Relacje z warsztatów, zdjęcia prac uczestników i zapowiedzi publikujemy na bieżąco w mediach społecznościowych. Zajrzyj tam i dołącz do nas.'],

        // Galeria
        'gallery_title' => ['gallery', 'Nagłówek (H2)', 'line', 'Z naszych warsztatów'],

        // Zespół
        'team_title' => ['team', 'Nagłówek (H2)', 'line', 'Poznaj nas'],
        'team_text' => ['team', 'Tekst', 'text', 'Wierzymy, że każdy ma w sobie ogromny potencjał — wystarczy stworzyć mu przestrzeń, by mógł go odkrywać.'],
        'team_items' => ['team', 'Osoby (Imię i nazwisko | rola | krótki opis)', 'list', "Anna Mossór-Koper | Terapeuta zajęciowy, koordynator zajęć | Prowadzi warsztaty rozwijające motorykę, sensorykę oraz sferę emocjonalną. Stawia na kreatywność, wzrost poczucia sprawstwa i kształtowanie zdrowych nawyków.\nSławomir Koper | Instruktor zajęć, nauczyciel języków obcych | Z zamiłowania ogrodnik i miłośnik pszczół. Stawia na naukę poprzez zabawę i zachęca uczestników do nauki języków."],

        // Opinie
        'reviews_title' => ['reviews', 'Nagłówek (H2)', 'line', 'Co mówią o nas uczestnicy?'],
        'reviews_items' => ['reviews', 'Opinie (Treść | podpis)', 'list', "Moi uczniowie wzięli udział już w kilku warsztatach, zawsze byłam pod wrażeniem zaangażowania oraz pomysłowości. W trakcie zajęć przygotowaliśmy prezenty m.in. na Dzień Mamy i Taty. Ogromny plus za mobilność, w końcu nie musimy wydawać pieniędzy na przejazdy :) | Maria\nNiedawno uczestniczyłam w warsztacie tworzenia lalek motanek podczas pikniku z okazji Dnia Dziecka. Byłam pod wrażeniem recyklingu tkanin i promowania praktyk zero waste. Dobra robota! | Magda\nWyjątkowe i inspirujące warsztaty. Nasze przedszkolaki zrobiły mydła na Dzień Babci i Dziadka. Wszyscy byli zachwyceni, zarówno dzieci, jak i obdarowani dziadkowie. Polecam każdemu! | Justyna"],
        'reviews_button' => ['reviews', 'Przycisk — napis', 'line', 'Więcej opinii na Facebooku'],
        'reviews_url' => ['reviews', 'Przycisk — adres (puste = Facebook z ustawień)', 'url', ''],

        // Jak wesprzeć
        'support_title' => ['support', 'Nagłówek (H2)', 'line', 'Jak możesz wesprzeć nasze działania?'],
        'support_text' => ['support', 'Tekst', 'text', "Fundacja boryka się z brakiem finansowania, a zapotrzebowanie na bezpłatną terapię zajęciową rośnie z każdym miesiącem. Szpitale i Domy Pomocy Społecznej proszą o wsparcie, którego nie zawsze jesteśmy w stanie udzielić.\n\nDziękujemy, że jesteś częścią naszej misji!"],
        'support_items' => ['support', 'Sposoby wsparcia (Tytuł | opis | adres)', 'list', "1,5% podatku | Wpisz nas w zeznaniu PIT — to nic nie kosztuje. | \nDarowizna | Darowiznę możesz odliczyć od podatku, rozliczając PIT. | \nZamów warsztaty | Z odpłatnych warsztatów mobilnych finansujemy bezpłatne zajęcia w szpitalach i DPS-ach. | #oferta"],

        // Kontakt
        'contact_title' => ['contact', 'Nagłówek (H2)', 'line', 'Masz pytania? Napisz do nas!'],
        'contact_text' => ['contact', 'Tekst', 'text', 'Ustalimy temat, termin i miejsce warsztatów. Działamy w Krakowie i okolicach.'],
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
        $hidden = $this->own['_hidden'] ?? [];

        return is_array($hidden) ? array_values(array_intersect($hidden, array_keys(self::SECTIONS))) : [];
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
        if (! empty($texts['_sections'])) {
            $hidden = array_values(array_diff(array_keys(self::SECTIONS), (array) ($texts['_shown'] ?? [])));
        } else {
            $hidden = array_values(array_intersect((array) ($texts['_hidden'] ?? []), array_keys(self::SECTIONS)));
        }
        if ($hidden !== []) {
            $out['_hidden'] = $hidden;
        }

        return $out === [] ? null : $out;
    }
}
