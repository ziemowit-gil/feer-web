<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Redirect;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectAndCookieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_active_redirect_sends_301(): void
    {
        Redirect::create(['from_path' => '/stary', 'to_url' => '/nowy', 'is_active' => true]);

        $this->get('/stary')->assertStatus(301)->assertRedirect('/nowy');
        $this->assertSame(1, Redirect::first()->fresh()->hits);
    }

    public function test_inactive_redirect_is_ignored(): void
    {
        Redirect::create(['from_path' => '/stary', 'to_url' => '/nowy', 'is_active' => false]);

        // Brak strony i nieaktywne przekierowanie → normalne 404.
        $this->get('/stary')->assertNotFound();
    }

    public function test_admin_can_add_redirect_and_paths_are_normalized(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->post(route('admin.przekierowania.store'), ['from_path' => 'bez-ukosnika/', 'to_url' => '/cel', 'is_active' => '1'])
            ->assertRedirect(route('admin.przekierowania.index'));

        $this->assertDatabaseHas('redirects', ['from_path' => '/bez-ukosnika', 'to_url' => '/cel']);
    }

    public function test_cookie_banner_shows_when_enabled(): void
    {
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
        SiteSetting::current()->update(['cookie_banner_enabled' => true]);

        $this->get('/start')->assertOk()->assertSee('plików cookies')->assertSee('Akceptuję');
    }

    public function test_cookie_banner_hidden_when_disabled(): void
    {
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
        SiteSetting::current()->update(['cookie_banner_enabled' => false]);

        $this->get('/start')->assertOk()->assertDontSee('data-cookie-banner', false);
    }
}
