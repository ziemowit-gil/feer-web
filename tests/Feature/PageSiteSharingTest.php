<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strony w sieci witryn: „Kopiuj do witryny” (szkic w innej witrynie) i „Podstrona globalna” (widoczna we wszystkich witrynach). */
class PageSiteSharingTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function page(string $slug, array $extra = []): Page
    {
        return Page::withoutGlobalScopes()->create($extra + [
            'site_id' => $this->main->id, 'title' => 'Strona '.$slug, 'slug' => $slug, 'type' => 'standard', 'is_published' => true, 'content' => '<p>Treść '.$slug.'.</p>',
        ]);
    }

    public function test_kopiuje_strone_do_witryny_jako_szkic_z_unikalnym_slugiem(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = $this->page('rodzic');
        $page = $this->page('oferta', ['parent_id' => $parent->id, 'is_system' => true, 'is_locked' => false]);

        $this->actingAs($admin)->post(route('admin.podstrony.copy-to-site', $page), ['target_site_id' => $this->sub->id])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Page::withoutGlobalScopes()->where('site_id', $this->sub->id)->sole();
        $this->assertSame('Strona oferta', $copy->title);
        $this->assertSame('strona-oferta', $copy->slug, 'slug powstaje z tytułu i jest unikalny globalnie');
        $this->assertFalse((bool) $copy->is_published, 'kopia to szkic');
        $this->assertNull($copy->parent_id);
        $this->assertFalse((bool) $copy->is_system);
        $this->assertSame('<p>Treść oferta.</p>', $copy->content);
        $this->assertSame($this->main->id, $page->fresh()->site_id, 'oryginał zostaje w swojej witrynie');
    }

    public function test_kopiowanie_wymaga_admina_i_innej_witryny(): void
    {
        $page = $this->page('oferta');
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->post(route('admin.podstrony.copy-to-site', $page), ['target_site_id' => $this->sub->id])->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post(route('admin.podstrony.copy-to-site', $page), ['target_site_id' => $this->main->id])->assertSessionHas('error');
        $this->assertSame(1, Page::withoutGlobalScopes()->where('slug', 'like', 'oferta%')->count());
    }

    public function test_podstrona_globalna_jest_dostepna_w_podwitrynie_a_zwykla_nie(): void
    {
        $this->page('regulamin-global', ['is_global' => true]);
        $this->page('tylko-glowna');

        // Najpierw główna witryna (po żądaniu na domenie sub-witryny kontener zostaje z jej powiązaniem).
        $this->get('/tylko-glowna')->assertOk();
        $this->get('/regulamin-global')->assertOk();

        $this->get('http://szkolenia.example.test/regulamin-global')->assertOk()->assertSee('Treść regulamin-global.');
        $this->get('http://szkolenia.example.test/tylko-glowna')->assertNotFound();
    }

    public function test_wlasna_strona_witryny_ma_pierwszenstwo_przed_globalna(): void
    {
        $this->page('wspolny', ['is_global' => true]);
        // slugi są unikalne globalnie, więc „własna” strona witryny o tym slugu nie istnieje; wiązanie najpierw szuka w witrynie
        $resolved = (new Page)->resolveRouteBinding('wspolny', 'slug');
        $this->assertSame('wspolny', $resolved?->slug);
    }

    public function test_niepublikowana_strona_globalna_nie_jest_pokazywana(): void
    {
        $this->page('szkic-global', ['is_global' => true, 'is_published' => false]);

        $this->get('http://szkolenia.example.test/szkic-global')->assertNotFound();
    }

    public function test_panel_ma_pole_globalnej_i_zapisuje_je_tylko_adminowi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = $this->page('do-edycji');

        $form = $this->actingAs($admin)->get(route('admin.podstrony.edit', [$page, 'klasyczny' => 1]))->assertOk()->getContent();
        $this->assertStringContainsString('Podstrona globalna', $form);
        $this->assertStringContainsString('name="is_global"', $form);

        $page->update(['is_global' => true]);
        $this->assertTrue($page->fresh()->is_global);
    }

    public function test_przenosi_ustawienia_wygladu_na_podstrony(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = $this->page('rodzic', ['side_nav_style' => 'accordion', 'show_gallery' => false, 'layout_data' => ['hide_attachments' => true]]);
        $child = $this->page('dziecko', ['parent_id' => $parent->id, 'layout_data' => ['metka' => 'x']]);
        $grand = $this->page('wnuk', ['parent_id' => $child->id]);

        $this->actingAs($admin)->post(route('admin.podstrony.ustawienia-na-podstrony', $parent))->assertRedirect()->assertSessionHas('status');

        $child->refresh();
        $this->assertSame('accordion', $child->side_nav_style);
        $this->assertTrue($child->layout_data['hide_attachments']);
        $this->assertSame('x', $child->layout_data['metka'], 'inne klucze layout_data zostają');
        $this->assertSame('accordion', $grand->fresh()->side_nav_style, 'także zagnieżdżone');
        $this->assertSame('<p>Treść dziecko.</p>', $child->content);
    }
}
