<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tabela modułów instalowanych (rozszerzeń, cała instalacja) w Ustawienia → Moduły i na stronie „Moduły systemu”:
 * zaznaczanie wielu wierszy i akcje masowe Aktywuj / Dezaktywuj zaznaczone (ModuleController::bulk).
 */
class PluginModulesBulkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Dwa aktywne rozszerzenia do testów akcji. @return array<int, string> */
    private function twoActive(): array
    {
        $manager = app(ModuleManager::class);
        $ids = $manager->all()->keys()->filter(fn ($id) => $manager->isActive($id))->reject(fn ($id) => in_array($id, ['bip', 'blog'], true))->take(2)->values()->all();
        if (count($ids) < 2) {
            $this->markTestSkipped('Za mało aktywnych rozszerzeń w tym środowisku.');
        }

        return $ids;
    }

    public function test_tabela_rozszerzen_w_ustawieniach_ma_pola_wyboru_wersje_i_formularz_poza_ustawieniami(): void
    {
        $manager = app(ModuleManager::class);
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'modules']))->assertOk()->getContent();

        $this->assertStringContainsString('To moduły całej instalacji', $html);
        $this->assertStringContainsString('<caption class="sr-only">Moduły instalowane (całej instalacji)', $html);
        $this->assertStringContainsString('Zaznacz wszystkie widoczne moduły instalowane', $html);
        $this->assertStringContainsString('Aktywuj zaznaczone', $html);
        $this->assertStringContainsString('Dezaktywuj zaznaczone', $html);
        $this->assertStringContainsString('>Wersja</th>', $html);

        foreach ($manager->all() as $id => $manifest) {
            $this->assertStringContainsString('form="plugins-bulk-form" name="modules[]" value="'.$id.'"', $html);
            $this->assertStringContainsString('Zaznacz moduł: '.e($manifest->name), $html);
            $this->assertStringContainsString('v'.e($manifest->version), $html);
            // Przycisk w wierszu dalej korzysta z formularza modułu (z potwierdzeniem przy dezaktywacji).
            $this->assertStringContainsString('form="plugin-module-form-'.$id.'"', $html);
        }

        $settingsFormEnd = strpos($html, '</form>', strpos($html, 'action="'.route('admin.ustawienia.update').'"'));
        $bulkPos = strpos($html, '<form id="plugins-bulk-form" method="POST" action="'.route('admin.moduly.masowo').'"');
        $this->assertNotFalse($bulkPos);
        $this->assertGreaterThan($settingsFormEnd, $bulkPos);
        $this->assertMatchesRegularExpression('/<form id="plugins-bulk-form"[^>]*>\s*<input type="hidden" name="_token"[^>]*>\s*<input type="hidden" name="return_to" value="settings">/', $html);
    }

    public function test_strona_modulow_systemu_ma_te_sama_tabele_i_zaznacz_wszystkie_bez_js(): void
    {
        $manager = app(ModuleManager::class);
        $html = $this->actingAs($this->admin())->get(route('admin.moduly.index', ['zaznacz' => 'rozszerzenia']))->assertOk()->getContent();

        $this->assertStringContainsString('<form id="plugins-bulk-form" method="POST" action="'.route('admin.moduly.masowo').'"', $html);
        foreach ($manager->all()->keys() as $id) {
            $this->assertMatchesRegularExpression('/name="modules\[\]" value="'.preg_quote($id, '/').'" x-model="psel"\s+checked/', $html);
        }
    }

    public function test_masowa_dezaktywacja_i_aktywacja(): void
    {
        $manager = app(ModuleManager::class);
        [$a, $b] = $this->twoActive();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.moduly.masowo'), ['action' => 'deactivate', 'modules' => [$a, $b], 'return_to' => 'settings'])
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'modules']).'#tabela-rozszerzen')
            ->assertSessionHas('plugins_table_status', fn ($m) => str_starts_with($m, 'Dezaktywowano 2 moduły: '));

        $this->assertFalse($manager->isActive($a));
        $this->assertFalse($manager->isActive($b));
        $this->assertDatabaseHas('plugins', ['identifier' => $a, 'status' => 'inactive']);

        // Komunikat po akcji przy tabeli.
        $this->actingAs($admin)->get(route('admin.moduly.index'))->assertOk();
        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.moduly.masowo'), ['action' => 'activate', 'modules' => [$a, $b]])
            ->assertSee('id="plugins-table-status" role="status"', false)
            ->assertSee('Aktywowano 2 moduły: ');

        $this->assertTrue($manager->isActive($a));
        $this->assertTrue($manager->isActive($b));
        $this->assertDatabaseHas('plugins', ['identifier' => $b, 'status' => 'active']);
    }

    public function test_bez_zaznaczenia_albo_z_nieznana_akcja_jest_blad(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.moduly.masowo'), ['action' => 'deactivate'])
            ->assertRedirect(route('admin.moduly.index').'#tabela-rozszerzen')
            ->assertSessionHasErrors(['modules'], null, 'pluginsTable');

        $this->actingAs($admin)->post(route('admin.moduly.masowo'), ['action' => 'deactivate', 'modules' => ['nie-ma-takiego']])
            ->assertSessionHasErrors(['modules'], null, 'pluginsTable');

        $this->actingAs($admin)->post(route('admin.moduly.masowo'), ['action' => 'usun', 'modules' => $this->twoActive()])
            ->assertSessionHasErrors(['modules'], null, 'pluginsTable');

        $this->actingAs($admin)->followingRedirects()->post(route('admin.moduly.masowo'), ['action' => 'activate'])
            ->assertSee('id="plugins-table-error" role="alert"', false);
    }

    public function test_redaktor_nie_moze_zmieniac_rozszerzen(): void
    {
        [$a] = $this->twoActive();
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $response = $this->actingAs($editor)->post(route('admin.moduly.masowo'), ['action' => 'deactivate', 'modules' => [$a]]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertTrue(app(ModuleManager::class)->isActive($a));
    }
}
