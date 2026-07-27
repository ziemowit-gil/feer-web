<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsplashKeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_panel_key_takes_precedence_and_falls_back_to_config(): void
    {
        config(['services.unsplash.access_key' => 'ENV-KEY']);

        // Bez wartości w panelu → fallback do .env/config.
        $this->assertSame('ENV-KEY', SiteSetting::current()->unsplashAccessKey());

        // Z wartością w panelu → panel wygrywa.
        SiteSetting::current()->update(['unsplash_access_key' => 'PANEL-KEY']);
        $this->assertSame('PANEL-KEY', SiteSetting::current()->fresh()->unsplashAccessKey());
    }

    public function test_blank_submit_keeps_existing_key(): void
    {
        SiteSetting::current()->update(['unsplash_access_key' => 'SEKRET']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.ustawienia.update'), [
                'site_name' => 'FEER', 'brand_color' => '#c31432', 'header_layout' => 'classic',
                'content_editor' => 'tinymce', 'mail_transport' => 'default',
                'contact_address' => 'X', 'contact_city' => 'Y', 'contact_email' => 'a@b.pl',
                'unsplash_access_key' => '', // puste → nie zmieniaj
            ])
            ->assertRedirect();

        $this->assertSame('SEKRET', SiteSetting::current()->fresh()->unsplashAccessKey());
    }
}
