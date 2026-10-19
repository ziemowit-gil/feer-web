<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mój profil → Menu panelu: styl menu bocznego (User::ADMIN_NAV_STYLES) — wysuwane albo rozwijane. */
class AdminNavStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_domyslnie_menu_wysuwane(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertSame('flyout', $admin->adminNavStyle());
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('admin-sidebar nav-style-flyout', false)
            ->assertSee("style: 'flyout'", false);
    }

    public function test_uzytkownik_wybiera_menu_rozwijane_w_profilu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()
            ->assertSee('id="menu-panelu"', false)
            ->assertSee('Styl menu bocznego')
            ->assertSee('Rozwijane');

        $this->actingAs($admin)->patch(route('profile.nav-style'), ['admin_nav_style' => 'accordion'])
            ->assertRedirect(route('profile.edit').'#menu-panelu')
            ->assertSessionHas('status', 'Styl menu panelu: Rozwijane.');
        $this->assertSame('accordion', $admin->fresh()->admin_nav_style);

        $this->actingAs($admin->fresh())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('admin-sidebar nav-style-accordion', false)
            ->assertSee("style: 'accordion'", false);
    }

    public function test_nieznany_styl_jest_odrzucany(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'admin_nav_style' => 'cokolwiek']);

        $this->assertSame('flyout', $editor->adminNavStyle());
        $this->actingAs($editor)->patch(route('profile.nav-style'), ['admin_nav_style' => 'mega'])
            ->assertSessionHasErrors('admin_nav_style');
    }
}
