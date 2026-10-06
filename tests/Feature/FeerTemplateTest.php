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
}
