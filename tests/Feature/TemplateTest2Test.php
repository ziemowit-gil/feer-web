<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NavItem;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest2Test extends TestCase
{
    use RefreshDatabase;

    private function resetSettingsCache(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_template_is_offered_in_settings(): void
    {
        $this->assertArrayHasKey('test2', SiteSetting::SITE_TEMPLATES);
    }

    public function test_home_renders_header_tiles_news_and_footer(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test2', 'contact_phone' => '12 616 7555'])->save();
        $this->resetSettingsCache();

        foreach (range(1, 4) as $i) {
            QuickAction::create(['label' => "Skrót $i", 'url' => "/s$i", 'icon' => 'fa-solid fa-car', 'order' => $i]);
        }
        News::create(['title' => 'Wpis testowy', 'slug' => 'wpis-testowy', 'is_published' => true, 'published_at' => now()]);
        NavItem::create(['label' => 'Parkowanie', 'url' => '/parkowanie', 'location' => 'main', 'type' => 'link', 'is_active' => true, 'order' => 1]);

        $this->get('/')->assertOk()
            ->assertSee('z2-header', false)
            ->assertSee('Parkowanie')
            ->assertSee('family=Roboto', false)
            ->assertSee('font-family: Roboto', false)
            ->assertSee('Skrót 1')
            ->assertSee('Skrót 4')
            ->assertSee('Wpis testowy')
            ->assertSee('Wszystkie aktualności')
            ->assertSee('z2-footer', false)
            ->assertSee('12 616 7555');
    }

    public function test_inner_page_uses_template_header(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test2'])->save();
        $this->resetSettingsCache();

        $this->get('/kontakt')->assertOk()->assertSee('z2-header', false)
            ->assertSee('template-test2', false)
            ->assertSee('.template-test2 main details', false)
            ->assertSee('details > summary', false);
    }

    public function test_other_templates_have_no_test2_body_class_or_styles(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'default'])->save();
        $this->resetSettingsCache();

        $this->get('/kontakt')->assertOk()->assertDontSee('template-test2', false);
    }

    public function test_home_renders_with_mission_slide_without_image(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test2'])->save();
        $this->resetSettingsCache();

        $slides = collect([(object) ['mission_text' => 'Nasza misja', 'logo_url' => null, 'site_name' => 'FEER', 'mission_bg' => 'brand']]);

        $this->view('templates.test2.partials.home.hero', ['slides' => $slides, 'quickLinks' => collect(), 'siteSettings' => SiteSetting::current()])
            ->assertSee('Nasza misja');
    }

    public function test_plain_submenu_is_vertical_and_mega_item_gets_mega_panel(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test2'])->save();
        $this->resetSettingsCache();

        $plain = NavItem::create(['label' => 'Parkowanie', 'url' => '#', 'location' => 'main', 'type' => 'dropdown', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Strefa A', 'url' => '/strefa-a', 'location' => 'main', 'type' => 'link', 'parent_id' => $plain->id, 'is_active' => true, 'order' => 1]);

        $mega = NavItem::create(['label' => 'Nasze działania', 'url' => '#', 'location' => 'main', 'type' => 'dropdown', 'is_mega' => true, 'is_active' => true, 'order' => 2]);
        NavItem::create(['label' => 'Inwestycje', 'url' => '#', 'location' => 'main', 'type' => 'link', 'is_column_heading' => true, 'parent_id' => $mega->id, 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Budowa KST', 'url' => '/kst', 'location' => 'main', 'type' => 'link', 'parent_id' => $mega->id, 'is_active' => true, 'order' => 2]);
        NavItem::create(['label' => 'Utrzymanie', 'url' => '#', 'location' => 'main', 'type' => 'link', 'is_column_heading' => true, 'parent_id' => $mega->id, 'is_active' => true, 'order' => 3]);
        NavItem::create(['label' => 'Remonty', 'url' => '/remonty', 'location' => 'main', 'type' => 'link', 'parent_id' => $mega->id, 'is_active' => true, 'order' => 4]);

        $html = $this->get('/')->assertOk()
            ->assertSee('Strefa A')
            ->assertSee('Budowa KST')
            ->assertSee('Utrzymanie')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="z2-panel"'));
        $this->assertSame(1, substr_count($html, 'class="z2-mega-panel"'));
        $this->assertSame(2, substr_count($html, 'class="z2-mega-h"'));
    }

    public function test_slider_ma_przycisk_wstrzymania_tylko_przy_wielu_slajdach(): void
    {
        $this->resetSettingsCache();
        SiteSetting::current()->forceFill(['site_template' => 'test2'])->save();
        $this->resetSettingsCache();
        $slide = fn (string $title) => (object) ['title' => $title, 'image_url' => null, 'button_url' => '/x'];
        $render = fn ($slides) => $this->view('templates.test2.partials.home.hero', ['slides' => $slides, 'quickLinks' => collect(), 'siteSettings' => SiteSetting::current()]);

        $render(collect([$slide('Pierwszy'), $slide('Drugi')]))->assertSee('Wstrzymaj automatyczną zmianę slajdów');
        $render(collect([$slide('Jedyny')]))->assertDontSee('Wstrzymaj automatyczną zmianę slajdów');
    }
}
