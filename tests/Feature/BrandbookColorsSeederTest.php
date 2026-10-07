<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use Database\Seeders\BrandbookColorsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandbookColorsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_seeder_podmienia_kolory_na_palete_brandbooka_i_jest_idempotentny(): void
    {
        SiteSetting::current()->update(['brand_color' => '#c31432']);
        foreach (['Facebook', 'Archiwum', 'Panel', 'Czwarta'] as $i => $label) {
            QuickAction::create(['label' => $label, 'url' => '/'.$i, 'icon' => 'fa-solid fa-link', 'color' => 'red', 'order' => $i]);
        }
        foreach (['Fundacja', 'Działania'] as $i => $label) {
            NavItem::create(['label' => $label, 'url' => '/'.$i, 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => $i]);
        }
        NavItem::create(['label' => 'Własny', 'url' => '/w', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 5, 'accent_color' => '#123456']);
        NavItem::create(['label' => 'Przycisk', 'url' => '/p', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 6, 'is_button' => true]);

        $this->seed(BrandbookColorsSeeder::class);
        $this->seed(BrandbookColorsSeeder::class); // drugi raz — ten sam wynik

        $site = SiteSetting::query()->first();
        $this->assertSame('#1e6dff', $site->brand_color);
        $this->assertSame('#ea8f00', $site->brand_color_2);
        $this->assertSame('#1d1d1a', $site->brand_color_3);
        $this->assertSame('#cbd5e7', $site->brand_color_4);
        $this->assertSame('#ea8f00', $site->ngo_color);

        $this->assertSame(['#1e6dff', '#1d1d1a', '#ea8f00', '#1e6dff'], QuickAction::orderBy('order')->pluck('color')->all());

        $nav = NavItem::orderBy('order')->get()->keyBy('label');
        $this->assertSame('#1e6dff', $nav['Fundacja']->accent_color);
        $this->assertSame('#ea8f00', $nav['Działania']->accent_color);
        $this->assertSame('#123456', $nav['Własny']->accent_color, 'własny kolor pozycji zostaje');
        $this->assertNull($nav['Przycisk']->accent_color, 'przyciski CTA bez koloru pozycji');
    }

    public function test_kolor_pozycji_menu_jest_wstrzykiwany_do_menu_glownego(): void
    {
        NavItem::create(['label' => 'Fundacja', 'url' => '/fundacja', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1, 'accent_color' => '#ea8f00']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-nav-accent', $html);
        $this->assertStringContainsString('--nav-accent: #ea8f00', $html);
        $this->assertStringContainsString('--nav-accent-text:', $html);
    }
}
