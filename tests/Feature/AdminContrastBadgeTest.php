<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Panel pokazuje plakietkę, gdy w przeglądarce włączono skalę szarości lub kontrast, z przyciskiem wyłączenia. */
class AdminContrastBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_ma_plakietke_trybu_kontrastu_z_przyciskiem_wylaczenia(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('id="a11y-mode-badge"', false)->assertSee('role="status"', false)
            ->assertSee('Włączona skala szarości')->assertSee('id="a11y-mode-badge-off"', false)
            ->assertSee("localStorage.setItem('a11y-contrast-mode', '')", false);
    }
}
