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
        $this->people();
        $this->offers();

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
        // Moduły, których wizytówka fundacji nie używa (Podcasty, Osoby) — dopisane do wyłączonych, reszta bez zmian.
        $fill['disabled_modules'] = array_values(array_unique(array_merge((array) $this->site->disabled_modules, ['podcasts', 'persons'])));
        // Stopka: bez linku do strefy współpracownika FEER (na tej witrynie prowadziłby donikąd), gdy stopki nikt nie ustawiał.
        if (empty($this->site->getAttribute('footer_options')) && \Illuminate\Support\Facades\Schema::hasColumn('site_settings', 'footer_options')) {
            $fill['footer_options'] = ['staff_link' => false];
        }
        $this->site->forceFill($fill)->save();
    }

    /** Oferta z tekstów szablonu do modułu „Mikrooferta – Kreatywni”, gdy moduł jest pusty. */
    private function offers(): void
    {
        if (! \App\Models\KreatywniOffer::supported()
            || \App\Models\KreatywniOffer::withoutGlobalScopes()->where('site_id', $this->site->id)->exists()) {
            return;
        }
        $texts = \App\Support\KreatywniTexts::for($this->site);
        if ($texts->rows('offer_items') === [] && $texts->get('offer_price') === '') {
            return;
        }
        \App\Models\KreatywniOffer::withoutGlobalScopes()->create(
            \App\Http\Controllers\Admin\KreatywniOfferController::fromTexts($texts) + ['site_id' => $this->site->id, 'order' => 1, 'is_active' => true]
        );
    }

    /** Osoby z tekstów szablonu („O nas” → zespół) do modułu „Osoby – Kreatywni”, gdy moduł jest pusty. */
    private function people(): void
    {
        if (! \App\Models\KreatywniPerson::supported()
            || \App\Models\KreatywniPerson::withoutGlobalScopes()->where('site_id', $this->site->id)->exists()) {
            return;
        }
        $texts = \App\Support\KreatywniTexts::for($this->site);
        foreach ($texts->rows('about_team', 3) as $i => [$name, $role, $bio]) {
            \App\Models\KreatywniPerson::withoutGlobalScopes()->create([
                'site_id' => $this->site->id, 'name' => $name, 'role' => $role ?: null, 'bio' => $bio ?: null,
                'order' => $i + 1, 'is_active' => true,
            ]);
        }
    }

    private function logo(): void
    {
        $path = database_path('seeders/assets/kreatywni/logo.webp');
        if (! is_file($path)) {
            return;
        }
        // Logo (gdy witryna go nie ma) i ten sam znak jako domyślny obrazek przy udostępnianiu linków (og:image).
        foreach (['logo' => 'kreatywni-logo.webp', 'og_image' => 'kreatywni-udostepnianie.webp'] as $collection => $fileName) {
            if ($this->site->getFirstMedia($collection)) {
                continue;
            }
            try {
                $this->site->addMedia($path)->preservingOriginal()->usingFileName($fileName)->toMediaCollection($collection);
            } catch (\Throwable $e) {
                $this->command?->warn('Nie dodano obrazka ('.$collection.'): '.$e->getMessage());
            }
        }
    }

    /** @return array<string, Page> */
    private function pages(): array
    {
        $offer = $this->page('Oferta', 1, $this->offerHtml(), 'Mobilne warsztaty kreatywne z dojazdem w Krakowie i okolicach: laleczki, biżuteria z filcu, świece sojowe, mydełka, miody smakowe i wiele innych. Cena od 30 zł za osobę.');
        // „O nas” ma dedykowany układ z tekstów szablonu (Ustawienia → Szablon → Podstrona „O nas”), więc bez treści.
        $about = $this->page('O nas', 2, '', 'Poznaj osoby prowadzące warsztaty fundacji „Kreatywni na co dzień” i dowiedz się, dlaczego powstała fundacja.');
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
        // „Działania” prowadzi do listy z modułu Działania (/dzialania), reszta do stron założonych wyżej.
        foreach (['offer' => 'Oferta', 'projects' => 'Działania', 'about' => 'O nas', 'gallery' => 'Galeria', 'foundation' => 'Fundacja'] as $key => $label) {
            NavItem::withoutGlobalScopes()->create([
                'site_id' => $this->site->id, 'label' => $label, 'url' => $key === 'projects' ? '/dzialania' : '/'.$pages[$key]->slug, 'type' => 'link',
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

        if ($html !== '' && blank(trim(strip_tags((string) $page->content)))) {
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
