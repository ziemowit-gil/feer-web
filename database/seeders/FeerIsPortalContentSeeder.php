<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;

/**
 * Typowe treści portalu instytucji szkoleniowo-zajęciowej (motyw FEER IS), domyślnie OPUBLIKOWANE jako strony przykładowe do edycji
 * (z PORTAL_DRAFT=1 jako szkice): strony „Dla osób”
 * i „Dla instytucji” z usługami (zakładki na stronie głównej), „O instytucji szkoleniowej” z podstronami (akredytacje,
 * regulamin, cennik), trenerzy, katalog szkoleń i trzy przykładowe szkolenia.
 *
 * Teksty są ogólne i nie zawierają cen, liczb ani nazw własnych. Regulamin i cennik to szkielety do sprawdzenia
 * przez osobę odpowiedzialną za zasady instytucji. Seeder niczego nie nadpisuje: istniejącą stronę uzupełnia tylko
 * wtedy, gdy jej treść jest pusta.
 *
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsPortalContentSeeder --force   (slug albo nazwa witryny; domyślnie „szkolenia”)
 */
class FeerIsPortalContentSeeder extends Seeder
{
    private SiteSetting $site;

    private int $created = 0;

    /** Z PORTAL_DRAFT=1 nowe strony powstają jako szkice; domyślnie są opublikowane, żeby menu nie prowadziło do 404. */
    private bool $draft = false;

    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }

        $this->site = $site;
        $this->draft = in_array((string) (getenv('PORTAL_DRAFT') ?: ($_SERVER['PORTAL_DRAFT'] ?? '')), ['1', 'true'], true);

        $this->persons();
        $this->institutions();
        $this->about();
        $this->trainersAndCatalog();

        $this->command?->info('Treści portalu szkoleniowego: utworzono lub uzupełniono '.$this->created.' stron ('.($this->draft ? 'szkice' : 'opublikowane, przykładowe do edycji').').');
    }

    private function persons(): void
    {
        $hub = $this->page('Dla osób', 'standard', '<p>Zajęcia dla osób indywidualnych: kursy, warsztaty i konsultacje. Jeśli nie wiesz, od czego zacząć, napisz do nas albo zajrzyj do katalogu szkoleń.</p><p>[podstrony]</p>', ['meta_description' => 'Kursy, warsztaty, zajęcia indywidualne i konsultacje dla osób indywidualnych.']);

        $this->service($hub, 1, 'Kursy i szkolenia otwarte', 'mi-school', 'Grupowe szkolenia z ustalonym programem i terminami, otwarte dla każdego.', '<p>Kursy mają ustalony program i terminy zapisów. Czas trwania, formę i cenę każdego kursu znajdziesz w katalogu szkoleń.</p>');
        $this->service($hub, 2, 'Zajęcia indywidualne', 'mi-person', 'Zajęcia jeden na jeden, dopasowane do Twojego tempa i potrzeb.', '<p>Na zajęciach indywidualnych pracujesz we własnym tempie i nad własnymi zadaniami. Zakres i termin ustalamy po rozmowie.</p>');
        $this->service($hub, 3, 'Warsztaty tematyczne', 'mi-groups', 'Krótkie spotkania poświęcone jednemu tematowi.', '<p>Warsztat trwa krótko i dotyczy jednego tematu. Terminy ogłaszamy w katalogu szkoleń i w aktualnościach.</p>');
        $this->service($hub, 4, 'Poradnictwo i konsultacje', 'mi-support_agent', 'Rozmowa o tym, jakie szkolenie lub wsparcie będzie dla Ciebie odpowiednie.', '<p>Jeśli nie wiesz, które szkolenie wybrać, porozmawiaj z nami. Pomożemy dobrać zakres i formę zajęć.</p>');
    }

    private function institutions(): void
    {
        $hub = $this->page('Dla instytucji', 'standard', '<p>Oferta dla firm, urzędów i organizacji: szkolenia zamknięte, szkolenia z dostępności i konsultacje. Zakres ustalamy po rozmowie o potrzebach Twojej instytucji.</p><p>[podstrony]</p>', ['meta_description' => 'Szkolenia zamknięte, szkolenia z dostępności, konsultacje i współpraca dla firm i instytucji.']);

        $this->service($hub, 1, 'Szkolenia zamknięte', 'mi-apartment', 'Szkolenie na zamówienie dla zespołu Twojej instytucji.', '<p>Szkolenie zamknięte prowadzimy dla jednej grupy, w terminie i miejscu, które ustalamy z Tobą. Program dopasowujemy do zadań uczestników.</p>');
        $this->service($hub, 2, 'Szkolenia z dostępności', 'mi-accessibility_new', 'Jak tworzyć dokumenty, strony i usługi dostępne dla wszystkich.', '<p>Szkolenia z dostępności pokazują, jak przygotować dokumenty, strony internetowe i obsługę klienta, żeby korzystały z nich także osoby z niepełnosprawnościami.</p>');
        $this->service($hub, 3, 'Konsultacje i audyty', 'mi-fact_check', 'Ocena dostępności i wskazanie zmian do wprowadzenia.', '<p>Konsultacja polega na przejrzeniu wybranych materiałów lub usług i wskazaniu, co warto poprawić. Zakres pracy omawiamy przed jej rozpoczęciem.</p>');
        $this->service($hub, 4, 'Współpraca z firmami szkoleniowymi', 'mi-handshake', 'Wspólne projekty, konsultacje materiałów i wsparcie merytoryczne.', '<p>Z firmami szkoleniowymi współpracujemy przy konsultowaniu materiałów, wsparciu merytorycznym i wspólnych projektach. Napisz do nas, jeśli chcesz o tym porozmawiać.</p>');
    }

    private function about(): void
    {
        $about = $this->page('O instytucji szkoleniowej', 'training_institution', <<<'HTML'
<p>Prowadzimy szkolenia i zajęcia dla osób indywidualnych oraz dla instytucji. Zajęcia odbywają się stacjonarnie, online i w formie hybrydowej, a ich zakres dopasowujemy do potrzeb uczestników.</p>
<h2>Jak wyglądają zajęcia</h2>
<p>Każde szkolenie ma opisany program, czas trwania i formę. Przed zapisem możesz zapytać o szczegóły, a przy zapisie opisać potrzebne dostosowania, na przykład czytnik ekranu, większą czcionkę albo asystenta.</p>
<h2>Kto prowadzi zajęcia</h2>
<p>Zajęcia prowadzą trenerzy, których profile znajdziesz na stronie Trenerzy. Przy każdym szkoleniu podajemy, kto je prowadzi.</p>
<h2>Dokumenty</h2>
<p>Akredytacje, regulamin szkoleń i cennik znajdziesz w podstronach poniżej.</p>
<p>[podstrony]</p>
HTML, ['meta_description' => 'Kim jesteśmy, jak pracujemy i gdzie znajdziesz akredytacje, regulamin i cennik szkoleń.']);

        $this->child($about, 1, 'Akredytacje i jakość', 'training_accreditations', <<<'HTML'
<p>Na tej stronie publikujemy wpisy, akredytacje i certyfikaty instytucji oraz wskaźniki jakości szkoleń.</p>
<p>Po szkoleniu zbieramy opinie uczestników i korzystamy z nich przy planowaniu kolejnych zajęć.</p>
HTML, ['meta_description' => 'Akredytacje, certyfikaty i opinie uczestników.'], ['lead' => 'Akredytacje, certyfikaty i opinie uczestników w jednym miejscu.']);

        $this->child($about, 2, 'Regulamin szkoleń', 'regulation', <<<'HTML'
<h2>Zapisy</h2>
<p>Zapisujesz się przez formularz przy wybranym szkoleniu. Po zgłoszeniu potwierdzamy udział i informujemy o kolejnych krokach. Gdy brakuje miejsc, zapisujemy Cię na listę rezerwową.</p>
<h2>Płatności</h2>
<p>Cenę i sposób płatności podajemy w opisie szkolenia. Szkolenia bezpłatne są oznaczone w katalogu.</p>
<h2>Rezygnacja</h2>
<p>Z udziału możesz zrezygnować, pisząc do nas. Warunki rezygnacji podajemy w opisie szkolenia.</p>
<h2>Obecność i zaświadczenia</h2>
<p>Zaświadczenie o ukończeniu wydajemy po udziale w zajęciach w zakresie opisanym w programie szkolenia.</p>
<h2>Dostosowania</h2>
<p>Przy zapisie możesz opisać potrzebne dostosowania. Staramy się je zapewnić, a gdy to niemożliwe, proponujemy inne rozwiązanie.</p>
<h2>Dane osobowe</h2>
<p>Dane z formularza zapisu wykorzystujemy do zorganizowania szkolenia. Szczegóły podajemy w klauzuli informacyjnej przy formularzu.</p>
<h2>Uwagi i reklamacje</h2>
<p>Uwagi i reklamacje możesz zgłosić pisemnie na adres kontaktowy. Rozpatrujemy je i odpowiadamy pisemnie.</p>
HTML, ['meta_description' => 'Zasady zapisów, płatności, rezygnacji, zaświadczeń i reklamacji.']);

        $this->child($about, 3, 'Cennik szkoleń', 'pricing', <<<'HTML'
<p>Cenę szkolenia podajemy w jego opisie, razem z informacją, co obejmuje. Część zajęć jest bezpłatna.</p>
<p>Dla instytucji przygotowujemy wycenę po rozmowie o zakresie szkolenia.</p>
HTML, ['meta_description' => 'Jak podajemy ceny szkoleń i kiedy przygotowujemy wycenę.']);
    }

    private function trainersAndCatalog(): void
    {
        $this->page('Trenerzy', 'standard', '<p>Poznaj osoby, które prowadzą zajęcia. Przy profilu trenera znajdziesz jego specjalizacje, kwalifikacje i prowadzone szkolenia.</p><p>[podstrony]</p>', ['meta_description' => 'Trenerzy i prowadzący zajęcia.']);

        $catalog = $this->page('Katalog szkoleń', 'training_catalog', '', ['meta_description' => 'Wybierz szkolenie, termin i formę zajęć.'], ['lead' => 'Wybierz szkolenie, termin i formę, która Ci pasuje. Przy zapisie możesz opisać potrzebne dostosowania.', 'show_slider' => '1']);

        $this->offer($catalog, 1, 'Podstawy obsługi komputera', [
            'category' => 'Komputer', 'mode' => 'stationary', 'level' => 'podstawowy', 'featured' => '1',
            'lead' => 'Pierwsze kroki z komputerem: uruchamianie programów, praca z plikami i korzystanie z internetu.',
            'audience' => [['text' => 'Osoby zaczynające pracę z komputerem']],
            'outcomes' => [['text' => 'Uruchomisz program i zapiszesz plik'], ['text' => 'Znajdziesz informacje w internecie'], ['text' => 'Skorzystasz z poczty elektronicznej']],
            'program' => [['title' => 'Pierwsze kroki', 'text' => 'Włączanie komputera, praca z myszą i klawiaturą.'], ['title' => 'Pliki i foldery', 'text' => 'Zapisywanie, otwieranie i wyszukiwanie plików.'], ['title' => 'Internet i poczta', 'text' => 'Przeglądarka, wyszukiwanie informacji i poczta elektroniczna.']],
            'prerequisites' => 'Nie potrzebujesz wcześniejszego doświadczenia.',
        ]);
        $this->offer($catalog, 2, 'Dostępne dokumenty cyfrowe', [
            'category' => 'Dostępność', 'mode' => 'online', 'level' => 'podstawowy', 'featured' => '1', 'closed_available' => '1',
            'lead' => 'Jak przygotować dokumenty Word i PDF, z których skorzystają także osoby używające czytników ekranu.',
            'audience' => [['text' => 'Pracownicy administracji i organizacji'], ['text' => 'Osoby przygotowujące materiały dla odbiorców']],
            'outcomes' => [['text' => 'Ustawisz style i nagłówki w dokumencie'], ['text' => 'Dodasz tekst alternatywny do obrazów'], ['text' => 'Sprawdzisz dostępność gotowego pliku']],
            'program' => [['title' => 'Struktura dokumentu', 'text' => 'Style, nagłówki i listy.'], ['title' => 'Obrazy, tabele i linki', 'text' => 'Teksty alternatywne, proste tabele i opisy linków.'], ['title' => 'Sprawdzanie dostępności', 'text' => 'Narzędzia do kontroli dokumentu i poprawki.']],
        ]);
        $this->offer($catalog, 3, 'Obsługa smartfona dla początkujących', [
            'category' => 'Urządzenia mobilne', 'mode' => 'hybrid', 'level' => 'podstawowy',
            'lead' => 'Podstawowe funkcje smartfona: połączenia, wiadomości, aparat i aplikacje.',
            'audience' => [['text' => 'Osoby, które zaczynają korzystać ze smartfona']],
            'outcomes' => [['text' => 'Wykonasz połączenie i wyślesz wiadomość'], ['text' => 'Zrobisz zdjęcie i znajdziesz je w galerii'], ['text' => 'Zainstalujesz aplikację']],
            'program' => [['title' => 'Pierwsze uruchomienie', 'text' => 'Ustawienia podstawowe i obsługa ekranu.'], ['title' => 'Połączenia i wiadomości', 'text' => 'Kontakty, rozmowy i wiadomości tekstowe.'], ['title' => 'Aplikacje', 'text' => 'Instalowanie i usuwanie aplikacji.']],
        ]);
    }

    /** Usługa w zakładce odbiorców: podstrona z opisem meta i ikoną. */
    private function service(Page $parent, int $order, string $title, string $icon, string $description, string $html): void
    {
        $page = $this->child($parent, $order, $title, 'standard', $html, ['meta_description' => $description]);

        if (blank(($page->layout_data ?? [])['icon'] ?? null)) {
            $page->forceFill(['layout_data' => array_merge((array) $page->layout_data, ['icon' => $icon])])->save();
        }
    }

    private function offer(Page $catalog, int $order, string $title, array $typeData): void
    {
        $this->child($catalog, $order, $title, 'training_offer', '', ['meta_description' => $typeData['lead'] ?? null], $typeData + [
            'faq' => [
                ['question' => 'Czy mogę zgłosić potrzebę dostosowania zajęć?', 'answer' => 'Tak. Opisz ją w formularzu zapisu, a skontaktujemy się z Tobą przed zajęciami.'],
                ['question' => 'Czy dostanę zaświadczenie?', 'answer' => 'Zaświadczenie wydajemy po ukończeniu szkolenia w zakresie opisanym w programie.'],
            ],
        ]);
    }

    private function child(Page $parent, int $order, string $title, string $type, string $html, array $extra = [], array $typeData = []): Page
    {
        $page = $this->page($title, $type, $html, $extra, $typeData);

        if (! $page->parent_id) {
            $page->forceFill(['parent_id' => $parent->id, 'order' => $order])->save();
        }

        return $page;
    }

    /** Tworzy szkic strony albo uzupełnia istniejącą, tylko gdy jej treść i dane typu są puste. */
    private function page(string $title, string $type, string $html, array $extra = [], array $typeData = []): Page
    {
        $page = Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('title', $title)->first();

        if (! $page) {
            $this->created++;

            return Page::withoutGlobalScopes()->create($extra + [
                'site_id' => $this->site->id, 'title' => $title, 'slug' => UniqueSlug::page($title), 'type' => $type,
                'content' => $html, 'type_data' => $typeData, 'is_published' => ! $this->draft, 'show_in_menu' => true, 'meta_title' => $title,
            ]);
        }

        $fill = [];
        if (blank(trim(strip_tags((string) $page->content))) && $html !== '') {
            $fill['content'] = $html;
        }
        if (blank($page->type_data) && $typeData !== []) {
            $fill['type_data'] = $typeData;
        }
        if (blank($page->meta_description) && isset($extra['meta_description'])) {
            $fill['meta_description'] = $extra['meta_description'];
        }
        if ($fill !== []) {
            $page->forceFill($fill)->save();
            $this->created++;
        }

        return $page;
    }
}
