<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_admin_sidebar_renders_with_skrzynka(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Skrzynka')
            ->assertSee('Oś czasu (historia)')
            ->assertSee('Zgłoszenia (spotkania)')
            ->assertSee('Wolontariat')
            ->assertSee('Komentarze (blog)');
    }

    public function test_editor_sidebar_renders_without_error(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk();
    }
}
