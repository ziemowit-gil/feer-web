<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Podgląd menu w panelu: sam nagłówek albo sama stopka, bez treści strony głównej. */
class NavItemPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_podglad_pokazuje_tylko_naglowek_albo_tylko_stopke(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        NavItem::create(['label' => 'Pozycja w nagłówku', 'url' => '/a', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $main = $this->actingAs($admin)->get(route('admin.pozycje-menu.podglad', ['location' => 'main']))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')->getContent();
        $this->assertStringContainsString('<header', $main);
        $this->assertStringContainsString('Pozycja w nagłówku', $main);
        $this->assertStringNotContainsString('<footer', $main);
        $this->assertStringNotContainsString('id="push-prompt"', $main);

        $footer = $this->actingAs($admin)->get(route('admin.pozycje-menu.podglad', ['location' => 'footer']))->assertOk()->getContent();
        $this->assertStringContainsString('<footer', $footer);
        $this->assertStringNotContainsString('<header', $footer);

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index'))->assertOk()
            ->assertSee('data-src="'.route('admin.pozycje-menu.podglad', ['location' => 'main']).'"', false);
        $this->get(route('home'))->assertOk()->assertSee('<footer', false);
    }
}
