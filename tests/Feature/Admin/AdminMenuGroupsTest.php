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

    public function test_menu_ma_grupy_glowne_w_stalej_kolejnosci(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $groups = AdminMenu::for(auth()->user());

        $this->assertSame(['start', 'pages', 'content', 'activity', 'docs', 'comms', 'settings', 'system'], array_column($groups, 'key'));
        // Żadna pozycja nie powtarza się w dwóch miejscach menu.
        $keys = collect($groups)->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key');
        $this->assertSame($keys->count(), $keys->unique()->count());
        foreach ($groups as $group) {
            $this->assertNotEmpty($group['icon']);
            $this->assertNotEmpty($group['blocks']);
        }
    }

    public function test_redaktor_nie_widzi_grup_administracyjnych(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]));

        $groups = AdminMenu::for(auth()->user());
        $keys = array_column($groups, 'key');
        $items = collect($groups)->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();

        $this->assertContains('start', $keys);
        $this->assertContains('content', $keys);
        $this->assertNotContains('system', $keys);
        $this->assertNotContains('settings', $keys);
        // W „Komunikacji” redaktor może widzieć najwyżej newsletter — marketing i skrzynka są tylko dla administratora.
        foreach (['banners', 'campaigns', 'contact', 'meetings', 'barriers'] as $adminOnly) {
            $this->assertNotContains($adminOnly, $items);
        }
    }

    public function test_aktywna_pozycja_oznacza_swoja_grupe(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="tm-group relative is-active"/', $html);
        $this->assertStringContainsString('Witryna', $html);
        $this->assertSame(8, substr_count($html, 'class="tm-head nav-link relative"'));
        $this->assertStringContainsString('aria-label="Menu panelu"', $html);
    }

    public function test_system_ma_bloki_narzedzi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $groups = collect(AdminMenu::for($admin))->keyBy('key');

        $this->assertSame(['Użytkownicy i dostęp', 'Dane i bezpieczeństwo', 'Diagnostyka', 'Sieć witryn'], array_column($groups['system']['blocks'], 'heading'));
    }

    public function test_ustawienia_strony_sa_podzielone_na_kategorie_i_zawieraja_wszystkie_zakladki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $groups = collect(AdminMenu::for($admin))->keyBy('key');
        $settings = $groups['settings'];

        $tabItems = collect($settings['blocks'])->flatMap(fn ($b) => $b['items'])->filter(fn ($i) => str_contains($i['url'], 'tab='));
        $this->assertCount(count(SiteSetting::SETTINGS_TABS), $tabItems, 'Każda zakładka ustawień trafia do menu dokładnie raz.');
        $this->assertSame(
            ['Wygląd', 'Treści i dane', 'Funkcje i integracje', 'SEO i dostępność', 'Zaawansowane'],
            array_column($settings['blocks'], 'heading')
        );

        // Podział obejmuje wszystkie klucze zakładek bez powtórzeń.
        $keys = collect(SiteSetting::SETTINGS_TAB_GROUPS)->flatten();
        $this->assertSame($keys->count(), $keys->unique()->count());
        $this->assertEqualsCanonicalizing(array_keys(SiteSetting::SETTINGS_TABS), $keys->all());
    }

    public function test_sekcje_ustawien_sa_w_menu_panelu_bez_dodatkowego_menu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'mail']))->assertOk()->getContent();

        // Dodatkowe menu sekcji usunięte (nawigacja tylko w menu panelu); na stronie jest nagłówek sekcji.
        $this->assertStringNotContainsString('aria-label="Sekcje ustawień"', $html);
        $this->assertStringContainsString('Ustawienia · Funkcje i integracje', $html);
        foreach (SiteSetting::SETTINGS_TABS as $key => $label) {
            $this->assertStringContainsString(route('admin.ustawienia.edit', ['tab' => $key]), $html, "Brak linku do sekcji {$label}.");
        }
        $this->assertStringContainsString(route('admin.ustawienia.env'), $html);
    }
}
