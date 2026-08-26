<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateNgoMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_mixed_template_is_offered_in_settings(): void
    {
        $this->assertArrayHasKey('ngo_mix', SiteSetting::SITE_TEMPLATES);
    }

    public function test_template_tab_shows_settings_of_the_selected_template_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()->getContent();

        // Wybór szablonu steruje widocznością sekcji z jego ustawieniami.
        $this->assertStringContainsString('x-model="tpl"', $html);
        $this->assertStringContainsString('x-show="tpl === \'municipality\'"', $html);
        $this->assertStringContainsString('x-show="tpl === \'ngo_mix\'"', $html);
        $this->assertStringContainsString('x-show="tpl === \'ngo\'"', $html);
        $this->assertStringContainsString('x-show="tpl === \'default\'"', $html);
    }

    public function test_expanded_ngo_template_lists_three_news(): void
    {
        SiteSetting::current()->forceFill(['site_template' => 'ngo'])->save();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        foreach (range(1, 5) as $i) {
            \App\Models\News::create([
                'title' => "Wpis $i",
                'slug' => "wpis-$i",
                'is_published' => true,
                'published_at' => now()->subDays($i),
            ]);
        }

        $response = $this->get('/')->assertOk();

        // Czwarta i piąta aktualność nie wchodzą już na stronę główną.
        $response->assertSee('Wpis 1')->assertSee('Wpis 2')->assertSee('Wpis 3')
            ->assertDontSee('Wpis 4')->assertDontSee('Wpis 5');
    }
}
