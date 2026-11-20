<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;

/**
 * Witryna fundacji „Kreatywni na co dzień” w szablonie „kreatywni” jednym poleceniem (treści i kolory jak na
 * warsztatydladziecikrakow.pl): szablon i kolory marki, kontakt i profile social media (tylko puste pola), logo
 * (gdy witryna go nie ma), strony Oferta, O nas, Galeria, Fundacja (z dokumentami jako szkicami do uzupełnienia)
 * i menu główne (tylko gdy witryna nie ma jeszcze menu). Teksty strony głównej są domyślne w szablonie
 * (App\Support\KreatywniTexts) i edytuje się je w Ustawienia → Szablon.
 *
 * Seeder niczego nie nadpisuje poza szablonem i kolorami: istniejącą stronę (po tytule) uzupełnia tylko wtedy, gdy
 * jej treść jest pusta. Wymaga wskazania witryny, żeby nie zmienić przypadkiem witryny głównej:
 *
 *   SITE_SLUG=kreatywni php artisan db:seed --class=KreatywniSiteSeeder --force   (slug albo nazwa witryny)
 */
class KreatywniSiteSeeder extends Seeder
{
    private SiteSetting $site;

    private int $created = 0;

    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? '')));
        if ($slug === '') {
            $this->command?->warn('Pominięto: podaj witrynę, np. SITE_SLUG=kreatywni php artisan db:seed --class=KreatywniSiteSeeder --force');

            return;
        }
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();
        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }
        $this->site = $site;

        $this->settings();
        $this->logo();
        $pages = $this->pages();
        $this->menu($pages);

        $this->command?->info('Witryna „'.$site->site_name.'” w szablonie „kreatywni”: utworzono lub uzupełniono '.$this->created.' stron. Dokumenty fundacji są szkicami do uzupełnienia.');
    }

    private function settings(): void
    {
        $fill = [
            'site_template' => 'kreatywni',
            'brand_color' => '#1b4a07',
            'brand_color_2' => '#007a38',
            'brand_color_3' => '#a2c523',
            'brand_color_4' => '#fce98b',
        ];
        $blank = [
            'tagline' => 'Warsztaty kreatywne i twórcze z dojazdem — Kraków i okolice',
            'meta_description' => 'Fundacja „Kreatywni na co dzień”: mobilne warsztaty kreatywne i terapeutyczne dla dzieci, dorosłych i seniorów w Krakowie i okolicach. Z dojazdem, materiały w cenie.',
            'contact_phone' => '+48 663 182 816',
            'contact_email' => 'warsztaty.kreatywninacodzien@gmail.com',
            'contact_address' => '',
            'contact_city' => 'Kraków i okolice',
            'facebook_url' => 'https://www.facebook.com/profile.php?id=100075973327291',
            'instagram_url' => 'https://www.instagram.com/fundacja.kreatywni/',
        ];
        // Wartości domyślne kolumn (dane FEER z migracji) traktujemy jak puste pole.
        $placeholders = ['contact_email' => 'fundacja@feer.org.pl', 'contact_address' => 'ul. Warszawska 12', 'contact_city' => '00-001 Warszawa'];
        foreach ($blank as $key => $value) {
            $current = trim((string) $this->site->{$key});
            if ($current === '' || $current === ($placeholders[$key] ?? null)) {
                $fill[$key] = $value;
            }
        }
        $this->site->forceFill($fill)->save();
    }

    private function logo(): void
    {
        $path = database_path('seeders/assets/kreatywni/logo.webp');
        if ($this->site->getFirstMedia('logo') || ! is_file($path)) {
            return;
        }
        try {
            $this->site->addMedia($path)->preservingOriginal()->usingFileName('kreatywni-logo.webp')->toMediaCollection('logo');
        } catch (\Throwable $e) {
            $this->command?->warn('Logo nie zostało dodane: '.$e->getMessage());
        }
    }

    /** @return array<string, Page> */
    private function pages(): array
    {
        $offer = $this->page('Oferta', 1, $this->offerHtml(), 'Mobilne warsztaty kreatywne z dojazdem w Krakowie i okolicach: laleczki, biżuteria z filcu, świece sojowe, mydełka, miody smakowe i wiele innych. Cena od 30 zł za osobę.');
        $about = $this->page('O nas', 2, $this->aboutHtml(), 'Poznaj osoby prowadzące warsztaty fundacji „Kreatywni na co dzień” i dowiedz się, dlaczego powstała fundacja.');
        $gallery = $this->page('Galeria', 3, '<p>Zdjęcia z naszych warsztatów publikujemy na bieżąco w mediach społecznościowych — na Facebooku i Instagramie. Tutaj znajdziesz wybrane z nich.</p>', 'Zdjęcia z warsztatów fundacji „Kreatywni na co dzień”.');
        $foundation = $this->page('Fundacja', 4, $this->foundationHtml(), 'Statut, regulamin darowizn, polityka prywatności i standardy ochrony małoletnich fundacji „Kreatywni na co dzień”. Przekaż 1,5% podatku.');

        foreach (['Statut Fundacji', 'Regulamin darowizn', 'Standardy ochrony małoletnich', 'Polityka prywatności', 'Polityka cookies'] as $i => $title) {
            $this->page($title, $i + 1, '<p>Treść dokumentu do uzupełnienia. Wklej tu pełny tekst albo dołącz plik PDF jako załącznik, a potem opublikuj stronę.</p>', null, $foundation, false);
        }

        return compact('offer', 'about', 'gallery', 'foundation');
    }

    /** @param array<string, Page> $pages */
    private function menu(array $pages): void
    {
        $hasMenu = NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->exists();
        if ($hasMenu) {
            return;
        }
        $order = 1;
        foreach (['offer' => 'Oferta', 'about' => 'O nas', 'gallery' => 'Galeria', 'foundation' => 'Fundacja'] as $key => $label) {
            NavItem::withoutGlobalScopes()->create([
                'site_id' => $this->site->id, 'label' => $label, 'url' => '/'.$pages[$key]->slug, 'type' => 'link',
                'location' => 'main', 'is_active' => true, 'order' => $order++,
            ]);
        }
    }

    private function page(string $title, int $order, string $html, ?string $description = null, ?Page $parent = null, bool $published = true): Page
    {
        $page = Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('title', $title)
            ->where('parent_id', $parent?->id)->first();

        if (! $page) {
            $this->created++;

            return Page::withoutGlobalScopes()->create([
                'site_id' => $this->site->id, 'parent_id' => $parent?->id, 'title' => $title, 'slug' => UniqueSlug::page($title),
                'type' => 'standard', 'content' => $html, 'is_published' => $published, 'show_in_menu' => $parent === null,
                'order' => $order, 'meta_title' => $title, 'meta_description' => $description,
            ]);
        }

        if (blank(trim(strip_tags((string) $page->content)))) {
            $page->forceFill(['content' => $html] + (blank($page->meta_description) && $description ? ['meta_description' => $description] : []))->save();
            $this->created++;
        }

        return $page;
    }

    private function offerHtml(): string
    {
        $items = [
            ['Laleczki zapachowe', 'Inspirujące warsztaty projektowania pachnących laleczek z naturalnym suszem.'],
            ['Biżuteria z filcu', 'Interaktywny kurs tworzenia unikatowej biżuterii z wełny czesankowej.'],
            ['Kolaż botaniczny', 'Obrazy wprost z natury. Nie musisz mieć zdolności plastycznych, wystarczy rozbudzić wyobraźnię.'],
            ['Laleczki ze sznurka', 'Co potrafią makramowe supełki, czyli całkowicie zaplątane zajęcia ze sznurkiem w roli głównej.'],
            ['Choinkowe bonsai', 'Świąteczne warsztaty sadzenia oraz dekorowania bożonarodzeniowego mini drzewka.'],
            ['Dekorowanie bombek', 'Magiczne zajęcia ozdabiania bombek różnymi metodami oraz technikami.'],
            ['Wianki wielkanocne', 'Wiosenne zajęcia rozwijające zainteresowania z wykorzystaniem wielkanocnych dodatków.'],
            ['Kompozycje kwiatowe', 'Wielkanocne tworzenie kompozycji z żywych kwiatów, przepełnione kolorami oraz wiosenną atmosferą.'],
            ['Dekorowanie jajek gipsowych', 'Wiosenne zajęcia ozdabiania jajek metodą decoupage z wykorzystaniem suszonych kwiatów.'],
            ['Język migowy', 'Wprowadzenie do języka migowego, nauka podstawowych zwrotów oraz alfabetu.'],
            ['Świece sojowe', 'Relaksujące sesje tworzenia dekoracyjnych świec z ekologicznych materiałów.'],
            ['Komponowanie miodów smakowych', 'Nauka poprzez doświadczanie produktów pszczelich. Stwórz miód z ulubionymi dodatkami.'],
            ['Laleczki motanki', 'Zajmujące warsztaty tworzenia tradycyjnych lalek słowiańskich w duchu zero waste.'],
            ['Perfumy', 'Pachnące zajęcia komponowania personalizowanych perfum z olejkami eterycznymi.'],
            ['Kule musujące', 'Sensoryczna zabawa polegająca na przygotowaniu magicznych, buzujących w wodzie kul.'],
            ['Mydełka glicerynowe', 'Kreatywne warsztaty tworzenia dekoracyjnych mydełek z naturalnych składników.'],
        ];
        $list = implode("\n", array_map(fn ($i) => '<h3>'.e($i[0]).'</h3>'."\n".'<p>'.e($i[1]).'</p>', $items));

        return <<<HTML
<p>Naszą misję realizujemy m.in. dzięki odpłatnym, mobilnym warsztatom w szkołach, przedszkolach, domach kultury, świetlicach, na festiwalach i wszędzie tam, gdzie są ludzie gotowi na inspirujące spotkanie. Dojeżdżamy do placówek w Krakowie i okolicach.</p>
<p><strong>Cena od 30 zł za osobę</strong> — zależna od liczby osób oraz lokalizacji. Wszystkie materiały i narzędzia są w cenie.</p>
<h2>Warsztaty tematyczne</h2>
{$list}
<h2>Zamów warsztaty</h2>
<p>Napisz albo zadzwoń — ustalimy temat, termin i miejsce zajęć. Z odpłatnych warsztatów finansujemy bezpłatne zajęcia w szpitalach i Domach Pomocy Społecznej.</p>
HTML;
    }

    private function aboutHtml(): string
    {
        return <<<'HTML'
<p>Wierzymy, że każdy ma w sobie ogromny potencjał — wystarczy stworzyć mu przestrzeń, by mógł go odkrywać. Naszą misją jest wspieranie rozwoju dzieci oraz dorosłych poprzez kreatywne, edukacyjne i pełne radości zajęcia, które pobudzają wyobraźnię, uczą współpracy i budują pewność siebie.</p>
<h2>Anna Mossór-Koper</h2>
<p><strong>Terapeuta zajęciowy, koordynator zajęć</strong></p>
<p>Uwielbiam robić coś z „niczego”, eksperymentować, modyfikować, tworzyć, inspirując innych. Jako terapeuta zajęciowy prowadzę warsztaty w sposób rozwijający motorykę, sensorykę oraz sferę emocjonalną. Zależy mi na tym, aby uczestnicy zapomnieli o troskach i przenieśli się do świata twórczości, spokoju, harmonii i piękna. Stawiam na kreatywność, wzrost poczucia sprawstwa oraz kształtowanie zdrowych nawyków.</p>
<h2>Sławomir Koper</h2>
<p><strong>Instruktor zajęć, nauczyciel języków obcych</strong></p>
<p>Z zamiłowania ogrodnik i miłośnik pszczół. Uświadamiam ludziom wartość pszczół oraz ryzyko związane z ich brakiem. Każde zajęcia projektuję tak, by były bezpieczne, angażujące i dostosowane do wieku oraz potrzeb uczestników. Stawiam na naukę poprzez zabawę. Posługuję się językiem angielskim, niemieckim, niderlandzkim oraz chorwackim i inspiruję uczestników do nauki języków.</p>
<h2>Dlaczego powstała Fundacja „Kreatywni na co dzień”?</h2>
<h3>Wsparcie pacjentów długoterminowych</h3>
<ul><li>Aby podnosić na duchu.</li><li>Aby dodawać skrzydeł i siły do walki.</li><li>Aby choć na chwilę pomóc zapomnieć o chorobie.</li></ul>
<h3>Integracja społeczna</h3>
<ul><li>Aby przeciwdziałać marginalizacji.</li><li>Aby integrować różnorodne grupy społeczne.</li><li>Aby zwiększyć świadomość oraz poszanowanie dla neuroróżnorodności.</li></ul>
<h3>Rozwój zmysłów</h3>
<ul><li>Aby rozwijać zmysły za pomocą różnorodnych, angażujących aktywności.</li><li>Aby promować apiterapię oraz inne naturalne metody profilaktyczne.</li><li>Aby obcować z naturą i jej darami.</li></ul>
<h3>Zdrowie psychiczne</h3>
<ul><li>Aby wspierać dobrostan psychiczny i równowagę emocjonalną.</li><li>Aby zapobiegać wypaleniu zawodowemu oraz depresji.</li></ul>
<h3>Edukacja ekologiczna</h3>
<ul><li>Aby kształtować proekologiczne postawy.</li><li>Aby wspierać inicjatywy na rzecz ochrony środowiska naturalnego.</li><li>Aby chronić pszczoły, lasy i rzeki.</li></ul>
<h2>Historia powstania Fundacji</h2>
<p>Wszystko zaczęło się w 2020 roku — czasie, który dla wielu był pełen lęku, izolacji i niepewności. Gdy świat nagle zwolnił, a codzienność zamknęła się w czterech ścianach, pojawiła się potrzeba odnalezienia choć odrobiny radości.</p>
<p>Zaczęło się od bazy mydlanej, kilku olejków i barwników. W domowym zaciszu powstały kolorowe mydełka — małe, pachnące symbole nadziei. To doświadczenie uświadomiło nam, jak ogromną moc ma twórczość — nie dla efektu, ale dla procesu tworzenia i chwili oddechu. I wtedy pojawiła się myśl: jeżeli nam to pomogło, dlaczego nie podzielić się tym z innymi — zwłaszcza z tymi, którzy przechodzą przez najtrudniejsze momenty swojego życia?</p>
<p>Droga do realizacji tego marzenia nie była łatwa. Fundacja boryka się z brakiem finansowania, a zapotrzebowanie na bezpłatną terapię zajęciową rośnie z każdym miesiącem. Mimo to nie poddajemy się. Krok po kroku, dzięki ludziom, którzy wierzą w sens pomagania, tworzymy przestrzeń, w której każdy — dziecko, dorosły, senior — może choć na chwilę zapomnieć o bólu, samotności czy lęku.</p>
<h2>Chcesz dołączyć do naszych wolontariuszy?</h2>
<p>Napisz do nas przez stronę Kontakt — chętnie porozmawiamy.</p>
HTML;
    }

    private function foundationHtml(): string
    {
        return <<<'HTML'
<p><strong>I Ty możesz zostać super bohaterem, przekazując 1,5% podatku.</strong></p>
<p>Statut Fundacji określa cele, sposoby działania oraz strukturę organizacyjną. Regulamin darowizn opisuje, jak przekazać darowiznę na cele statutowe Fundacji. Polityka prywatności wyjaśnia, jak gromadzimy, przechowujemy i wykorzystujemy dane osobowe. Standardy ochrony małoletnich opisują, jak dbamy o bezpieczeństwo dzieci na naszych zajęciach.</p>
<p>Przekazane nam darowizny możesz odliczyć od podatku, rozliczając PIT.</p>
<h2>Dokumenty</h2>
<p>[podstrony]</p>
HTML;
    }
}
