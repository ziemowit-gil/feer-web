<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuGroupsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_menu_ma_najwyzej_szesc_grup_glownych_w_stalej_kolejnosci(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $groups = AdminMenu::for(auth()->user());

        $this->assertLessThanOrEqual(6, count($groups));
        $this->assertSame(['start', 'pages', 'content', 'comms', 'users', 'system'], array_column($groups, 'key'));
        foreach ($groups as $group) {
            $this->assertNotEmpty($group['icon']);
            $this->assertNotEmpty($group['blocks']);
        }
    }

    public function test_redaktor_nie_widzi_grup_administracyjnych(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]));

        $keys = array_column(AdminMenu::for(auth()->user()), 'key');

        $this->assertContains('start', $keys);
        $this->assertContains('content', $keys);
        $this->assertNotContains('system', $keys);
        $this->assertNotContains('users', $keys);
        $this->assertNotContains('comms', $keys);
    }

    public function test_aktywna_pozycja_oznacza_swoja_grupe(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="tm-group relative is-active"/', $html);
        $this->assertSame(6, substr_count($html, 'class="tm-head nav-link relative"'));
        $this->assertStringContainsString('aria-label="Menu panelu"', $html);
    }

    public function test_przelaczniki_ustawien_trafiaja_do_grupy_system(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $groups = collect(AdminMenu::for($admin))->keyBy('key');

        $systemItems = $groups['system']['blocks'][0]['items'];
        $this->assertContains('settings', array_column($systemItems, 'key'));
        $this->assertSame(['Konfiguracja', 'Narzędzia'], array_column($groups['system']['blocks'], 'heading'));
    }

    public function test_ustawienia_strony_sa_podzielone_na_kategorie_i_zawieraja_wszystkie_zakladki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $groups = collect(AdminMenu::for($admin))->keyBy('key');
        $settings = collect($groups['system']['blocks'][0]['items'])->firstWhere('key', 'settings');

        $tabChildren = collect($settings['children'])->filter(fn ($c) => $c['group'] !== 'Zaawansowane');
        $this->assertCount(count(SiteSetting::SETTINGS_TABS), $tabChildren, 'Każda zakładka ustawień trafia do menu dokładnie raz.');
        $this->assertSame(
            ['Wygląd', 'Treści i dane', 'Funkcje i integracje', 'SEO i dostępność', 'Zaawansowane'],
            collect($settings['children'])->pluck('group')->unique()->values()->all()
        );

        // Podział obejmuje wszystkie klucze zakładek bez powtórzeń.
        $keys = collect(SiteSetting::SETTINGS_TAB_GROUPS)->flatten();
        $this->assertSame($keys->count(), $keys->unique()->count());
        $this->assertEqualsCanonicalizing(array_keys(SiteSetting::SETTINGS_TABS), $keys->all());
    }

    public function test_strona_ustawien_ma_dodatkowe_boczne_menu_z_pelna_lista_sekcji(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'mail']))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Sekcje ustawień"', $html);
        foreach (SiteSetting::SETTINGS_TABS as $key => $label) {
            $this->assertStringContainsString(route('admin.ustawienia.edit', ['tab' => $key]), $html, "Brak linku do sekcji {$label}.");
        }
        $this->assertStringContainsString(route('admin.ustawienia.env'), $html);
    }
}
