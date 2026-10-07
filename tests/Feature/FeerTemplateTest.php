<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeerTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_motyw_brandbooka_tylko_w_szablonie_feer(): void
    {
        $this->get('/projekty')->assertOk()->assertDontSee('--color-ink: #1d1d1a', false);

        $this->assertArrayHasKey('feer', SiteSetting::SITE_TEMPLATES);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/projekty')->assertOk()->assertDontSee('--color-ink: #1d1d1a', false);

        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/projekty')->assertOk()->assertSee('--color-ink: #1d1d1a', false);
        $this->get('/')->assertOk()->assertSee('--color-ink: #1d1d1a', false);
    }

    public function test_szablon_feer_ma_wlasna_palete_i_strone_glowna(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'brand_color' => '#c31432']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertSee('--color-brand: #1e6dff', false)->assertSee('--color-brand-4: #cbd5e7', false);
    }

    public function test_szablon_feer_pokazuje_szybkie_akcje_i_pozwala_je_wylaczyc(): void
    {
        \App\Models\QuickAction::create(['label' => 'Zgłoś barierę', 'url' => '/zgloszenia', 'icon' => 'fa-solid fa-flag', 'order' => 1]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertSee('Na skróty')->assertSee('Zgłoś barierę');

        SiteSetting::current()->update(['homepage_sections_hidden' => ['ankieta']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertDontSee('Zgłoś barierę');
    }

    public function test_wylaczona_sekcja_aktualnosci_znika_ze_strony_glownej(): void
    {
        \App\Models\News::create(['title' => 'Ważna wiadomość', 'slug' => 'wazna', 'content' => 'x', 'excerpt' => 'x', 'is_published' => true, 'published_at' => now()->subDay()]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertSee('id="ngo-news-heading"', false);

        SiteSetting::current()->update(['homepage_sections_hidden' => ['news']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertDontSee('id="ngo-news-heading"', false);
    }

    public function test_kolory_szablonu_feer_maja_kontrast_co_najmniej_4_5_do_1_na_bialym(): void
    {
        $css = file_get_contents(resource_path('views/partials/theme-feer.blade.php'));
        $lum = function (string $hex): float {
            $hex = ltrim($hex, '#');
            $c = array_map(fn ($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
            $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };

        // Kolor główny #1E6DFF (decyzja właściciela) ma na bieli 4,48:1 — minimalnie poniżej AA; tekst i linki używają brand-dark.
        foreach (['--color-brand' => 4.4, '--color-brand-dark' => 4.4] as $var => $min) {
            $this->assertSame(1, preg_match('/'.preg_quote($var, '/').':\s*(#[0-9a-fA-F]{6})/', $css, $m), $var);
            $ratio = 1.05 / ($lum($m[1]) + 0.05);
            $this->assertGreaterThanOrEqual($min, $ratio, "{$var} {$m[1]} ma kontrast {$ratio}:1 na bieli");
        }
    }

    public function test_menu_w_szablonie_feer_to_bialy_pasek_z_pigulkami(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'header_layout' => 'wide_mission']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('nav-pills', $html);
        $this->assertStringContainsString('border-b border-gray-200', $html);
        $this->assertStringNotContainsString('border-y border-gray-200', $html);
        $this->assertStringNotContainsString('relative hidden bg-brand shadow-sm', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/')->assertOk()->assertSee('relative hidden bg-brand shadow-sm', false);
    }

    private function hubPage(): void
    {
        \App\Models\Page::create([
            'title' => 'Dołącz', 'slug' => 'dolacz-hub-test', 'type' => 'links_hub', 'is_published' => true, 'hub_intro' => 'Zostań z nami',
            'hub_links' => [['label' => 'Wolontariat', 'url' => '/wolontariat', 'icon' => 'fa-solid fa-hand', 'color' => 'green', 'description' => 'Pomagaj']],
        ]);
    }

    public function test_strona_hub_w_szablonie_feer_ma_plaskie_karty_bez_kolorowych_naglowkow(): void
    {
        $this->hubPage();
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/dolacz-hub-test')->assertOk()->assertSee('Wolontariat')->assertSee('Zostań z nami')->getContent();
        $this->assertStringContainsString('border-top: 4px solid #16a34a', $html);
        $this->assertStringNotContainsString('<section class="bg-brand text-white"', $html);
    }

    public function test_strona_hub_w_innych_szablonach_zostaje_bez_zmian(): void
    {
        $this->hubPage();

        $this->get('/dolacz-hub-test')->assertOk()->assertSee('<section class="bg-brand text-white"', false);
    }

    public function test_strona_o_organizacji_w_szablonie_feer_ma_jasny_naglowek(): void
    {
        \App\Models\Page::create(['title' => 'O fundacji', 'slug' => 'o-fundacji-test', 'type' => 'about', 'is_published' => true, 'about_motto' => 'Razem bez barier']);

        $this->get('/o-fundacji-test')->assertOk()->assertSee('<header class="relative overflow-hidden bg-brand', false);

        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/o-fundacji-test')->assertOk()->assertSee('Razem bez barier')->getContent();
        $this->assertStringNotContainsString('<header class="relative overflow-hidden bg-brand', $html);
        $this->assertStringContainsString('<header>', $html);
        $this->assertStringContainsString('<article class="about-feer">', $html);
        $this->assertStringNotContainsString('border-b-4 border-transparent', $html);
    }

    public function test_strona_sprawozdan_ma_jasny_naglowek(): void
    {
        $this->get('/sprawozdania')->assertOk()->assertSee('Sprawozdania roczne')->assertSee('bg-gray-50', false);
    }

    public function test_skroty_wypelniaja_rzad_a_projekty_na_glownej_to_lista_wierszy(): void
    {
        foreach (['Facebook', 'Archiwum', 'Panel'] as $i => $label) {
            \App\Models\QuickAction::create(['label' => $label, 'url' => '/'.$i, 'icon' => 'fa-solid fa-link', 'order' => $i]);
        }
        $category = \App\Models\Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        \App\Models\Project::create(['title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'category_id' => $category->id]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->assertSee('Wsparcie IT')->getContent();
        $this->assertStringContainsString('feer-shortcuts-heading', $html);
        $this->assertStringNotContainsString('divide-y divide-gray-200', $html);
        $this->assertStringNotContainsString('border border-gray-200 bg-white', $html);
    }

    public function test_pasek_gorny_feer_laczy_konto_i_wesprzyj_w_ciemnym_rzedzie(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'header_layout' => 'wide_mission', 'wide_mission_layout' => 'bar', 'bank_account_number' => '09 1020 2906 0000 1402 0659 7480']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('site-topbar-feer', $html);
        // Numer konta: pasek górny i panel mobilny; osobnego paska nad belką już nie ma.
        $this->assertSame(2, substr_count($html, 'Nr konta:'), 'pasek górny + panel mobilny');
        $this->assertStringNotContainsString('bg-brand-light/50', $html);
    }

    public function test_stopka_feer_jest_ciemnym_pasem_z_bialym_tekstem(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringContainsString('bg-ink text-white', $footer);
        $this->assertStringContainsString('Deklaracja dostępności', $footer);
        $this->assertStringNotContainsString('opacity-40', $footer);
    }

    public function test_kontakt_w_szablonie_feer_ma_wlasny_uklad_dane_i_formularz_obok_siebie(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'contact_layout' => 'tabs']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/kontakt')->assertOk()->getContent();
        $this->assertStringNotContainsString('role="tablist"', $html);
        $this->assertStringContainsString('id="formularz-heading"', $html);
        $this->assertStringContainsString('id="dane-heading"', $html);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_26rem]', $html);
        $this->assertStringNotContainsString('<section class="relative bg-ink', $html);
    }

    public function test_inne_szablony_zostaja_przy_ukladzie_z_zakladkami(): void
    {
        SiteSetting::current()->update(['site_template' => 'ngo_mix', 'contact_layout' => 'tabs']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/kontakt')->assertOk()->assertSee('role="tablist"', false);
    }

    public function test_strona_glowna_feer_nie_ma_kresek_miedzy_modulami(): void
    {
        \App\Models\QuickAction::create(['label' => 'Panel', 'url' => '/p', 'icon' => 'fa-solid fa-link', 'order' => 1]);
        \App\Models\News::create(['title' => 'Wiadomość', 'slug' => 'wiadomosc', 'content' => 'x', 'excerpt' => 'x', 'is_published' => true, 'published_at' => now()->subDay()]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        foreach (['feer-shortcuts-heading', 'ngo-news-heading'] as $id) {
            $this->assertSame(1, preg_match('/<section class="([^"]*)"[^>]*aria-labelledby="'.$id.'"/s', $html, $m), $id);
            $this->assertDoesNotMatchRegularExpression('/border-(t|b)\b/', $m[1], $id);
        }
        $this->assertStringNotContainsString('border-top: 1px solid #f3f4f6', $html);
    }

    public function test_slajder_feer_nie_wywala_sie_na_slajdzie_z_misja(): void
    {
        \App\Models\HeroSlide::create(['title' => 'Zwykły slajd', 'order' => 1]);
        SiteSetting::current()->update(['site_template' => 'feer', 'hero_mission_slide' => true, 'tagline' => 'Razem bez barier', 'hero_mission_order' => 2]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertSee('Zwykły slajd')->assertSee('Nasza misja')->assertSee('Razem bez barier');
    }

    public function test_slajder_szablonu_ngo_tez_znosi_slajd_z_misja(): void
    {
        \App\Models\HeroSlide::create(['title' => 'Zwykły slajd', 'order' => 1]);
        SiteSetting::current()->update(['site_template' => 'ngo', 'hero_mission_slide' => true, 'tagline' => 'Razem bez barier', 'hero_mission_order' => 2]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()->assertSee('Zwykły slajd');
    }

    public function test_strona_kontakt_2_typu_kontakt_renderuje_sie_w_szablonie_feer(): void
    {
        \App\Models\Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);
        SiteSetting::current()->update([
            'site_template' => 'feer', 'header_layout' => 'wide_mission', 'contact_layout' => 'tabs',
            'contact_phone' => '601 350 487', 'contact_office_hours' => 'Pon–Śr 10–12',
            'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']],
            'contact_schedule_enabled' => true, 'contact_online_meeting_url' => 'https://example.org/spotkanie',
            'contact_paczkomat_code' => 'KRA01M', 'contact_shipping_visible' => true,
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/kontakt')->assertRedirect('/kontakt-2');
        $this->get('/kontakt-2')->assertOk()
            ->assertSee('id="formularz-heading"', false)->assertSee('601 350 487')->assertSee('PL12 3456')->assertSee('contact-feer', false)->assertSee('tel:601350487', false);
    }

    public function test_dluga_uwaga_przy_rachunkach_jest_skrocona_do_pierwszego_zdania(): void
    {
        $long = 'Opłaty za szkolenia firm prosimy wpłacać na ogólny rachunek bankowy: 88 1020 2906 0000 1302 0661 7932. Osoby fizyczne mają indywidualny numer rachunku wirtualnego, który przesyłamy w wiadomości e-mail potwierdzającej zapis. Prosimy o upewnienie się, że wybrano właściwy numer konta.';
        SiteSetting::current()->update(['contact_bank_accounts_note' => $long, 'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']]]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/kontakt')->assertOk()->getContent();
        $this->assertStringContainsString('Czytaj więcej', $html);
        $this->assertStringContainsString('<details class="mt-2">', $html);
        $this->assertMatchesRegularExpression('/<p class="leading-relaxed">Opłaty za szkolenia firm[^<]*7932\.<\/p>/u', $html);
    }

    public function test_lista_projektow_feer_ma_nawigacje_kategorii_i_wiersze_bez_ramek(): void
    {
        $category = \App\Models\Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        \App\Models\Project::create(['title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'category_id' => $category->id]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/projekty')->assertOk()->assertSee('Wsparcie IT')->assertSee('Kategorie projektów')->getContent();
        $this->assertStringContainsString('lg:grid-cols-[14rem_minmax(0,1fr)]', $html);
        $this->assertStringNotContainsString('role="tablist"', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/projekty')->assertOk()->assertDontSee('lg:grid-cols-[14rem_minmax(0,1fr)]', false);
    }

    public function test_lista_materialow_feer_ma_wiersze_i_nawigacje_grup(): void
    {
        \App\Models\EducationalMaterial::create(['title' => 'Poradnik A', 'description' => 'Opis A', 'type' => 'pdf', 'is_published' => true, 'target_group' => array_key_first(\App\Models\EducationalMaterial::TARGET_GROUPS)]);
        \App\Models\EducationalMaterial::create(['title' => 'Poradnik B', 'description' => 'Opis B', 'type' => 'pdf', 'is_published' => true, 'target_group' => array_keys(\App\Models\EducationalMaterial::TARGET_GROUPS)[1] ?? array_key_first(\App\Models\EducationalMaterial::TARGET_GROUPS)]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/materialy')->assertOk()->assertSee('Poradnik A')->assertSee('Poradnik B')->getContent();
        $this->assertStringContainsString('lg:grid-cols-[14rem_minmax(0,1fr)]', $html);
        $this->assertStringContainsString('Grupy materiałów', $html);
        $this->assertStringContainsString('id="zapis"', $html);
        $this->assertStringContainsString('data-thumb-wrap', $html);
        $this->assertStringContainsString('aspect-video', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/materialy')->assertOk()->assertDontSee('Grupy materiałów');
    }

    public function test_strona_wsparcia_i_darowizny_w_szablonie_feer(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $support = $this->get('/wsparcie')->assertOk()->getContent();
        $this->assertStringContainsString('feer-flat', $support);
        $this->assertStringContainsString('id="wesprzyj-hero"', $support);
        $this->assertStringNotContainsString('bg-linear-to-br', $support);
        $this->assertStringContainsString('id="methods-heading"', $support);
        $this->assertStringContainsString('id="support-sticky"', $support);

        $this->get('/wsparcie/darowizna')->assertOk()->assertSee('feer-flat', false);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/wsparcie')->assertOk()->assertSee('bg-linear-to-br', false);
    }

    public function test_lista_aktualnosci_feer_ma_wyrozniony_wpis_i_karty(): void
    {
        foreach (['Pierwsza', 'Druga', 'Trzecia'] as $i => $title) {
            \App\Models\News::create(['title' => $title.' wiadomość', 'slug' => 'w'.$i, 'content' => 'x', 'excerpt' => 'Zajawka '.$title, 'is_published' => true, 'published_at' => now()->subDays($i + 1)]);
        }
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/aktualnosci')->assertOk()->assertSee('Pierwsza wiadomość')->assertSee('Trzecia wiadomość')->getContent();
        $this->assertStringContainsString('Kategorie aktualności', $html);
        $this->assertStringNotContainsString('overflow-x-auto pb-1', $html);
        $this->assertStringContainsString('text-2xl font-extrabold leading-tight text-ink md:text-4xl', $html);
        $this->assertStringContainsString('bg-ink text-white', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/aktualnosci')->assertOk()->assertDontSee('text-2xl font-extrabold leading-tight text-ink md:text-4xl', false);
    }

    public function test_pojedyncza_aktualnosc_feer_zachowuje_funkcje_i_ma_nowy_uklad(): void
    {
        $news = \App\Models\News::create(['title' => 'Ważna wiadomość', 'slug' => 'wazna', 'content' => '<p>Treść artykułu</p>', 'excerpt' => 'Krótka zajawka', 'is_published' => true, 'published_at' => now()->subDay()]);
        \App\Models\News::create(['title' => 'Inny wpis', 'slug' => 'inny', 'content' => 'x', 'excerpt' => 'z', 'is_published' => true, 'published_at' => now()->subDays(3)]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get(route('news.show', $news))->assertOk()
            ->assertSee('Ważna wiadomość')->assertSee('Krótka zajawka')->assertSee('Treść artykułu')->getContent();
        $this->assertStringContainsString('aria-label="Opcje artykułu"', $html);
        $this->assertStringContainsString('Odsłuchaj', $html);
        $this->assertStringContainsString('PDF', $html);
        $this->assertStringContainsString('Czytaj także', $html);
        $this->assertStringContainsString('id="article-text"', $html);
        $this->assertStringContainsString('min czytania', $html);
        $this->assertStringContainsString('.news-feer-body img { max-width: min(100%, 28rem)', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get(route('news.show', $news))->assertOk()->assertDontSee('min czytania');
    }

    public function test_na_skroty_maja_obramowke_albo_wypelnienie_zaleznie_od_ustawien(): void
    {
        \App\Models\QuickAction::create(['label' => 'Zwykła', 'url' => '/a', 'icon' => 'fa-solid fa-link', 'color' => '#1b66f5', 'is_negative' => false, 'order' => 1]);
        \App\Models\QuickAction::create(['label' => 'Negatyw', 'url' => '/b', 'icon' => 'fa-solid fa-link', 'color' => '#1456cc', 'is_negative' => true, 'order' => 2]);
        SiteSetting::current()->update(['site_template' => 'feer', 'quick_actions_panel_negative' => false]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('border: 2px solid #1b66f5', $html);          // zwykła: obramówka w kolorze akcji
        $this->assertStringContainsString('background-color: #1456cc', $html);          // negatyw: wypełnienie
        $this->assertMatchesRegularExpression('/<section class="bg-gray-50 py-10" aria-labelledby="feer-shortcuts-heading"/', $html);

        SiteSetting::current()->update(['quick_actions_panel_negative' => true]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/')->assertOk()->assertSee('<section class="bg-white py-10" aria-labelledby="feer-shortcuts-heading"', false);
    }

    public function test_sekcja_w_liczbach_feer_to_duze_liczby_bez_kart(): void
    {
        \App\Models\Page::create(['title' => 'O fundacji', 'slug' => 'o-fundacji-stat', 'type' => 'about', 'is_published' => true, 'about_stats' => [['value' => '120', 'label' => 'szkoleń'], ['value' => '35', 'label' => 'organizacji']]]);

        $this->get('/o-fundacji-stat')->assertOk()->assertSee('rounded-2xl bg-white px-6 py-6', false);

        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/o-fundacji-stat')->assertOk()->assertSee('szkoleń')->assertSee('data-countup-value', false)->getContent();
        $this->assertStringContainsString('border-t-4 border-brand pt-4', $html);
        $this->assertStringNotContainsString('rounded-2xl bg-white px-6 py-6', $html);
    }

    public function test_motyw_feer_wylacza_gradienty_tailwind_4(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('[class*="bg-linear-to"]', $html);
        $this->assertStringContainsString('[class*="bg-gradient-to"]', $html);
    }

    public function test_archiwum_aktualnosci_feer_ma_przyciski_zakresu_i_plaskie_wiersze(): void
    {
        \App\Models\News::create(['title' => 'Stary wpis', 'slug' => 'stary', 'content' => 'x', 'excerpt' => 'Zajawka', 'is_published' => true, 'is_archived' => true, 'published_at' => now()->subYears(2)]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/archiwum?tab=archiwalne')->assertOk()->assertSee('Stary wpis')->getContent();
        $this->assertStringContainsString('aria-label="Zakres archiwum"', $html);
        $this->assertStringContainsString('bg-ink text-white', $html);
        $this->assertStringNotContainsString('divide-y divide-gray-100', $html);
    }

    public function test_mikrointerakcje_kart_maja_wylaczenie_dla_ograniczonego_ruchu(): void
    {
        $category = \App\Models\Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        foreach (['Wpis', 'Drugi wpis'] as $i => $title) {
            \App\Models\News::create(['title' => $title, 'slug' => 'wpis-'.$i, 'content' => 'x', 'excerpt' => 'z', 'is_published' => true, 'published_at' => now()->subDays($i + 1)]);
        }
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('.feer-card:hover', $home);
        $this->assertStringContainsString('translateY(-3px)', $home);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $home);
        $this->assertStringContainsString('feer-card group relative', $home);
        $this->get('/aktualnosci')->assertOk()->assertSee('feer-card group', false);
    }

    public function test_motyw_feer_dodaje_pasek_akcentu_pod_naglowkami_i_odcien_tla(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('#ngo-news-heading::after', $html);
        $this->assertStringContainsString('background-color: var(--color-brand-light)', $html);
    }

    public function test_wolontariat_w_szablonie_feer_ma_plaskie_karty_i_karte_zgloszenia(): void
    {
        $ad = \App\Models\VolunteerAd::create([
            'title' => 'Wolontariusz w Klubie Cyfrowym', 'slug' => 'klub-cyfrowy', 'lead' => 'Pomóż osobom starszym.',
            'q_beneficiaries' => 'Osoby 60+.', 'q_tasks' => ['Prowadzenie spotkań'], 'q_mode' => 'stacjonarnie', 'q_location' => 'Nowy Sącz',
            'q_schedule' => 'Wtorki 16:00', 'q_time_commitment' => '4 godziny tygodniowo', 'q_benefits' => ['Zaświadczenie'],
            'q_how_to_apply' => 'Wypełnij formularz.', 'application_url' => 'https://forms.example.com/w', 'is_published' => true,
        ]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $list = $this->get('/wolontariat')->assertOk()->assertSee('Wolontariusz w Klubie Cyfrowym')->getContent();
        $this->assertStringContainsString('feer-card group relative', $list);

        $show = $this->get(route('volunteer.show', $ad))->assertOk()->assertSee('4 godziny tygodniowo')->assertSee('Jak się zgłosić?')->getContent();
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_22rem]', $show);
    }

    public function test_podcasty_w_szablonie_feer_maja_wyrozniony_odcinek_i_wiersze(): void
    {
        foreach ([3 => 'Trzeci odcinek', 2 => 'Drugi odcinek', 1 => 'Pierwszy odcinek'] as $n => $title) {
            \App\Models\Podcast::create(['title' => $title, 'slug' => 'odc-'.$n, 'description' => 'Opis '.$n, 'episode_number' => $n, 'is_published' => true, 'published_at' => now()->subDays(10 - $n)]);
        }
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $list = $this->get('/podcasty')->assertOk()->assertSee('Najnowszy odcinek')->assertSee('Wcześniejsze odcinki')->assertSee('Pierwszy odcinek')->getContent();
        $this->assertStringContainsString('feer-card group relative', $list);

        $podcast = \App\Models\Podcast::where('slug', 'odc-3')->first();
        $this->get(route('podcasts.show', $podcast))->assertOk()->assertSee('Trzeci odcinek')->assertSee('Wszystkie odcinki');
    }

    public function test_motyw_feer_ma_delikatne_tlo_po_bokach_wylaczone_w_trybie_kontrastu(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('body::before', $html);
        $this->assertStringContainsString('pointer-events: none', $html);
        $this->assertStringContainsString('forced-colors: active', $html);

        SiteSetting::current()->update(['site_template' => 'ngo_mix']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get('/')->assertOk()->assertDontSee('body::before', false);
    }

    public function test_naglowek_ma_drugi_przycisk_obok_pierwszego_i_w_menu_mobilnym(): void
    {
        SiteSetting::current()->update([
            'site_template' => 'feer', 'header_layout' => 'wide_mission',
            'wide_mission_cta_label' => 'Materiały', 'wide_mission_cta_url' => '/materialy',
            'wide_mission_cta2_label' => 'Wolontariat', 'wide_mission_cta2_url' => '/wolontariat',
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, '>Wolontariat</a>'), 'pasek nagłówka + panel mobilny');
        $this->assertStringContainsString('!border-brand', $html);
    }

    public function test_zdjecie_wpisu_feer_slucha_ustawienia_ukladu_a_ikonki_sa_na_dole(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $make = fn (string $layout) => \App\Models\News::create(['title' => 'Wpis '.$layout, 'slug' => 'w-'.$layout, 'content' => '<p>Treść</p>', 'excerpt' => 'z', 'is_published' => true, 'published_at' => now()->subDay(), 'article_layout' => $layout]);
        $withImg = function ($news) {
            \Illuminate\Support\Facades\Storage::fake('public');
            $news->addMedia(\Illuminate\Http\UploadedFile::fake()->image('a.jpg', 800, 600))->toMediaCollection('image');

            return $news->fresh();
        };

        $side = $withImg($make('side'));
        $html = $this->get(route('news.show', $side))->assertOk()->getContent();
        $this->assertStringContainsString('md:grid-cols-[18rem_minmax(0,1fr)]', $html);

        $center = $withImg($make('default'));
        $html = $this->get(route('news.show', $center))->assertOk()->getContent();
        $this->assertStringContainsString('mx-auto max-w-xl', $html);
        $this->assertStringNotContainsString('md:grid-cols-[18rem_minmax(0,1fr)]', $html);

        // Ikonki narzędzi stoją po treści artykułu (na dole), nie nad nią.
        $this->assertGreaterThan(strpos($html, 'id="article-text"'), strpos($html, 'aria-label="Opcje artykułu"'));
    }

    public function test_nawigacja_kafelkowa_pokazuje_podstrony_jako_kafelki(): void
    {
        $root = \App\Models\Page::create(['title' => 'Egzamin maturalny', 'slug' => 'egzamin-maturalny', 'type' => 'standard', 'is_published' => true, 'side_nav_style' => 'tiles', 'content' => '<p>Wstęp</p>']);
        foreach (['O egzaminie', 'Informatory', 'Arkusze'] as $i => $title) {
            \App\Models\Page::create(['title' => $title, 'slug' => 'egzamin-maturalny/'.str($title)->slug(), 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id, 'order' => $i, 'meta_description' => 'Opis '.$title]);
        }

        $html = $this->get($root->publicUrl())->assertOk()->assertSee('O egzaminie')->assertSee('Informatory')->assertSee('Arkusze')->assertSee('Opis Arkusze')->getContent();
        $this->assertStringContainsString('aria-label="Podstrony: Egzamin maturalny"', $html);
        $this->assertStringContainsString('min-h-36', $html);

        // Zwykły styl: bez kafelków.
        $root->update(['side_nav_style' => 'sidebar']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->get($root->publicUrl())->assertOk()->assertDontSee('aria-label="Podstrony: Egzamin maturalny"', false);
    }

    public function test_lista_projektow_moze_byc_kafelkowa_w_dowolnym_szablonie(): void
    {
        $category = \App\Models\Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        \App\Models\Project::create(['title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'excerpt' => 'Pomagamy', 'is_published' => true, 'category_id' => $category->id]);

        foreach (['default', 'feer'] as $template) {
            SiteSetting::current()->update(['site_template' => $template, 'projects_layout' => 'tiles']);
            \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
            \Illuminate\Support\Facades\Cache::flush();

            $html = $this->get('/projekty')->assertOk()->assertSee('Wsparcie IT')->getContent();
            $this->assertStringContainsString('min-h-40', $html, $template);
        }

        SiteSetting::current()->update(['site_template' => 'feer', 'projects_layout' => 'list']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        $this->get('/projekty')->assertOk()->assertDontSee('min-h-40', false);
    }

    public function test_naglowek_feer_ma_plaskie_skroty_social_i_przycisk_akcji(): void
    {
        SiteSetting::current()->update([
            'site_template' => 'feer', 'header_layout' => 'wide_mission', 'facebook_url' => 'https://facebook.com/feer',
            'wide_mission_social_1' => 'facebook', 'wide_mission_cta_label' => 'Materiały', 'wide_mission_cta_url' => '/materialy',
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '<main') - $start);
        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringContainsString('>Materiały</a>', $header);
        // jedyny zestaw ikon to ten z paska górnego — w samym nagłówku ich nie ma
        $this->assertStringContainsString('rounded-md text-ink hover:bg-brand-light', $header);
        $this->assertStringNotContainsString('rounded-full text-muted hover:bg-gray-100', $header);
        $this->assertStringContainsString('Facebook — otwiera się w nowej karcie', $footer);
    }

    public function test_naglowek_feer_ma_przycisk_materialow_edukacyjnych(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'header_layout' => 'wide_mission']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '<main') - $start);
        $this->assertStringContainsString('>Materiały edukacyjne</a>', $header);
    }

    public function test_naglowek_feer_w_ukladzie_classic_ma_przycisk_materialow(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'header_layout' => 'classic']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '<main') - $start);
        $this->assertStringContainsString('Materiały edukacyjne', $header);
    }

    public function test_pojedyncza_szybka_akcja_feer_to_duzy_przycisk(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\QuickAction::create(['label' => 'Panel kursanta', 'url' => '/panel', 'icon' => 'bi-person', 'color' => 'blue', 'order' => 1, 'is_active' => true]);

        $this->get('/')->assertOk()->assertSee('min-h-32', false)->assertSee('Panel kursanta');
    }

    public function test_wiersze_projektow_feer_maja_mikropis_z_tresci_gdy_brak_zajawki(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        $cat = \App\Models\Category::create(['name' => 'Dla każdego', 'slug' => 'dla-kazdego', 'order' => 1]);
        \App\Models\Project::create(['category_id' => $cat->id, 'title' => 'Projekt X', 'slug' => 'projekt-x', 'content' => '<p>Opis zajęć dla wolontariuszy.</p>', 'is_published' => true, 'order' => 1]);

        $this->get('/projekty')->assertOk()->assertSee('Opis zajęć dla wolontariuszy.');
    }

    public function test_szybkie_akcje_feer_obsluguja_negatyw_pasek_i_kolumny(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\QuickAction::create(['label' => 'Negatyw A', 'url' => '/a', 'icon' => 'bi-person', 'color' => '#1d1d1a', 'order' => 1, 'is_negative' => true, 'cols' => 2]);
        \App\Models\QuickAction::create(['label' => 'Pasek B', 'url' => '/b', 'icon' => 'bi-star', 'color' => 'blue', 'order' => 2, 'strip' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('sm:col-span-2', $html);
        $this->assertStringContainsString('min-h-14', $html);
        $this->assertStringContainsString('background-color: #1d1d1a', $html);
    }

    public function test_okruszki_feer_maja_wlasny_uklad_bez_szarego_paska(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $this->get('/kontakt')->assertOk()->assertSee('fa-chevron-right', false)->assertDontSee('border-b border-gray-200 bg-gray-50"><nav aria-label="Ścieżka', false);
    }

    public function test_szybka_akcja_feer_z_kolorem_purple_dostaje_firmowy_niebieski(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\QuickAction::create(['label' => 'Fiolet', 'url' => '/f', 'icon' => 'bi-star', 'color' => 'purple', 'order' => 1, 'is_negative' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('background-color: #1e6dff', $html);
        $this->assertStringNotContainsString('#7e22ce', $html);
    }

    public function test_naglowek_feer_classic_ma_ikony_social_obok_przycisku(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer', 'header_layout' => 'classic', 'facebook_url' => 'https://facebook.com/feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '<main') - $start);
        $this->assertStringContainsString('rounded-md text-xl text-ink', $header);
        $this->assertStringContainsString('Facebook — otwiera się w nowej karcie', $header);
    }

    public function test_strona_glowna_feer_ma_dla_admina_przycisk_edycji(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $this->get('/')->assertOk()->assertDontSee('stronę główną</span>', false);

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('stronę główną</span>', false);
    }

    public function test_strona_glowna_feer_pokazuje_ankiete_obok_szybkich_akcji(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        $poll = \App\Models\Poll::create(['question' => 'Co dalej?', 'is_active' => true]);
        $poll->options()->create(['label' => 'Opcja A']);

        $this->get('/')->assertOk()->assertSee('id="ankieta"', false)->assertSee('Co dalej?')->assertSee('Głosuj');
    }

    public function test_admin_widzi_link_zarzadzania_szybkimi_akcjami_w_szablonie_feer(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\QuickAction::create(['label' => 'Panel', 'url' => '/p', 'icon' => 'bi-person', 'order' => 1]);

        $this->get('/')->assertOk()->assertDontSee('Zarządzaj skrótami');
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Zarządzaj skrótami');
        $this->actingAs($admin)->get(route('admin.szybkie-akcje.create'))->assertOk()->assertSee('Niebieski FEER');
    }

    public function test_szybka_akcja_feer_pokazuje_krotki_opis(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\QuickAction::create(['label' => 'Panel kursanta', 'description' => 'Wróć do swoich szkoleń', 'url' => '/p', 'icon' => 'bi-person', 'order' => 1]);

        $this->get('/')->assertOk()->assertSee('Wróć do swoich szkoleń');
    }

    public function test_admin_widzi_linki_zarzadzania_projektami_i_aktualnosciami_w_szablonie_feer(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $this->get('/projekty')->assertOk()->assertDontSee('Zarządzaj projektami');
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/projekty')->assertOk()->assertSee('Zarządzaj projektami');
        $this->actingAs($admin)->get('/aktualnosci')->assertOk()->assertSee('Zarządzaj aktualnościami');
    }

    public function test_formularz_w_szablonie_feer_ma_pola_bez_obwodki(): void
    {
        \App\Models\FormDefinition::create([
            'title' => 'Zgłoszenie', 'slug' => 'zgloszenie-test',
            'fields' => [['label' => 'Imię', 'type' => 'text', 'required' => true], ['label' => 'Treść', 'type' => 'textarea']],
            'is_active' => true,
        ]);

        $this->get('/formularz/zgloszenie-test')->assertOk()->assertSee('border-gray-300 bg-white', false)->assertDontSee('border-0 border-b-2', false);

        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();

        $html = $this->get('/formularz/zgloszenie-test')->assertOk()->getContent();
        $this->assertStringContainsString('border-0 border-b-2', $html);
        $this->assertStringContainsString('bg-gray-100', $html);
        $this->assertStringNotContainsString('rounded-xl border border-gray-200 bg-white p-6 shadow-sm', $html);
    }

    public function test_strona_dostepnosc_z_kafelkowym_menu_podstron_pokazuje_kafle(): void
    {
        $parent = \App\Models\Page::create(['title' => 'Dostępność', 'slug' => 'dostepnosc-test', 'type' => 'links_hub', 'is_published' => true, 'side_nav_style' => 'tiles', 'show_side_nav' => true]);
        \App\Models\Page::create(['parent_id' => $parent->id, 'title' => 'Dostępność cyfrowa', 'slug' => 'dostepnosc-cyfrowa-test', 'type' => 'standard', 'is_published' => true, 'order' => 1]);

        $this->get('/dostepnosc-test')->assertOk()->assertSee('Dostępność cyfrowa')->assertSee('aria-label', false);
        $this->assertSame('tiles', $parent->fresh()->sideNavStyle());
    }
}
