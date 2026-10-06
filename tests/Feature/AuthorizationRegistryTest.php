<?php

namespace Tests\Feature;

use App\Models\Authorization;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Publiczny rejestr pełnomocnictw i upoważnień: render strony, wyszukiwarka,
 * filtr rodzaju, statusy ważności i wyłączanie modułu.
 */
class AuthorizationRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function makeEntry(array $overrides = []): Authorization
    {
        return Authorization::create(array_merge([
            'type'            => 'pelnomocnictwo',
            'document_number' => 'PEL/2026/' . fake()->unique()->numberBetween(1, 999),
            'principal'       => 'Zarząd Fundacji',
            'grantee_name'    => 'Anna Kowalska',
            'scope'           => 'Reprezentowanie przed urzędami',
            'valid_from'      => now()->subMonth()->toDateString(),
            'valid_to'        => now()->addMonth()->toDateString(),
            'is_active'       => true,
        ], $overrides));
    }

    public function test_registry_page_renders_with_entries(): void
    {
        $this->makeEntry(['document_number' => 'PEL/2026/100']);

        $this->get(route('authorizations.index'))
            ->assertOk()
            ->assertSee('Rejestr pełnomocnictw i upoważnień')
            ->assertSee('PEL/2026/100')
            ->assertSee('Aktywne');
    }

    public function test_search_matches_name_number_and_scope(): void
    {
        $this->makeEntry(['document_number' => 'PEL/2026/101', 'grantee_name' => 'Jan Nowak']);
        $this->makeEntry(['document_number' => 'UPW/2026/5', 'type' => 'upowaznienie', 'scope' => 'Odbiór korespondencji']);

        $this->get(route('authorizations.index', ['q' => 'Nowak']))
            ->assertSee('PEL/2026/101')->assertDontSee('UPW/2026/5');

        $this->get(route('authorizations.index', ['q' => 'korespondencji']))
            ->assertSee('UPW/2026/5')->assertDontSee('PEL/2026/101');

        $this->get(route('authorizations.index', ['typ' => 'upowaznienie']))
            ->assertSee('UPW/2026/5')->assertDontSee('PEL/2026/101');
    }

    public function test_status_reflects_validity_period_and_revocation(): void
    {
        $this->assertSame('active', $this->makeEntry(['valid_to' => null])->status());
        $this->assertSame('expired', $this->makeEntry(['valid_from' => '2020-01-01', 'valid_to' => '2020-12-31'])->status());
        $this->assertSame('upcoming', $this->makeEntry(['valid_from' => now()->addMonth()->toDateString()])->status());
        $this->assertSame('revoked', $this->makeEntry(['is_active' => false])->status());
    }

    public function test_expired_and_revoked_entries_show_labels(): void
    {
        $this->makeEntry(['valid_from' => '2020-01-01', 'valid_to' => '2020-12-31']);
        $this->makeEntry(['is_active' => false]);

        $this->get(route('authorizations.index'))
            ->assertSee('Wygasło')
            ->assertSee('Unieważnione');
    }

    public function test_module_can_be_disabled(): void
    {
        SiteSetting::current()->update(['disabled_modules' => ['authorizations']]);

        $this->get(route('authorizations.index'))->assertNotFound();
    }

    // ── Panel administracyjny: CRUD wpisów ─────────────────────────────

    private function admin(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
    }

    public function test_admin_can_create_entry_via_panel(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pelnomocnictwa.store'), [
                'type'            => 'upowaznienie',
                'document_number' => 'UPW/2026/99',
                'principal'       => 'Zarząd Fundacji',
                'grantee_name'    => 'Ewa Testowa',
                'scope'           => 'Odbiór przesyłek',
                'valid_from'      => '2026-01-01',
                'valid_to'        => '',
                'is_active'       => '1',
            ])
            ->assertRedirect(route('admin.pelnomocnictwa.index'));

        $this->assertDatabaseHas('authorizations', ['document_number' => 'UPW/2026/99', 'valid_to' => null]);
    }

    public function test_admin_form_validates_unique_number_and_date_order(): void
    {
        $this->makeEntry(['document_number' => 'PEL/2026/1']);

        $this->actingAs($this->admin())
            ->from(route('admin.pelnomocnictwa.create'))
            ->post(route('admin.pelnomocnictwa.store'), [
                'type'            => 'pelnomocnictwo',
                'document_number' => 'PEL/2026/1',
                'principal'       => 'Zarząd',
                'grantee_name'    => 'Jan Testowy',
                'scope'           => 'Zakres',
                'valid_from'      => '2026-05-01',
                'valid_to'        => '2026-04-01',
            ])
            ->assertSessionHasErrors(['document_number', 'valid_to']);
    }

    public function test_admin_can_update_toggle_and_delete_entry(): void
    {
        $admin = $this->admin();
        $entry = $this->makeEntry(['document_number' => 'PEL/2026/7']);

        $this->actingAs($admin)
            ->put(route('admin.pelnomocnictwa.update', $entry), [
                'type'            => 'pelnomocnictwo',
                'document_number' => 'PEL/2026/7',
                'principal'       => 'Rada Fundacji',
                'grantee_name'    => 'Nowa Osoba',
                'scope'           => 'Nowy zakres',
                'valid_from'      => '2026-01-01',
                'valid_to'        => '',
                'is_active'       => '1',
            ])
            ->assertRedirect(route('admin.pelnomocnictwa.index'));
        $this->assertSame('Nowa Osoba', $entry->fresh()->grantee_name);

        $this->actingAs($admin)->patch(route('admin.pelnomocnictwa.aktywnosc', $entry));
        $this->assertFalse($entry->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.pelnomocnictwa.destroy', $entry));
        $this->assertDatabaseMissing('authorizations', ['id' => $entry->id]);
    }

    public function test_panel_registry_is_admin_only(): void
    {
        $editor = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_EDITOR]);

        $this->actingAs($editor)->get(route('admin.pelnomocnictwa.index'))->assertForbidden();
    }
}
