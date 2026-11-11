<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ustawienia dostępności panelu są osobne od ustawień strony (rozstrzał liter, rozmiar tekstu, kontrast włączone na
 * stronie nie zmieniają panelu), a komunikat po akcji w panelu da się zamknąć i w stylu Metro nie jest pełnym pasem koloru.
 */
class AdminPanelA11yScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_panel_ma_wlasny_zakres_ustawien_dostepnosci_a_strona_nie(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('<html lang="pl" data-a11y-scope="admin">', false);
        $this->get('/kontakt')->assertOk()->assertDontSee('data-a11y-scope', false);
    }

    public function test_skrypt_zapisuje_ustawienia_dostepnosci_przez_zakres(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("document.documentElement.dataset.a11yScope === 'admin' ? 'admin:' : ''", $js);
        // Żadnego bezpośredniego odczytu/zapisu kluczy a11y-* z pominięciem zakresu.
        $this->assertDoesNotMatchRegularExpression("~localStorage\\.(get|set|remove)Item\\('a11y-~", $js);
        $this->assertStringContainsString("a11yStore.set('a11y-ls'", $js);
    }

    public function test_komunikat_po_akcji_mozna_zamknac(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->withSession(['status' => 'Pracujesz teraz na witrynie „test”.'])->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Pracujesz teraz na witrynie „test”.')
            ->assertSee('x-data="{ shown: true }" x-show="shown"', false)
            ->assertSee('aria-label="Zamknij komunikat"', false);
    }

    public function test_w_metro_komunikat_jest_jasny_z_kreska_akcentu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);

        $this->actingAs($admin)->withSession(['status' => 'Zapisano.'])->get(route('admin.dashboard'))->assertOk()
            ->assertSee('[role="status"].bg-brand-light { background: var(--m-surface); color: var(--m-ink); box-shadow: inset 6px 0 0 var(--m-accent);', false)
            ->assertDontSee('[role="status"].bg-brand-light { background: var(--m-accent)', false);
    }

    public function test_metro_pokazuje_aktualna_witryne_zaraz_za_logo(): void
    {
        $main = SiteSetting::current();
        $sub = SiteSetting::query()->forceCreate(['site_name' => 'test', 'slug' => 'osrodek', 'parent_site_id' => $main->id]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);

        $html = $this->actingAs($admin)->withSession(['admin_active_site_id' => $sub->id, 'admin_site_chosen' => true])
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        // Przełącznik witryny stoi za logo, przed grupami menu, i pokazuje nazwę aktualnej witryny.
        $this->assertMatchesRegularExpression('~class="mt-brand".*?id="mt-btn-witryna".*?<span class="mt-site-kicker">Witryna</span><span class="mt-site-name">test</span>.*?class="mt-groups"~s', $html);
        $this->assertStringContainsString('class="mt-drop mt-drop--left"', $html);
        $this->assertSame(1, substr_count($html, 'id="mt-btn-witryna"'));
    }

    public function test_metro_przy_jednej_witrynie_pokazuje_sama_nazwe(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('class="mt-site mt-site--static"', false)
            ->assertSee('<span class="mt-site-name">'.e(SiteSetting::current()->site_name).'</span>', false)
            ->assertDontSee('id="mt-btn-witryna"', false);
    }
}
