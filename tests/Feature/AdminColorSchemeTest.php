<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mój profil → Wygląd panelu: schematy kolorów panelu (User::ADMIN_SCHEMES) na motywie Joomla. */
class AdminColorSchemeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN] + $attrs);
    }

    public function test_domyslnie_krem_z_motywem_joomla(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<body data-scheme="krem" class="admin-joomla', false);
    }

    public function test_wybor_schematu_w_profilu_zmienia_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()
            ->assertSee('id="wyglad-panelu"', false)
            ->assertSee('<legend class="text-sm font-bold text-ink">Schemat kolorów panelu</legend>', false)
            ->assertSee('Szałwiowy');

        $this->actingAs($admin)->patch(route('profile.scheme'), ['admin_scheme' => 'szalwia'])
            ->assertRedirect(route('profile.edit').'#wyglad-panelu')
            ->assertSessionHas('status', 'Schemat kolorów panelu: Szałwiowy.');
        $this->assertSame('szalwia', $admin->fresh()->admin_scheme);

        $this->actingAs($admin->fresh())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<body data-scheme="szalwia" class="admin-joomla', false)
            ->assertSee('.admin-joomla[data-scheme="szalwia"]', false);
    }

    public function test_pierwotny_to_panel_sprzed_motywu_i_zly_schemat_odrzucony(): void
    {
        $admin = $this->admin(['admin_scheme' => 'pierwotny']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<body data-scheme="pierwotny" class="admin-joomla flex', false)
            ->assertSee('.admin-joomla[data-scheme="pierwotny"]', false);

        $this->actingAs($admin)->from(route('profile.edit'))->patch(route('profile.scheme'), ['admin_scheme' => 'neon'])
            ->assertSessionHasErrors('admin_scheme');
        $this->assertSame('pierwotny', $admin->fresh()->admin_scheme);

        // Nieznana wartość w bazie → schemat domyślny.
        $admin->forceFill(['admin_scheme' => 'stary'])->save();
        $this->assertSame('krem', $admin->fresh()->adminScheme());
    }

    public function test_admin_zmienia_schemat_innej_osoby_w_edycji_uzytkownika(): void
    {
        $admin = $this->admin();
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($admin)->get(route('admin.uzytkownicy.edit', $editor))->assertOk()
            ->assertSee('<label for="admin_scheme"', false)->assertSee('Niebieski — ', false);

        $this->actingAs($admin)->put(route('admin.uzytkownicy.update', $editor), [
            'name' => $editor->name, 'email' => $editor->email, 'role' => $editor->role, 'admin_scheme' => 'niebieski',
        ])->assertSessionHasNoErrors();
        $this->assertSame('niebieski', $editor->fresh()->admin_scheme);

        $this->actingAs($admin)->from(route('admin.uzytkownicy.edit', $editor))->put(route('admin.uzytkownicy.update', $editor), [
            'name' => $editor->name, 'email' => $editor->email, 'role' => $editor->role, 'admin_scheme' => 'neon',
        ])->assertSessionHasErrors('admin_scheme');
    }
}
