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
}
