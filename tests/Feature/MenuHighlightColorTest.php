<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kolor wyróżnienia pozycji menu (górne i boczne) ustawiany w panelu. */
class MenuHighlightColorTest extends TestCase
{
    use RefreshDatabase;

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_highlight_color_from_settings_is_used_in_menu(): void
    {
        $this->reset();
        SiteSetting::current()->update(['menu_highlight_color' => '#1e6dff', 'brand_color' => '#123456']);
        $this->reset();
        NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('--color-menu-highlight:', $html);
        $this->assertStringContainsString('.menu-hl {', $html);
        // Kolor jest dobierany do kontrastu WCAG (może być lekko przyciemniony), więc sprawdzamy format.
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', SiteSetting::current()->menuHighlightColor());
    }

    public function test_falls_back_to_brand_color_when_empty(): void
    {
        $this->reset();
        SiteSetting::current()->update(['menu_highlight_color' => null, 'brand_color' => '#0b5394', 'brand_skip_contrast' => true]);
        $this->reset();
        $this->assertSame('#0b5394', SiteSetting::current()->menuHighlightColor());
    }


    public function test_single_item_is_highlighted_in_top_menu(): void
    {
        $this->reset();
        NavItem::create(['label' => 'Zwykła', 'url' => '/zwykla', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1, 'highlight' => false]);
        NavItem::create(['label' => 'Ważna sekcja', 'url' => '/wazna', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 2, 'highlight' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~<span class="menu-hl font-bold"\s*>Ważna sekcja</span>~', $html);
        $this->assertDoesNotMatchRegularExpression('~<span class="menu-hl font-bold"\s*>Zwykła</span>~', $html);
    }
}
