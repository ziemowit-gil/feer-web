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

        $this->get('/')->assertOk()->assertSee('--color-brand: #1b66f5', false)->assertSee('--color-brand-4: #cbd5e7', false);
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

        foreach (['--color-brand', '--color-brand-dark'] as $var) {
            $this->assertSame(1, preg_match('/'.preg_quote($var, '/').':\s*(#[0-9a-fA-F]{6})/', $css, $m), $var);
            $ratio = 1.05 / ($lum($m[1]) + 0.05);
            $this->assertGreaterThanOrEqual(4.5, $ratio, "{$var} {$m[1]} ma kontrast {$ratio}:1 na bieli");
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
        $this->assertStringContainsString('border-l-4 border-brand', $html);
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
        $this->assertStringContainsString('sm:grid-cols-3', $html);
        $this->assertStringContainsString('divide-y divide-gray-200', $html);
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
            ->assertSee('id="formularz-heading"', false)->assertSee('601 350 487')->assertSee('PL12 3456')->assertSee('contact-feer', false);
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
}
