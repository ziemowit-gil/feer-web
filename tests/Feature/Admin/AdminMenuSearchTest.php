<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_menu_panelu_ma_wyszukiwarke_z_pozycjami_menu(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Szukaj w menu', false)
            ->assertSee('id="admin-menu-search"', false)
            ->assertSee('Wiadomości kontaktowe');
    }
}
