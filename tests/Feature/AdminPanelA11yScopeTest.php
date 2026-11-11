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

    public function test_metro_pokazuje_edytowana_witryne_ikona_za_logo_z_podpowiedzia_i_okienkiem(): void
    {
        $main = SiteSetting::current();
        $sub = SiteSetting::query()->forceCreate(['site_name' => 'test', 'slug' => 'osrodek', 'parent_site_id' => $main->id]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);

        $html = $this->actingAs($admin)->withSession(['admin_active_site_id' => $sub->id, 'admin_site_chosen' => true])
            ->get(route('admin.podstrony.index'))->assertOk()->getContent();

        // Ikona witryny stoi za logo, przed grupami menu; podpowiedź i okienko z nazwą i rodzajem witryny.
        $this->assertMatchesRegularExpression('~class="mt-brand".*?id="mt-btn-witryna" class="mt-tool mt-site is-sub".*?aria-describedby="mt-tip-witryna".*?class="mt-groups"~s', $html);
        $this->assertMatchesRegularExpression('~<div id="mt-tip-witryna" class="mt-tip" role="tooltip"[^>]*>\s*Edytujesz: <strong>test</strong> \(witryna w sieci\)~', $html);
        $this->assertStringContainsString('@keydown.escape="tip = false"', $html);
        $this->assertMatchesRegularExpression('~id="mt-panel-witryna".*?<strong>test</strong><span class="mt-site-kind">witryna w sieci</span>.*?admin/witryny~s', $html);
        $this->assertSame(1, substr_count($html, 'id="mt-btn-witryna"'));
        // Pasek nad treścią zostaje w kodzie (telefon), a na szerokim ekranie Metro go chowa.
        $this->assertStringContainsString('class="admin-site-bar ', $html);
        $this->assertStringContainsString('body.nav-top .admin-site-bar { display: none; }', $html);
    }

    public function test_metro_przy_jednej_witrynie_ikona_bez_listy_witryn(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_scheme' => 'metro']);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="mt-btn-witryna" class="mt-tool mt-site"', $html);
        $this->assertStringContainsString('<strong>'.e(SiteSetting::current()->site_name).'</strong> (witryna główna)', $html);
        $this->assertStringNotContainsString('class="mt-site-switch"', $html);
    }
}
