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
}
