<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use App\View\Components\BannerZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pozycje Działalności (Portale, Koordynatorzy) i Komunikacji (skrzynki, Bannery, Zbiórki, Wpłaty) jako osobne
 * moduły w Ustawienia → Moduły: wyłączony moduł chowa pozycję w menu panelu i blokuje jej ekrany.
 */
class ActivityCommsModulesTest extends TestCase
{
    use RefreshDatabase;

    /** Klucz modułu → klucz pozycji w menu panelu. */
    private const MENU_ITEMS = [
        'portals' => 'portals',
        'coordinators' => 'coordinators',
        'contact_inbox' => 'contact',
        'meetings' => 'meetings',
        'barriers' => 'barriers',
        'banners' => 'banners',
        'campaigns' => 'campaigns',
        'donations' => 'donations',
    ];

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

    private function disable(array $modules): void
    {
        SiteSetting::current()->update(['disabled_modules' => $modules]);
        $this->reset();
    }

    /** @return array<int, string> */
    private function menuItemKeys(User $user): array
    {
        return collect(AdminMenu::for($user))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
    }

    public function test_nowe_moduly_sa_na_liscie_z_opisem_i_bez_uprawnien_grup(): void
    {
        foreach (array_keys(self::MENU_ITEMS) as $module) {
            $this->assertArrayHasKey($module, SiteSetting::MODULES);
            $this->assertArrayHasKey($module, SiteSetting::MODULE_META);
            $this->assertContains($module, SiteSetting::MODULES_WITHOUT_GROUP_ACCESS);
        }
    }

    public function test_wlaczone_moduly_maja_pozycje_w_menu_a_wylaczone_nie(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $items = $this->menuItemKeys($admin);
        foreach (self::MENU_ITEMS as $module => $item) {
            $this->assertContains($item, $items, "Brak pozycji „{$item}” przy włączonym module {$module}.");
        }

        foreach (self::MENU_ITEMS as $module => $item) {
            $this->disable([$module]);
            $items = $this->menuItemKeys($admin);
            $this->assertNotContains($item, $items, "Pozycja „{$item}” została mimo wyłączonego modułu {$module}.");
            // Pozostałe pozycje zostają.
            foreach (array_diff(self::MENU_ITEMS, [$item]) as $other) {
                $this->assertContains($other, $items);
            }
        }

        // Działania zostają, gdy wyłączone są tylko Portale i Koordynatorzy.
        $this->disable(['portals', 'coordinators']);
        $this->assertContains('projects', $this->menuItemKeys($admin));
    }

    public function test_ekrany_wylaczonego_modulu_pokazuja_modul_wylaczony(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $routes = [
            'portals' => 'admin.portale.index',
            'coordinators' => 'admin.koordynatorzy.index',
            'contact_inbox' => 'admin.wiadomosci-kontaktowe.index',
            'meetings' => 'admin.zgloszenia-spotkania.index',
            'barriers' => 'admin.zgloszenia-barier.index',
            'banners' => 'admin.banery.index',
            'campaigns' => 'admin.kampanie.index',
            'donations' => 'admin.darowizny.index',
        ];

        $this->disable(array_keys($routes));

        foreach ($routes as $module => $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('Moduł dezaktywowany')
                ->assertSee(SiteSetting::MODULES[$module])
                ->assertSee('w zakładce Moduły');
        }

        $this->get(route('admin.strefy-bannerow.index'))->assertSee('Moduł dezaktywowany');
        $this->get(route('admin.zbiorki-teksty.edit'))->assertSee('Moduł dezaktywowany');
    }

    public function test_wylaczone_zbiorki_i_portale_zwracaja_404_na_stronie(): void
    {
        $this->disable(['campaigns', 'portals']);

        $this->get('/kampanie')->assertNotFound();
        $this->get('/kampanie/dowolna')->assertNotFound();
        $this->get('/portal/dowolny')->assertNotFound();
    }

    public function test_wylaczone_bannery_nie_wyswietlaja_strefy(): void
    {
        $this->disable(['banners']);

        $this->assertTrue((new BannerZone('sidebar'))->banners->isEmpty());
    }

    public function test_formularz_grupy_nie_pokazuje_modulow_tylko_dla_administratora(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.grupy.create'))->assertOk()->getContent();

        $this->assertStringContainsString(SiteSetting::MODULES['projects'], $html);
        foreach (SiteSetting::MODULES_WITHOUT_GROUP_ACCESS as $module) {
            $this->assertStringNotContainsString('value="'.$module.'"', $html);
        }
    }
}
