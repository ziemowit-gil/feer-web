<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ustawienia → Moduły: tabele modułów i typów treści z zaznaczaniem wielu wierszy i akcjami masowymi
 * (SiteModulesController) oraz moduły Kampanie, Newsletter, Portale i Mapa pomocy przełączane dla witryny.
 */
class SiteModulesBulkTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** @return array<int, string> */
    private function menuKeys(User $user): array
    {
        $this->reset();

        return collect(AdminMenu::for($user))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
    }

    private function settingsUrl(string $anchor): string
    {
        return route('admin.ustawienia.edit', ['tab' => 'modules']).'#'.$anchor;
    }

    public function test_tabele_maja_podpis_pola_wyboru_z_nazwa_wiersza_i_formularze_poza_formularzem_ustawien(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit', ['tab' => 'modules']))->assertOk()->getContent();

        // Podpisy tabel i zaznaczanie wszystkich (JS) + link bez JS.
        $this->assertMatchesRegularExpression('/<caption id="modules-table-caption"[^>]*>\s*Moduły witryny/', $html);
        $this->assertStringContainsString('<caption class="sr-only">Typy treści i układy', $html);
        $this->assertStringContainsString('Zaznacz wszystkie widoczne moduły', $html);
        $this->assertStringContainsString('Zaznacz wszystkie widoczne typy treści i układy', $html);
        $this->assertStringContainsString('zaznacz=moduly', $html);

        // Wiersz: pole wyboru z nazwą modułu, przycisk z nazwą modułu (cel ≥ 44 px: min-h-11 / h-11 w-11).
        $this->assertStringContainsString('Zaznacz moduł: '.e(SiteSetting::MODULES['newsletter']), $html);
        $this->assertStringContainsString('name="row_action" value="disable|newsletter"', $html);
        $this->assertStringContainsString('&nbsp;moduł '.e(SiteSetting::MODULES['newsletter']), $html);
        $this->assertStringContainsString('flex h-11 w-11 cursor-pointer', $html);
        $this->assertStringContainsString('Włącz zaznaczone', $html);
        $this->assertStringContainsString('Wyłącz zaznaczone', $html);
        $this->assertStringContainsString('Udostępnij zaznaczone', $html);
        $this->assertStringContainsString('Ukryj zaznaczone', $html);
        $this->assertStringContainsString('Zaznacz: ', $html);

        // Formularze akcji masowych są poza formularzem ustawień (HTML nie pozwala ich zagnieżdżać).
        $settingsFormEnd = strpos($html, '</form>', strpos($html, 'action="'.route('admin.ustawienia.update').'"'));
        foreach (['modules-bulk-form' => 'admin.ustawienia.moduly.masowo', 'options-bulk-form' => 'admin.ustawienia.typy-tresci.masowo'] as $id => $route) {
            $pos = strpos($html, '<form id="'.$id.'" method="POST" action="'.route($route).'"');
            $this->assertNotFalse($pos, "Brak formularza {$id}.");
            $this->assertGreaterThan($settingsFormEnd, $pos);
        }

        // Główny formularz nie wysyła już stanu modułów ani typów treści — „Zapisz” ich nie zmienia.
        $this->assertStringNotContainsString('name="enabled_modules[]"', $html);
        $this->assertStringNotContainsString('name="blocked_options_form"', $html);
    }

    public function test_zaznacz_wszystkie_bez_js_zaznacza_wszystkie_wiersze(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.ustawienia.edit', ['tab' => 'modules', 'zaznacz' => 'moduly']))
            ->assertOk()->getContent();

        foreach (array_keys(SiteSetting::MODULES) as $key) {
            $this->assertMatchesRegularExpression('/name="modules\[\]" value="'.preg_quote($key, '/').'" x-model="sel"\s+checked/', $html);
        }
    }

    public function test_masowe_wylaczenie_i_wlaczenie_czterech_modulow(): void
    {
        $admin = $this->admin();
        $modules = ['campaigns', 'newsletter', 'portals', 'help_map'];

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'disable', 'modules' => $modules])
            ->assertRedirect($this->settingsUrl('tabela-modulow'))
            ->assertSessionHas('modules_table_status', fn ($m) => str_starts_with($m, 'Wyłączono 4 moduły: ') && str_contains($m, 'Newsletter'));

        $this->reset();
        $this->assertEqualsCanonicalizing($modules, SiteSetting::current()->disabled_modules);

        // Menu panelu: pozycje znikają.
        $keys = $this->menuKeys($admin);
        foreach (['campaigns', 'newsletter', 'portals', 'help-map'] as $item) {
            $this->assertNotContains($item, $keys);
        }

        // Komunikat po akcji na stronie (role="status").
        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'modules']))
            ->assertSee('id="modules-table-status" role="status"', false);

        // Strona publiczna: 404.
        $this->get('/kampanie')->assertNotFound();
        $this->get('/portal/dowolny')->assertNotFound();
        $this->get('/mapa-pomocy')->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'enable', 'modules' => $modules])
            ->assertSessionHas('modules_table_status', fn ($m) => str_starts_with($m, 'Włączono 4 moduły: '));

        $this->reset();
        $this->assertSame([], SiteSetting::current()->disabled_modules);
        $keys = $this->menuKeys($admin);
        foreach (['campaigns', 'portals', 'help-map'] as $item) {
            $this->assertContains($item, $keys);
        }
        $this->get('/kampanie')->assertOk();
    }

    public function test_przycisk_w_wierszu_zmienia_tylko_jeden_modul(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.ustawienia.moduly.masowo'), [
                'row_action' => 'disable|campaigns',
                'modules' => ['gallery', 'polls'], // zaznaczenie nie liczy się przy akcji w wierszu
            ])
            ->assertSessionHas('modules_table_status', 'Wyłączono 1 moduł: '.SiteSetting::MODULES['campaigns'].'.');

        $this->reset();
        $this->assertSame(['campaigns'], SiteSetting::current()->disabled_modules);

        $this->actingAs($this->admin())
            ->post(route('admin.ustawienia.moduly.masowo'), ['row_action' => 'enable|campaigns'])
            ->assertSessionHas('modules_table_status');

        $this->reset();
        $this->assertSame([], SiteSetting::current()->disabled_modules);
    }

    public function test_bez_zaznaczenia_albo_z_nieznanym_modulem_jest_komunikat_bledu(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'disable'])
            ->assertRedirect($this->settingsUrl('tabela-modulow'))
            ->assertSessionHasErrors(['modules'], null, 'modulesTable');

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'disable', 'modules' => ['nie-ma-takiego']])
            ->assertSessionHasErrors(['modules'], null, 'modulesTable');

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'usun', 'modules' => ['gallery']])
            ->assertSessionHasErrors(['modules'], null, 'modulesTable');

        $this->reset();
        $this->assertSame([], SiteSetting::current()->disabled_modules ?? []);

        // Błąd widać przy tabeli jako alert.
        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'enable'])
            ->assertSee('id="modules-table-error" role="alert"', false)
            ->assertSee('Zaznacz co najmniej jeden moduł, a potem wybierz akcję.');
    }

    public function test_redaktor_nie_moze_zmieniac_modulow(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $response = $this->actingAs($editor)->post(route('admin.ustawienia.moduly.masowo'), ['action' => 'disable', 'modules' => ['gallery']]);
        $this->assertContains($response->status(), [302, 403]);

        $this->reset();
        $this->assertSame([], SiteSetting::current()->disabled_modules ?? []);
    }

    public function test_newsletter_wylaczony_dla_witryny_znika_z_menu_i_zapisow_a_wypis_dziala(): void
    {
        if (! app(ModuleManager::class)->isActive('newsletter')) {
            $this->markTestSkipped('Rozszerzenie Newsletter nieaktywne w tym środowisku.');
        }

        $admin = $this->admin();
        $this->assertContains('newsletter', $this->menuKeys($admin));
        $this->get('/newsletter')->assertOk();

        SiteSetting::current()->update(['disabled_modules' => ['newsletter']]);
        $this->reset();

        $this->assertFalse(SiteSetting::current()->isModuleEnabled('newsletter'));
        $this->assertNotContains('newsletter', $this->menuKeys($admin));
        $this->get('/newsletter')->assertNotFound();
        $this->post('/newsletter/zapis', ['email' => 'a@example.pl'])->assertNotFound();
        // Panel: ekran modułu mówi, gdzie go włączyć.
        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))
            ->assertSee('Moduł dezaktywowany')
            ->assertSee('w zakładce Moduły');
        // Linki z wysłanych maili (potwierdzenie, wypis, preferencje) działają dalej — bez blokady modułu.
        foreach (['newsletter.confirm', 'newsletter.unsubscribe', 'newsletter.preferences'] as $name) {
            $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertNotContains('module:newsletter', $route->gatherMiddleware());
        }
        $this->assertContains('module:newsletter', \Illuminate\Support\Facades\Route::getRoutes()->getByName('newsletter.subscribe')->gatherMiddleware());
    }

    public function test_wylaczone_portale_kieruja_linki_kategorii_na_strone_kategorii(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_slug')) {
            $this->markTestSkipped('Brak kolumny portal_slug.');
        }

        $category = Category::create(['name' => 'Edukacja', 'slug' => 'edukacja', 'portal_slug' => 'edukacja']);
        $this->assertNotNull($category->portalUrl());

        SiteSetting::current()->update(['disabled_modules' => ['portals']]);
        $this->reset();

        $this->assertNull($category->fresh()->portalUrl());
        $this->assertSame(route('categories.show', $category), $category->fresh()->publicUrl());
    }

    public function test_masowe_ukrycie_i_udostepnienie_typow_tresci(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.ustawienia.typy-tresci.masowo'), [
                'action' => 'hide',
                'options' => ['opt:news_layouts:side', 'opt:news_layouts:portal', 'opt:page_types:standard', 'opt:nie_ma:x'],
            ])
            ->assertRedirect($this->settingsUrl('tabela-typow-tresci'))
            ->assertSessionHas('options_table_status', fn ($m) => str_starts_with($m, 'Ukryto 2 opcje: ') && str_contains($m, 'Zawsze dostępne, pominięto:'));

        $this->reset();
        $blocked = SiteSetting::current()->blocked_options;
        $this->assertEqualsCanonicalizing(['side', 'portal'], $blocked['news_layouts']);
        $this->assertNotContains('standard', $blocked['page_types'] ?? []);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'modules']))
            ->assertSee('id="options-table-status" role="status"', false)
            ->assertSee('name="row_action" value="show|news_layouts|side"', false);

        // Przycisk w wierszu: udostępnia jedną opcję.
        $this->actingAs($admin)
            ->post(route('admin.ustawienia.typy-tresci.masowo'), ['row_action' => 'show|news_layouts|side'])
            ->assertSessionHas('options_table_status', fn ($m) => str_starts_with($m, 'Udostępniono 1 opcję: '));

        $this->reset();
        $this->assertSame(['portal'], SiteSetting::current()->blocked_options['news_layouts']);
    }

    public function test_nie_mozna_ukryc_calej_grupy_typow_tresci(): void
    {
        $all = array_map(fn ($v) => 'opt:header_layouts:'.$v, array_keys(SiteSetting::blockableOptionGroups()['header_layouts']));

        $this->actingAs($this->admin())
            ->post(route('admin.ustawienia.typy-tresci.masowo'), ['action' => 'hide', 'options' => $all])
            ->assertSessionHasErrors(['options'], null, 'optionsTable');

        $this->reset();
        $this->assertEmpty(SiteSetting::current()->blocked_options['header_layouts'] ?? []);

        $this->actingAs($this->admin())
            ->post(route('admin.ustawienia.typy-tresci.masowo'), ['action' => 'hide'])
            ->assertSessionHasErrors(['options'], null, 'optionsTable');
    }
}
