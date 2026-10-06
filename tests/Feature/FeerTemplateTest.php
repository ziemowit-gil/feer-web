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

        $this->get('/')->assertOk()->assertSee('Szybkie akcje')->assertSee('Zgłoś barierę');

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
}
