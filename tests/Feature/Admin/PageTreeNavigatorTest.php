<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * „Strony Nawigator” — dodatki wzorowane na drzewie stron TYPO3: gałąź (tymczasowy punkt montowania),
 * zapamiętany stan gałęzi, schowek (wytnij / kopiuj / wklej), strzałki, nakładki stanu na ikonach i historia w karcie.
 */
class PageTreeNavigatorTest extends TestCase
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

    public function test_galaz_zaweza_drzewo_i_jest_zapamietana_az_do_pokaz_cale_drzewo(): void
    {
        $a = $this->page('Alfa');
        $a1 = $this->page('Alfa jeden', ['parent_id' => $a->id]);
        $this->page('Beta');
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index', ['galaz' => $a->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Tylko gałąź:', $html);
        $this->assertStringContainsString('data-page-id="'.$a1->id.'"', $html);
        $this->assertStringNotContainsString('data-label="Beta"', $html);
        $this->assertSame($a->id, session('pages_tree_branch'));

        // Bez parametru gałąź zostaje (sesja); ?galaz=0 wraca do całego drzewa
        $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->assertDontSee('data-label="Beta"', false);
        $this->actingAs($admin)->get(route('admin.podstrony.index', ['galaz' => 0]))->assertOk()
            ->assertSee('data-label="Beta"', false)->assertDontSee('Tylko gałąź:');
    }

    public function test_drzewo_pamieta_rozwiniete_galezie_i_ma_strzalki_oraz_schowek(): void
    {
        $a = $this->page('Alfa');
        $this->page('Alfa jeden', ['parent_id' => $a->id]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index'))->assertOk()->getContent();
        $this->assertStringContainsString('window.pageTreeState', $html);
        $this->assertStringContainsString("window.pageTreeState?.has({$a->id})", $html);
        $this->assertStringContainsString("\$watch('open', v => window.pageTreeState?.set({$a->id}, v))", $html);
        // Menu strony: gałąź, wytnij, kopiuj, wklej; schowek nad drzewem; obsługa strzałek
        foreach (['data-act="branch"', 'data-act="cut"', 'data-act="copy-page"', 'data-act="paste-after"', 'data-act="paste-into"', 'id="tree-clip"', "'ArrowRight'", 'pageTreeClip:'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_wklej_kopie_tworzy_szkic_pod_wybrana_strona_na_pozycji(): void
    {
        $src = $this->page('Źródło');
        $dest = $this->page('Cel');
        $first = $this->page('Pierwsza', ['parent_id' => $dest->id, 'order' => 0]);
        $second = $this->page('Druga', ['parent_id' => $dest->id, 'order' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.wklej-kopie', $src), ['parent_id' => $dest->id, 'position' => 1])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Page::where('title', 'Źródło (kopia)')->firstOrFail();
        $this->assertSame($dest->id, $copy->parent_id);
        $this->assertFalse((bool) $copy->is_published);
        $this->assertSame([$first->id, $copy->id, $second->id], Page::where('parent_id', $dest->id)->orderBy('order')->pluck('id')->all());
        $this->assertNull($src->fresh()->parent_id);
    }

    public function test_wklej_kopie_do_nieistniejacej_strony_nadrzednej_zwraca_blad(): void
    {
        $src = $this->page('Źródło');
        $before = Page::count();

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.wklej-kopie', $src), ['parent_id' => 999999, 'position' => 0])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame($before, Page::count());
    }

    public function test_ikona_typu_z_nakladka_stanu_tylko_dla_wyjatkow(): void
    {
        $live = $this->page('Na żywo');
        $this->page('Szkicowa', ['is_published' => false]);
        $this->page('Wyłączona', ['is_disabled' => true]);
        $this->page('Kontakt', ['type' => 'contact']);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $live->id]))->assertOk()->getContent();

        $this->assertStringContainsString('tree-ov fa-solid fa-pen text-muted', $html);
        $this->assertStringContainsString('tree-ov fa-solid fa-ban text-red-700', $html);
        $this->assertStringContainsString('(szkic)', $html);
        // Typ strony ma swoją ikonę (kontakt: koperta)
        $this->assertStringContainsString('fa-solid fa-envelope text-xs', $html);
        // Opublikowana strona nie ma znaczka „fa-circle-check” w drzewie
        $tree = substr($html, strpos($html, 'id="tree-scroll"'));
        $this->assertStringNotContainsString('fa-circle-check', substr($tree, 0, strpos($tree, '</nav>')));
    }

    public function test_karta_pokazuje_historie_i_przywrocenie_wraca_do_drzewa(): void
    {
        $page = $this->page('Strona');
        $page->update(['title' => 'Strona nowa']);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Historia zmian', $html);
        $this->assertStringContainsString('zmieniono: tytuł', $html);
        $this->assertStringContainsString('bieżąca', $html);

        $old = $page->revisions()->reorder()->orderBy('id')->first();
        $this->actingAs($admin)
            ->post(route('admin.historia.restore', ['page', $page->id, $old->id]).'?wroc=drzewo')
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $page->id]));
        $this->assertSame('Strona', $page->fresh()->title);
    }

    public function test_znaczki_dat_wygasniecie_i_przeglad_tresci(): void
    {
        $live = $this->page('Zwykła');
        $exp = $this->page('Wygasająca', ['unpublish_at' => now()->addDays(10)]);
        $old = $this->page('Stara');
        Page::whereKey($old->id)->update(['updated_at' => now()->subMonths(14)]);

        $html = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $exp->id]))->assertOk()->getContent();
        $this->assertStringContainsString('tree-ov fa-solid fa-hourglass-half', $html);
        $this->assertStringContainsString('zniknie ze strony '.now()->addDays(10)->format('d.m.Y'), $html);
        $this->assertStringContainsString('tree-ov fa-solid fa-circle-exclamation', $html);
        $this->assertStringContainsString('Strona zniknie ze strony publicznej', $html);

        $card = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $old->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Oznacz jako sprawdzoną', $card);
        $this->assertStringContainsString('value="pages:'.$old->id.'"', $card);
    }

    public function test_wklej_kopie_z_podstronami_kopiuje_cala_galaz_jako_szkice(): void
    {
        $src = $this->page('Dział');
        $c1 = $this->page('Dziecko', ['parent_id' => $src->id]);
        $this->page('Wnuk', ['parent_id' => $c1->id]);
        $dest = $this->page('Cel');

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.wklej-kopie', $src), ['parent_id' => $dest->id, 'position' => 0, 'z_podstronami' => 1])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Page::where('title', 'Dział (kopia)')->firstOrFail();
        $this->assertSame($dest->id, $copy->parent_id);
        $child = Page::where('parent_id', $copy->id)->firstOrFail();
        $this->assertSame('Dziecko', $child->title);
        $this->assertFalse((bool) $child->is_published);
        $this->assertNotSame($c1->slug, $child->slug);
        $this->assertSame('Wnuk', Page::where('parent_id', $child->id)->value('title'));
        $this->assertSame(1, Page::where('parent_id', $src->id)->count());   // oryginał bez zmian
    }

    public function test_kopiowanie_galezi_do_wlasnej_podstrony_nie_zapetla(): void
    {
        $src = $this->page('Dział');
        $c1 = $this->page('Dziecko', ['parent_id' => $src->id]);
        $before = Page::count();

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.wklej-kopie', $src), ['parent_id' => $c1->id, 'position' => 0, 'z_podstronami' => 1])
            ->assertRedirect();
        $this->assertSame($before + 2, Page::count());
    }

    public function test_sprawdzenie_linkow_strony_pokazuje_niedzialajace_w_karcie(): void
    {
        \Illuminate\Support\Facades\Http::fake(['https://dziala.example/*' => \Illuminate\Support\Facades\Http::response('', 200), 'https://zepsuty.example/*' => \Illuminate\Support\Facades\Http::response('', 404)]);
        $page = $this->page('Linki', ['content' => '<p><a href="https://dziala.example/a">ok</a> <a href="https://zepsuty.example/b">zły</a> <a href="/nie-ma-takiej">wewn</a> <a href="#x">kotwica</a></p>']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.podstrony.sprawdz-linki', $page))
            ->assertRedirect(route('admin.podstrony.index', ['wybrana' => $page->id]).'#linki-strony')
            ->assertSessionHas('error');

        $html = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $page->id]))->assertOk()->getContent();
        $this->assertStringContainsString('nie działa: 2', $html);
        $this->assertStringContainsString('https://zepsuty.example/b', $html);
        $this->assertStringContainsString('Brak opublikowanej strony /nie-ma-takiej', $html);
        $this->assertStringNotContainsString('dziala.example/a</span>', $html);
    }
}
