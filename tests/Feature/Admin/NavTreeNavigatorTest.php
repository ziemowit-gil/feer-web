<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MenuLinkCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu nawigacji przebudowane jak Strony Nawigator (TYPO3): zwarte drzewo z menu kontekstowym, schowkiem
 * i klawiaturą, stan linków (szkic, wyłączona strona, 404, moduł wyłączony), przenoszenie w dowolne miejsce
 * (także do stopki), kopiowanie pozycji i etykieta z tytułu strony.
 */
class NavTreeNavigatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        NavItem::query()->delete();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function item(string $label, array $extra = []): NavItem
    {
        $location = $extra['location'] ?? 'main';
        $parent = $extra['parent_id'] ?? null;

        return NavItem::create($extra + ['label' => $label, 'url' => '/'.str($label)->slug(), 'type' => 'link', 'location' => $location, 'is_active' => true,
            'order' => NavItem::where('location', $location)->where('parent_id', $parent)->count()]);
    }

    private function labels(string $location = 'main', ?int $parentId = null): array
    {
        return NavItem::where('location', $location)->where('parent_id', $parentId)->orderBy('order')->pluck('label')->all();
    }

    public function test_drzewo_ma_wyglad_nawigatora_menu_kontekstowe_legende_i_filtr(): void
    {
        $a = $this->item('Fundacja');
        $this->item('O nas', ['parent_id' => $a->id]);
        $this->item('Kontakt');

        $html = $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $a->id]))
            ->assertOk()->getContent();

        // Zwarte drzewo: gałąź z przyciskiem rozwijania, pamięć rozwinięcia, wybrana pozycja z tłem marki.
        $this->assertStringContainsString('window.navTreeState', $html);
        $this->assertStringContainsString('tree-link flex min-h-6', $html);
        $this->assertStringContainsString('is-selected bg-brand-light', $html);
        $this->assertStringContainsString('aria-controls="nav-branch-'.$a->id.'"', $html);
        $this->assertStringContainsString('data-can-hold="1"', $html);
        // Menu kontekstowe, schowek, legenda, filtr; bez starej biblioteki Sortable i bez ramki wokół każdego wiersza.
        $this->assertStringContainsString('id="nav-ctx" role="menu"', $html);
        $this->assertStringContainsString('data-act="cut"', $html);
        $this->assertStringContainsString('data-act="paste-into"', $html);
        $this->assertStringContainsString('id="nav-clip"', $html);
        $this->assertStringContainsString('Wklej na końcu tego menu', $html);
        $this->assertStringContainsString('id="nav-legend"', $html);
        $this->assertStringContainsString('id="nav-filter"', $html);
        $this->assertStringNotContainsString('Sortable', $html);
        $this->assertStringNotContainsString('rounded-lg border border-brand-dark bg-brand-light', $html);
        // Karta pozycji: lekkie akcje, spokojne etykiety, duplikowanie, zwarta tabela podpozycji.
        $this->assertStringContainsString('Duplikuj', $html);
        $this->assertStringContainsString('Ostatnia zmiana', $html);
        $this->assertStringNotContainsString('uppercase tracking-wide text-muted">Adres', $html);
        $this->assertStringContainsString('id="nav-children-h"', $html);
        $this->assertStringContainsString('<caption class="sr-only">Podpozycje „Fundacja”</caption>', $html);
        // „Dodaj” w rzędzie zakładek, eksport i podgląd na pasku narzędzi.
        $this->assertStringContainsString('content-head-primary', $html);
        $this->assertStringContainsString('id="podglad-menu"', $html);
    }

    public function test_stan_linkow_pokazuje_szkic_brak_strony_i_wylaczony_modul(): void
    {
        Page::create(['title' => 'Projekty', 'slug' => 'projekty-szkic', 'type' => 'standard', 'is_published' => false]);
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true]);
        $draft = $this->item('Projekty', ['url' => '/projekty-szkic']);
        $missing = $this->item('Stary adres', ['url' => '/nie-ma-takiej-strony-xyz']);
        $ok = $this->item('O nas', ['url' => '/o-nas']);
        $external = $this->item('Partner', ['url' => 'https://example.org']);
        $hidden = $this->item('Ukryta', ['url' => '/nie-ma-takiej-strony-xyz', 'is_active' => false]);
        $site = SiteSetting::current();
        $site->update(['disabled_modules' => array_values(array_unique([...((array) $site->disabled_modules), 'faq']))]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $faq = $this->item('FAQ', ['type' => 'faq', 'url' => '/faq', 'module' => 'faq']);

        $check = MenuLinkCheck::forItems(NavItem::all());
        $this->assertSame('page_draft', $check[$draft->id]['state']);
        $this->assertTrue($check[$draft->id]['issue']);
        $this->assertSame('page_missing', $check[$missing->id]['state']);
        $this->assertSame('ok', $check[$ok->id]['state']);
        $this->assertSame('page', $check[$ok->id]['kind']);
        $this->assertSame('external', $check[$external->id]['kind']);
        $this->assertSame('hidden', $check[$hidden->id]['state']);
        $this->assertFalse($check[$hidden->id]['issue'], 'ukryta pozycja nie jest problemem na stronie');
        if (! SiteSetting::current()->isModuleEnabled('faq')) {
            $this->assertSame('module_off', $check[$faq->id]['state']);
        }

        $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $draft->id]))->assertOk()
            ->assertSee('2 pozycje prowadzą do niedostępnej strony')
            ->assertSee('Pokaż tylko je')
            ->assertSee('to szkic — odwiedzający zobaczą błąd 404', false)
            ->assertSee('tree-ov fa-solid fa-pen text-amber-800', false)
            ->assertSee('tree-ov fa-solid fa-link-slash text-red-700', false)
            ->assertSee('fa-arrow-up-right-from-square', false);
    }

    public function test_umiesc_zmienia_kolejnosc_zagniezdza_i_odpowiada_jsonem(): void
    {
        $a = $this->item('A', ['type' => 'dropdown', 'url' => '#']);
        $b = $this->item('B');
        $c = $this->item('C');
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $c), ['position' => 0])
            ->assertOk()->assertJson(['ok' => true, 'location' => 'main']);
        $this->assertSame(['C', 'A', 'B'], $this->labels());

        // Do środka pozycji z paska (podpozycja), na koniec jej listy.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $b), ['parent_id' => $a->id, 'position' => 100000])->assertOk();
        $this->assertSame($a->id, $b->fresh()->parent_id);
        $this->assertSame(['C', 'A'], $this->labels());
        $this->assertSame(['B'], $this->labels('main', $a->id));

        // Z powrotem na pasek, tuż za rodzicem.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $b), ['parent_id' => null, 'position' => 2])->assertOk();
        $this->assertSame(['C', 'A', 'B'], $this->labels());
    }

    public function test_umiesc_pilnuje_jednego_poziomu_i_przyciskow(): void
    {
        $a = $this->item('A', ['type' => 'dropdown', 'url' => '#']);
        $kid = $this->item('Dziecko', ['parent_id' => $a->id]);
        $b = $this->item('B');
        $cta = $this->item('Wesprzyj', ['is_button' => true]);
        $admin = $this->admin();

        // Pozycja z podpozycjami nie zejdzie poziom niżej.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $a), ['parent_id' => $b->id, 'position' => 0])
            ->assertStatus(422)->assertJson(['ok' => false]);
        // Podpozycja nie może być rodzicem.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $b), ['parent_id' => $kid->id, 'position' => 0])->assertStatus(422);
        // Przycisk CTA nie zostanie podpozycją.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $cta), ['parent_id' => $b->id, 'position' => 0])->assertStatus(422);
        // Rozwijane menu nie trafi do stopki.
        $this->actingAs($admin)->postJson(route('admin.pozycje-menu.umiesc', $a), ['location' => 'footer', 'position' => 0])->assertStatus(422);

        $this->assertNull($a->fresh()->parent_id);
        $this->assertSame('main', $a->fresh()->location);
    }

    public function test_wytnij_wklej_przenosi_pozycje_do_stopki(): void
    {
        $this->item('Polityka prywatności', ['location' => 'footer']);
        $a = $this->item('Fundacja', ['type' => 'dropdown', 'url' => '#']);
        $child = $this->item('RODO', ['parent_id' => $a->id, 'is_column_heading' => true]);

        $this->actingAs($this->admin())->postJson(route('admin.pozycje-menu.umiesc', $child), ['location' => 'footer', 'parent_id' => null, 'position' => 100000])
            ->assertOk()->assertJson(['ok' => true, 'location' => 'footer']);

        $child->refresh();
        $this->assertSame('footer', $child->location);
        $this->assertNull($child->parent_id);
        $this->assertSame('link', $child->type);
        $this->assertFalse($child->is_column_heading);
        $this->assertSame(['Polityka prywatności', 'RODO'], $this->labels('footer'));
    }

    public function test_duplikuj_kopiuje_z_podpozycjami_jako_ukryta_tuz_za_oryginalem(): void
    {
        $a = $this->item('Oferta', ['type' => 'dropdown', 'url' => '#', 'is_mega' => true]);
        $this->item('Szkolenia', ['parent_id' => $a->id]);
        $this->item('Doradztwo', ['parent_id' => $a->id]);
        $this->item('Kontakt');

        $response = $this->actingAs($this->admin())->post(route('admin.pozycje-menu.kopiuj', $a), ['z_podpozycjami' => '1']);
        $copy = NavItem::where('label', 'Oferta')->where('id', '!=', $a->id)->first();

        $response->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $copy->id]))->assertSessionHas('status');
        $this->assertFalse($copy->is_active, 'kopia jest ukryta');
        $this->assertTrue($copy->is_mega);
        $this->assertSame(['Oferta', 'Oferta', 'Kontakt'], $this->labels());
        $this->assertSame(['Szkolenia', 'Doradztwo'], $this->labels('main', $copy->id));
        $this->assertSame(2, $a->allChildren()->count(), 'oryginał bez zmian');
    }

    public function test_kopia_do_stopki_jest_bez_podpozycji(): void
    {
        $a = $this->item('Fundacja');
        $this->item('O nas', ['parent_id' => $a->id]);

        $this->actingAs($this->admin())->post(route('admin.pozycje-menu.kopiuj', $a), ['location' => 'footer', 'position' => 0, 'z_podpozycjami' => '1'])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'nie ma podmenu'));

        $copy = NavItem::where('location', 'footer')->firstOrFail();
        $this->assertSame('Fundacja', $copy->label);
        $this->assertSame(0, $copy->allChildren()->count());
    }

    public function test_etykieta_z_tytulu_strony(): void
    {
        $page = Page::create(['title' => 'Fundacja FEER', 'slug' => 'fundacja', 'type' => 'standard', 'is_published' => true]);
        $item = $this->item('Fundacja', ['url' => '/fundacja']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $item->id]))->assertOk()
            ->assertSee('Użyj tytułu strony')
            ->assertSee(route('admin.podstrony.index', ['wybrana' => $page->id]), false);

        $this->actingAs($admin)->patch(route('admin.pozycje-menu.etykieta', $item))->assertRedirect()->assertSessionHas('status');
        $this->assertSame('Fundacja FEER', $item->fresh()->label);

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $item->id]))->assertOk()
            ->assertDontSee('Użyj tytułu strony');
    }

    public function test_historia_zmian_pozycji(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $item = $this->item('Kontakt');
        $item->update(['label' => 'Kontakt z nami']);

        $this->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $item->id]))->assertOk()
            ->assertSee('id="historia-pozycji"', false)
            ->assertSee($admin->name);
    }
}
