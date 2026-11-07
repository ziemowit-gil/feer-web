<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * „Strony i nawigacja” na jednym ekranie: drzewo stron pokazuje, czy i gdzie strona jest w menu, a panel strony
 * pozwala dodać ją do menu, zmienić napis i widoczność pozycji albo usunąć ją z menu (bez wchodzenia w Menu nawigacji).
 */
class PageMenuNavigationTest extends TestCase
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

    private function page(string $title, string $slug, array $extra = []): Page
    {
        return Page::create($extra + ['title' => $title, 'slug' => $slug, 'type' => 'standard', 'is_published' => true]);
    }

    public function test_drzewo_i_panel_pokazuja_gdzie_strona_jest_w_menu(): void
    {
        $about = $this->page('O nas', 'o-nas');
        $other = $this->page('Regulamin', 'regulamin');
        $parent = NavItem::create(['label' => 'Fundacja', 'url' => '#', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'O Nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'parent_id' => $parent->id, 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'O nas', 'url' => url('/o-nas'), 'type' => 'link', 'location' => 'footer', 'is_active' => true, 'order' => 1]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $about->id]))->assertOk()->getContent();

        $this->assertStringContainsString('(w menu: Menu główne › Fundacja, Stopka)', $html);
        $this->assertStringContainsString('id="menu-nawigacji"', $html);
        $this->assertStringContainsString('„O Nas”', $html);
        $this->assertMatchesRegularExpression('~data-page-id="'.$about->id.'"[^>]*data-menu="1"~', $html);
        $this->assertMatchesRegularExpression('~data-page-id="'.$other->id.'"[^>]*data-menu="0"~', $html);
    }

    public function test_dodanie_strony_do_menu_glownego_pod_pozycja(): void
    {
        $page = $this->page('Sprawozdania', 'sprawozdania');
        $parent = NavItem::create(['label' => 'Fundacja', 'url' => '#', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'parent_id' => $parent->id, 'is_active' => true, 'order' => 5]);

        $this->actingAs($this->admin())->post(route('admin.podstrony.menu.store', $page), ['location' => 'main', 'parent_id' => $parent->id])
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $page->id]).'#menu-nawigacji');

        $item = NavItem::where('url', '/sprawozdania')->first();
        $this->assertNotNull($item);
        $this->assertSame(['Sprawozdania', 'main', $parent->id, 6, 'link'], [$item->label, $item->location, $item->parent_id, $item->order, $item->type]);
    }

    public function test_stopka_bez_pozycji_nadrzednej_i_wlasny_napis(): void
    {
        $page = $this->page('Polityka prywatności', 'polityka-prywatnosci');
        $parent = NavItem::create(['label' => 'Fundacja', 'url' => '#', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $this->actingAs($this->admin())->post(route('admin.podstrony.menu.store', $page), ['location' => 'footer', 'parent_id' => $parent->id, 'label' => 'Prywatność']);

        $item = NavItem::where('url', '/polityka-prywatnosci')->first();
        $this->assertSame(['Prywatność', 'footer', null], [$item->label, $item->location, $item->parent_id]);
    }

    public function test_zmiana_napisu_widocznosci_i_usuniecie_pozycji(): void
    {
        $page = $this->page('O nas', 'o-nas');
        $item = NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.podstrony.menu.update', [$page, $item]), ['label' => 'Kim jesteśmy', 'is_active' => '0'])->assertRedirect();
        $this->assertSame(['Kim jesteśmy', false], [$item->fresh()->label, $item->fresh()->is_active]);

        $this->actingAs($admin)->delete(route('admin.podstrony.menu.destroy', [$page, $item]))->assertRedirect();
        $this->assertNull($item->fresh());
        $this->assertNotNull($page->fresh(), 'strona zostaje');
    }

    public function test_nie_usuwa_pozycji_z_podpozycjami_ani_cudzej_pozycji(): void
    {
        $page = $this->page('O nas', 'o-nas');
        $other = $this->page('Kontakt', 'kontakt-2');
        $item = NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Zespół', 'url' => '/zespol', 'type' => 'link', 'location' => 'main', 'parent_id' => $item->id, 'is_active' => true, 'order' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.podstrony.menu.destroy', [$page, $item]))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($item->fresh());

        // Pozycja nie prowadzi do strony „Kontakt” — nie da się jej zmienić z panelu tej strony.
        $this->actingAs($admin)->patch(route('admin.podstrony.menu.update', [$other, $item]), ['label' => 'X'])->assertNotFound();
    }

    public function test_dawne_pokaz_w_menu_jest_widoczne_i_da_sie_je_wylaczyc(): void
    {
        $page = $this->page('Aktualności', 'aktualnosci-strona', ['show_in_menu' => true]);
        $admin = $this->admin();

        // Bez pozycji „Menu podstron” ustawienie strony nic nie robi — strona nie jest w menu.
        $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->assertDontSee('Dopisywana automatycznie');

        NavItem::create(['label' => 'Strony', 'url' => '#', 'type' => 'pages', 'location' => 'main', 'is_active' => true, 'order' => 9]);

        $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()
            ->assertSee('Dopisywana automatycznie')->assertSee('Nie dopisuj automatycznie');

        $this->actingAs($admin)->patch(route('admin.podstrony.menu.auto', $page))->assertRedirect();
        $this->assertFalse($page->fresh()->show_in_menu);
    }

    public function test_redaktor_widzi_menu_bez_przyciskow_i_nie_moze_zmieniac(): void
    {
        $page = $this->page('O nas', 'o-nas');
        NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $html = $this->actingAs($editor)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();
        $this->assertStringContainsString('W menu nawigacji', $html);
        $this->assertStringNotContainsString('Dodaj do menu', $html);

        $this->actingAs($editor)->post(route('admin.podstrony.menu.store', $page), ['location' => 'main'])->assertForbidden();
    }

    public function test_menu_nawigacji_odsyla_do_stron(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index'))->assertOk()
            ->assertSee('data-nav-pages-hint', false)->assertSee('„W menu nawigacji”');
    }
}
