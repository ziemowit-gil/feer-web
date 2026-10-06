<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTreeViewTest extends TestCase
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

    private function page(string $title, array $extra = []): Page
    {
        return Page::create($extra + ['title' => $title, 'slug' => str($title)->slug()->toString(), 'type' => 'standard', 'is_published' => true]);
    }

    public function test_domyslnie_pokazuje_dwupanelowe_drzewo(): void
    {
        $root = $this->page('Dział');
        $this->page('Podstrona', ['parent_id' => $root->id]);

        $this->actingAs($this->admin())->get(route('admin.podstrony.index'))
            ->assertOk()
            ->assertSee('Drzewo stron')
            ->assertSee('Struktura serwisu')
            ->assertSee('Wszystkie strony')
            ->assertSee('Podstrona');
    }

    public function test_wybrana_strona_pokazuje_szczegoly_podstrony_i_sciezke(): void
    {
        $root = $this->page('Dział');
        $mid = $this->page('Środek', ['parent_id' => $root->id]);
        $leaf = $this->page('Liść', ['parent_id' => $mid->id]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $mid->id]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Ścieżka strony', $html);
        $this->assertStringContainsString('Podstrony', $html);
        $this->assertStringContainsString('Liść', $html);
        $this->assertStringContainsString(route('admin.podstrony.edit', $leaf), $html);
        $this->assertStringContainsString('aria-current="true"', $html);
    }

    public function test_osoby_nie_sa_czescia_drzewa(): void
    {
        $about = $this->page('O nas', ['type' => 'about']);
        $this->page('Jan Kowalski', ['type' => 'about_person', 'parent_id' => $about->id]);

        $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $about->id]))
            ->assertOk()->assertDontSee('Jan Kowalski')->assertSee('1 osoba');
    }

    public function test_filtry_i_widok_listy_zachowuja_tabele(): void
    {
        $this->page('Alfa');

        $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['q' => 'Alfa']))
            ->assertOk()->assertSee('Zaznacz wszystkie strony')->assertDontSee('Struktura serwisu');
        $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['widok' => 'lista']))
            ->assertOk()->assertSee('Zaznacz wszystkie strony');
    }

    public function test_przenoszenie_zmienia_rodzica_i_blokuje_cykl(): void
    {
        $a = $this->page('A');
        $b = $this->page('B', ['parent_id' => $a->id]);
        $c = $this->page('C');
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.podstrony.przenies', $c), ['parent_id' => $b->id])
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $c->id]));
        $this->assertSame($b->id, $c->fresh()->parent_id);

        // A do własnego potomka (C leży teraz pod B pod A) — odmowa.
        $this->actingAs($admin)->patch(route('admin.podstrony.przenies', $a), ['parent_id' => $c->id])
            ->assertSessionHas('error');
        $this->assertNull($a->fresh()->parent_id);

        // Do samej siebie — odmowa.
        $this->actingAs($admin)->patch(route('admin.podstrony.przenies', $b), ['parent_id' => $b->id])
            ->assertSessionHas('error');

        // Na poziom główny.
        $this->actingAs($admin)->patch(route('admin.podstrony.przenies', $c), ['parent_id' => null]);
        $this->assertNull($c->fresh()->parent_id);
    }

    public function test_akcje_z_drzewa_zachowuja_wybrana_strone(): void
    {
        $p = $this->page('Dział');

        $this->actingAs($this->admin())->patch(route('admin.podstrony.widocznosc', $p), ['wybrana' => $p->id])
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $p->id]));
        $this->assertFalse($p->fresh()->is_published);
    }

    public function test_usuniecie_z_drzewa_wraca_do_strony_nadrzednej(): void
    {
        $root = $this->page('Dział');
        $child = $this->page('Podstrona', ['parent_id' => $root->id]);

        $this->actingAs($this->admin())->delete(route('admin.podstrony.destroy', $child), ['wybrana' => $child->id])
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $root->id]));
        $this->assertSoftDeleted($child);
    }

    public function test_lista_jest_osobna_zakladka_a_drzewo_domyslne(): void
    {
        $this->page('Alfa');
        $admin = $this->admin();

        $tree = $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->getContent();
        $list = $this->actingAs($admin)->get(route('admin.podstrony.index', ['widok' => 'lista']))->assertOk()->getContent();

        // Zakładka „Strony” aktywna na drzewie, „Lista stron” — na liście; zawsze obie widoczne.
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*<i[^>]*><\/i>Strony/u', $tree);
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*<i[^>]*><\/i>Lista stron/u', $list);
        $this->assertStringContainsString('Lista stron', $tree);
        $this->assertStringNotContainsString('Struktura serwisu', $list);
    }

    public function test_przeciaganie_zmienia_kolejnosc_rodzenstwa(): void
    {
        $a = $this->page('A'); $b = $this->page('B'); $c = $this->page('C');

        // C na początek.
        $this->actingAs($this->admin())->postJson(route('admin.podstrony.uloz'), ['id' => $c->id, 'parent_id' => null, 'position' => 0])
            ->assertOk()->assertJson(['ok' => true]);

        // W bazie są też strony systemowe z migracji — porównujemy tylko nasze trzy.
        $ordered = Page::whereIn('id', [$a->id, $b->id, $c->id])->orderBy('order')->pluck('title')->all();
        $this->assertSame(['C', 'A', 'B'], $ordered);
        $this->assertSame(0, $c->fresh()->order);
        $orders = Page::whereNull('parent_id')->orderBy('order')->pluck('order')->all();
        $this->assertSame(range(0, count($orders) - 1), $orders);
    }

    public function test_przeciaganie_wklada_strone_jako_podstrone_na_wskazanej_pozycji(): void
    {
        $root = $this->page('Dział');
        $x = $this->page('X', ['parent_id' => $root->id, 'order' => 0]);
        $y = $this->page('Y', ['parent_id' => $root->id, 'order' => 1]);
        $z = $this->page('Z');

        $this->actingAs($this->admin())->postJson(route('admin.podstrony.uloz'), ['id' => $z->id, 'parent_id' => $root->id, 'position' => 1])
            ->assertOk();

        $this->assertSame($root->id, $z->fresh()->parent_id);
        $this->assertSame(['X', 'Z', 'Y'], Page::where('parent_id', $root->id)->orderBy('order')->pluck('title')->all());
    }

    public function test_przeciaganie_odrzuca_cykl_nieistniejacego_rodzica_i_zablokowane(): void
    {
        $a = $this->page('A');
        $b = $this->page('B', ['parent_id' => $a->id]);
        $locked = $this->page('Zablokowana', ['is_locked' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.podstrony.uloz'), ['id' => $a->id, 'parent_id' => $b->id, 'position' => 0])
            ->assertStatus(422)->assertJson(['ok' => false]);
        $this->actingAs($admin)->postJson(route('admin.podstrony.uloz'), ['id' => $a->id, 'parent_id' => 99999, 'position' => 0])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('admin.podstrony.uloz'), ['id' => 99999, 'parent_id' => null, 'position' => 0])
            ->assertStatus(404);
        $this->actingAs($admin)->postJson(route('admin.podstrony.uloz'), ['id' => $a->id, 'position' => 'x'])
            ->assertStatus(422);
        $this->assertNull($a->fresh()->parent_id);

        // Redaktor (nie administrator) nie przeniesie zablokowanej strony.
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->postJson(route('admin.podstrony.uloz'), ['id' => $locked->id, 'parent_id' => $a->id, 'position' => 0])
            ->assertStatus(403);
    }

    public function test_drzewo_oznacza_wiersze_jako_przeciagalne(): void
    {
        $this->page('A');

        $this->actingAs($this->admin())->get(route('admin.podstrony.index'))
            ->assertOk()->assertSee('data-draggable="1"', false)->assertSee('Upuść tutaj, aby przenieść na poziom główny');
    }
}
