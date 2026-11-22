<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduły „Podcasty” (podcasts) i „Osoby” (persons) w Ustawienia → Moduły: domyślnie włączone, wyłączone znikają z panelu. */
class PodcastsPersonsModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
    }

    /** @return array<int, string> */
    private function menuKeys(User $user): array
    {
        $this->reset();

        return collect(AdminMenu::for($user))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
    }

    public function test_klucze_modulow_sa_na_liscie_z_opisem(): void
    {
        $this->assertSame('Podcasty (odcinki i strona podcastów)', SiteSetting::MODULES['podcasts']);
        $this->assertSame('Osoby (strony osób i ich lista w panelu)', SiteSetting::MODULES['persons']);
        $this->assertArrayHasKey('podcasts', SiteSetting::MODULE_META);
        $this->assertArrayHasKey('persons', SiteSetting::MODULE_META);
    }

    public function test_domyslnie_wlaczone_a_po_wylaczeniu_znikaja_z_menu_i_panelu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $keys = $this->menuKeys($admin);
        $this->assertContains('podcasts', $keys);
        $this->assertContains('persons', $keys);
        $this->get('/podcasty')->assertOk();

        // Wyłączenie akcją masową w Ustawienia → Moduły.
        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'disable', 'modules' => ['podcasts', 'persons']])
            ->assertSessionHas('modules_table_status');
        $this->reset();
        $this->assertEqualsCanonicalizing(['podcasts', 'persons'], SiteSetting::current()->disabled_modules);

        $keys = $this->menuKeys($admin);
        $this->assertNotContains('podcasts', $keys);
        $this->assertNotContains('persons', $keys);
        $this->assertContains('pages', $keys, 'Strony zostają.');

        $this->actingAs($admin)->get(route('admin.podcasty.index'))->assertSee('Moduł dezaktywowany')->assertSee('w zakładce Moduły');
        $this->actingAs($admin)->get(route('admin.osoby.index'))->assertSee('Moduł dezaktywowany');
        $this->actingAs($admin)->get(route('admin.osoby.create'))->assertSee('Moduł dezaktywowany');

        // Strona publiczna podcastów: 404.
        $this->get('/podcasty')->assertNotFound();
        $this->get('/podcasty/dowolny')->assertNotFound();
    }

    public function test_podcasty_mozna_nadac_grupie_a_osoby_ida_za_stronami(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.grupy.create'))->assertOk()->getContent();

        $this->assertStringContainsString('value="podcasts"', $html);
        $this->assertStringNotContainsString('value="persons"', $html);
    }
}
