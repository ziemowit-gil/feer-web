<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavItemEditorTest extends TestCase
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
        return NavItem::create($extra + ['label' => $label, 'url' => '/'.str($label)->slug(), 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => NavItem::where('location', $extra['location'] ?? 'main')->whereNull('parent_id')->count()]);
    }

    public function test_edytor_pokazuje_drzewo_i_szczegoly_wybranej_pozycji(): void
    {
        $a = $this->item('Oferta', ['type' => 'dropdown', 'is_mega' => true]);
        $b = $this->item('Szkolenia', ['parent_id' => $a->id, 'order' => 0]);
        $this->item('Kontakt');

        $html = $this->actingAs($this->admin())->get(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $a->id]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Struktura menu', $html);
        $this->assertStringContainsString('role="tree"', $html);
        $this->assertStringContainsString('role="group"', $html);
        $this->assertStringContainsString('id="nav-pane-heading"', $html);
        $this->assertStringContainsString('Dodaj podpozycję', $html);
        $this->assertStringContainsString('Podpozycje', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
        $this->assertStringContainsString('Układ w mega menu', $html);
    }

    public function test_bez_wyboru_otwiera_pierwsza_pozycje_a_stopka_nie_ma_podpozycji(): void
    {
        $first = $this->item('Pierwsza', ['location' => 'footer']);
        $this->item('Druga', ['location' => 'footer']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'footer']))
            ->assertOk()->assertSee('Stopka')->assertSee('Pierwsza')->assertDontSee('Ta pozycja nie ma jeszcze podpozycji');
        $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'footer', 'pozycja' => 99999]))->assertOk();
    }

    public function test_ukryj_pokaz_przelacza_widocznosc_i_zachowuje_wybor(): void
    {
        $item = $this->item('Kontakt');

        $this->actingAs($this->admin())->patch(route('admin.pozycje-menu.aktywna', $item))
            ->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $item->id]))
            ->assertSessionHas('status');
        $this->assertFalse($item->fresh()->is_active);

        $this->actingAs($this->admin())->patch(route('admin.pozycje-menu.aktywna', $item));
        $this->assertTrue($item->fresh()->is_active);
    }

    public function test_przesuniecie_i_edycja_wracaja_do_wybranej_pozycji(): void
    {
        $a = $this->item('A');
        $b = $this->item('B');
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.pozycje-menu.przenies', $b), ['action' => 'up'])
            ->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $b->id]));
        $this->assertSame(['B', 'A'], NavItem::where('location', 'main')->orderBy('order')->pluck('label')->all());

        $this->actingAs($admin)->put(route('admin.pozycje-menu.update', $a), ['label' => 'A2', 'url' => '/a', 'type' => 'link', 'location' => 'main', 'is_active' => '1'])
            ->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $a->id]));
    }

    public function test_dodanie_przechodzi_na_nowa_pozycje_a_usuniecie_podpozycji_na_rodzica(): void
    {
        $admin = $this->admin();
        $parent = $this->item('Dział', ['type' => 'dropdown']);

        $response = $this->actingAs($admin)->post(route('admin.pozycje-menu.store'), ['label' => 'Nowa', 'url' => '/nowa', 'type' => 'link', 'location' => 'main', 'parent_id' => $parent->id, 'is_active' => '1']);
        $created = NavItem::where('label', 'Nowa')->first();
        $response->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $created->id]));

        $this->actingAs($admin)->delete(route('admin.pozycje-menu.destroy', $created))
            ->assertRedirect(route('admin.pozycje-menu.index', ['location' => 'main', 'pozycja' => $parent->id]));
        $this->assertNull(NavItem::find($created->id));
    }
}
