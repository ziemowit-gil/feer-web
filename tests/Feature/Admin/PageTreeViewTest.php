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
}
