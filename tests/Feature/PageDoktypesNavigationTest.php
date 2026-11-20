<?php

namespace Tests\Feature;

use App\Models\EtrContent;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SubpageList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Strony w duchu TYPO3: doktypy (skrót, folder systemowy), tytuł w nawigacji, „Ukryj w menu”,
 * adresy stron osób przy zmianie adresu i przeniesieniu (301), ustawienia dziedziczone w głąb drzewa
 * i kopiowanie gałęzi z podstronami.
 */
class PageDoktypesNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function page(string $title, array $extra = []): Page
    {
        return Page::create($extra + ['title' => $title, 'slug' => str($title)->slug()->toString(), 'type' => 'standard', 'is_published' => true, 'show_side_nav' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_skrot_przenosi_do_pierwszej_podstrony_wskazanej_strony_i_w_menu_linkuje_wprost(): void
    {
        $section = $this->page('Dział');
        $shortcut = $this->page('Na skróty', ['type' => 'shortcut', 'parent_id' => $section->id, 'type_data' => ['shortcut_mode' => 'first_child']]);
        $this->page('Ukryta pierwsza', ['parent_id' => $shortcut->id, 'order' => 0, 'hide_in_menu' => true]);
        $first = $this->page('Pierwsza widoczna', ['parent_id' => $shortcut->id, 'order' => 1]);
        $sibling = $this->page('Sąsiednia', ['parent_id' => $section->id]);

        $this->get($shortcut->publicUrl())->assertStatus(302)->assertRedirect($first->publicUrl());

        // W menu bocznym sąsiedniej strony skrót prowadzi od razu do celu.
        $html = $this->get($sibling->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('href="'.$first->publicUrl().'"', $html);
        $this->assertStringNotContainsString('href="'.$shortcut->publicUrl().'"', $html);

        $shortcut->update(['type_data' => ['shortcut_mode' => 'page', 'target_page_id' => $sibling->id]]);
        $this->get($shortcut->publicUrl())->assertRedirect($sibling->publicUrl());

        // Bez celu (brak opublikowanych podstron) — do strony nadrzędnej.
        $empty = $this->page('Pusty skrót', ['type' => 'shortcut', 'parent_id' => $section->id]);
        $this->get($empty->publicUrl())->assertRedirect($section->publicUrl());
    }

    public function test_folder_systemowy_nie_ma_strony_menu_ani_mapy(): void
    {
        $folder = $this->page('Archiwum kampanii', ['type' => 'sys_folder', 'show_in_menu' => true]);
        $inside = $this->page('Kampania 2025', ['parent_id' => $folder->id]);
        $this->page('Kontakt główny');

        $this->get($folder->publicUrl())->assertNotFound();
        $this->get($folder->previewUrl())->assertOk()->assertSee('To folder systemowy');
        $this->get($inside->publicUrl())->assertOk()
            // Okruszki pomijają folder (nie ma adresu publicznego).
            ->assertDontSee('Archiwum kampanii');

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();
        $this->assertStringNotContainsString($folder->publicUrl().'<', $xml);
        $this->assertStringContainsString($inside->publicUrl().'<', $xml);
        $this->assertStringNotContainsString('Archiwum kampanii', $this->get(route('sitemap.page'))->assertOk()->getContent());

        $this->assertFalse($folder->shouldBeSearchable());
        $this->assertSame([], Page::find($folder->id)->parent?->menuChildren?->all() ?? []);
        $this->assertTrue(Page::query()->whereNull('parent_id')->get()->every(fn (Page $p) => $p->menuChildren->doesntContain('id', $folder->id)));
    }

    public function test_sitemap_xml_pomija_skroty_i_linki(): void
    {
        $link = $this->page('Profil FB', ['type' => 'menu_link', 'type_data' => ['target_url' => 'https://facebook.com/feer']]);
        $shortcut = $this->page('Skrót do startu', ['type' => 'shortcut']);
        $regular = $this->page('Zwykła strona');

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();
        $this->assertStringNotContainsString($link->publicUrl().'<', $xml);
        $this->assertStringNotContainsString($shortcut->publicUrl().'<', $xml);
        $this->assertStringContainsString($regular->publicUrl().'<', $xml);
    }

    public function test_tytul_w_nawigacji_w_menu_bocznym_i_okruszkach_a_h1_i_title_bez_zmian(): void
    {
        $section = $this->page('Fundacja Ekonomii Empatii i Rozwoju', ['nav_title' => 'Fundacja']);
        $child = $this->page('Sprawozdania finansowe i merytoryczne', ['parent_id' => $section->id, 'nav_title' => 'Sprawozdania', 'meta_title' => 'Sprawozdania FEER — SEO']);
        $this->page('Statut fundacji', ['parent_id' => $section->id]);

        $html = $this->get($child->publicUrl())->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<h1[^>]*>\s*Sprawozdania finansowe i merytoryczne\s*</h1>~', $html);
        $this->assertStringContainsString('<title>Sprawozdania FEER — SEO', $html);
        // Okruszki i nagłówek działu: tytuł w nawigacji rodzica.
        $this->assertStringContainsString('>Fundacja<', $html);
        $this->assertStringNotContainsString('>Fundacja Ekonomii Empatii i Rozwoju<', $html);
        // Lista podstron [podstrony] też używa krótkiej nazwy.
        $list = SubpageList::render([], $section);
        $this->assertStringContainsString('Sprawozdania', $list);
        $this->assertStringNotContainsString('finansowe i merytoryczne', $list);
    }

    public function test_ukryj_w_menu_pomija_strone_w_menu_i_listach_ale_strona_dziala(): void
    {
        $section = $this->page('Dział');
        $visible = $this->page('Widoczna', ['parent_id' => $section->id]);
        $hidden = $this->page('Tylko z linku', ['parent_id' => $section->id, 'hide_in_menu' => true]);

        $this->get($hidden->publicUrl())->assertOk()->assertSee('Tylko z linku');

        $html = $this->get($visible->publicUrl())->assertOk()->getContent();
        $this->assertStringNotContainsString('href="'.$hidden->publicUrl().'"', $html);
        $this->assertStringNotContainsString('Tylko z linku', SubpageList::render([], $section));
        $this->assertSame([$visible->id], $section->menuChildren()->pluck('id')->all());
        // Strona z „Ukryj w menu” nie trafia do „Menu podstron” (strony główne).
        $top = $this->page('Strona główna działu ukryta', ['show_in_menu' => true, 'hide_in_menu' => true]);
        $this->assertFalse(Page::excludeHiddenInMenu(Page::query()->whereNull('parent_id'))->pluck('id')->contains($top->id));
    }

    public function test_formularz_zapisuje_tytul_w_nawigacji_ukrycie_i_ustawienia_dziedziczone(): void
    {
        $page = $this->page('Strona działu');

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), [
            'title' => 'Strona działu', 'slug' => $page->slug, 'type' => 'standard', 'is_published' => 1, 'parent_id' => '',
            'nav_title' => '  Dział  ', 'hide_in_menu' => 1,
            'layout_data' => ['contact_name' => 'Anna Nowak', 'contact_email' => 'anna@example.org', 'contact_role' => '', 'banner_off' => '0', 'banner_image' => ''],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('Dział', $page->nav_title);
        $this->assertTrue($page->hide_in_menu);
        $this->assertSame('Anna Nowak', $page->layout_data['contact_name']);
        $this->assertArrayNotHasKey('contact_role', $page->layout_data);
        $this->assertArrayNotHasKey('banner_off', $page->layout_data);
        $this->assertArrayNotHasKey('banner_image', $page->layout_data);
    }

    public function test_edytor_blokowy_pokazuje_i_zapisuje_tytul_w_nawigacji_i_ukrycie_w_menu(): void
    {
        $page = $this->page('Sprawozdania finansowe', ['nav_title' => 'Sprawozdania']);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()->getContent();
        $this->assertStringContainsString('"nav_title":"Sprawozdania"', $html);
        $this->assertStringContainsString('"hide_in_menu":false', $html);

        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Sprawozdania finansowe', 'blocks' => [],
            'settings' => ['is_published' => true, 'nav_title' => '  Raporty  ', 'hide_in_menu' => true],
        ])->assertOk();
        $page->refresh();
        $this->assertSame(['Raporty', true], [$page->nav_title, $page->hide_in_menu]);

        // Zapis bez tych kluczy (starszy edytor) nie zeruje tytułu w nawigacji; puste pole go czyści.
        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Sprawozdania finansowe', 'blocks' => [], 'settings' => ['is_published' => true],
        ])->assertOk();
        $this->assertSame(['Raporty', true], [$page->fresh()->nav_title, $page->fresh()->hide_in_menu]);
        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Sprawozdania finansowe', 'blocks' => [], 'settings' => ['is_published' => true, 'nav_title' => '', 'hide_in_menu' => false],
        ])->assertOk();
        $this->assertSame([null, false], [$page->fresh()->nav_title, $page->fresh()->hide_in_menu]);
    }

    public function test_formularz_skrotu_wymaga_strony_w_trybie_wskazanej_strony(): void
    {
        $page = $this->page('Skrót', ['type' => 'shortcut']);

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), [
            'title' => 'Skrót', 'slug' => $page->slug, 'type' => 'shortcut', 'is_published' => 1, 'parent_id' => '',
            'type_data' => ['shortcut_mode' => 'page'],
        ])->assertSessionHasErrors('type_data.target_page_id');

        $html = $this->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();
        $this->assertStringContainsString('name="type_data[shortcut_mode]"', $html);
        $this->assertStringContainsString('name="nav_title"', $html);
        $this->assertStringContainsString('name="hide_in_menu"', $html);
    }

    public function test_zmiana_adresu_rodzica_i_przeniesienie_osoby_daje_nowe_adresy_osob_z_301(): void
    {
        $team = $this->page('Zespół');
        $person = Page::create(['title' => 'Jan Kowalski', 'slug' => 'zespol/osoba/jan-kowalski', 'type' => 'about_person', 'parent_id' => $team->id, 'is_published' => true]);

        $team->update(['slug' => 'nasz-zespol']);

        $person->refresh();
        $this->assertSame('nasz-zespol/osoba/jan-kowalski', $person->slug);
        $this->assertDatabaseHas('redirects', ['from_path' => '/zespol/osoba/jan-kowalski', 'to_url' => '/nasz-zespol/osoba/jan-kowalski']);
        $this->get('/zespol/osoba/jan-kowalski')->assertStatus(301)->assertRedirect('/nasz-zespol/osoba/jan-kowalski');

        // Przeniesienie strony osoby w drzewie pod inną stronę.
        $board = $this->page('Zarząd');
        $person->update(['parent_id' => $board->id]);
        $this->assertSame('zarzad/osoba/jan-kowalski', $person->fresh()->slug);
        $this->get('/nasz-zespol/osoba/jan-kowalski')->assertStatus(301)->assertRedirect('/zarzad/osoba/jan-kowalski');
    }

    public function test_przeniesienie_zwyklej_strony_nie_zmienia_adresu(): void
    {
        $a = $this->page('Alfa');
        $b = $this->page('Beta');
        $child = $this->page('Podstrona', ['parent_id' => $a->id]);

        $child->update(['parent_id' => $b->id]);

        $this->assertSame('podstrona', $child->fresh()->slug);
        $this->get($child->publicUrl())->assertOk();
        $this->assertDatabaseCount('redirects', 0);
    }

    public function test_baner_i_kontakt_dziedzicza_w_glab_drzewa_z_nadpisaniem_i_wylaczeniem(): void
    {
        $section = $this->page('Dział', ['layout_data' => [
            'banner_image' => '/storage/baner-dzialu.jpg', 'banner_alt' => 'Ludzie przy stole',
            'contact_name' => 'Anna Nowak', 'contact_email' => 'anna@example.org', 'contact_phone' => '+48 600 100 200',
        ]]);
        $child = $this->page('Podstrona', ['parent_id' => $section->id]);
        $grandchild = $this->page('Głębiej', ['parent_id' => $child->id, 'layout_data' => ['contact_name' => 'Piotr Lis']]);
        $off = $this->page('Bez banera', ['parent_id' => $section->id, 'layout_data' => ['banner_off' => true]]);

        $banner = $grandchild->inheritedSetting('banner');
        $this->assertTrue($banner['inherited']);
        $this->assertSame($section->id, $banner['source']->id);
        $contact = $grandchild->inheritedSetting('contact');
        $this->assertFalse($contact['inherited']);
        $this->assertSame('Piotr Lis', $contact['values']['contact_name']);
        $this->assertNull($off->inheritedSetting('banner'));

        $html = $this->get($child->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('src="/storage/baner-dzialu.jpg" alt="Ludzie przy stole"', $html);
        $this->assertStringContainsString('Kontakt w sprawie tej strony', $html);
        $this->assertStringContainsString('href="mailto:anna@example.org"', $html);
        $this->assertStringContainsString('href="tel:+48600100200"', $html);
        $this->assertStringNotContainsString('baner-dzialu.jpg', $this->get($off->publicUrl())->getContent());

        // Karta w Stronach Nawigatorze mówi, skąd wartość jest dziedziczona.
        $card = $this->actingAs($this->admin())->get(route('admin.podstrony.index', ['wybrana' => $child->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~data-inherited="banner".*?dziedziczony z „<a[^>]*>Dział</a>”~s', $card);
    }

    public function test_karta_pokazuje_cel_skrotu_folder_i_ukrycie(): void
    {
        $section = $this->page('Dział');
        $target = $this->page('Cel skrótu', ['parent_id' => $section->id]);
        $shortcut = $this->page('Skrót', ['type' => 'shortcut', 'type_data' => ['shortcut_mode' => 'page', 'target_page_id' => $target->id], 'hide_in_menu' => true]);
        $folder = $this->page('Folder', ['type' => 'sys_folder']);
        $admin = $this->admin();

        $card = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $shortcut->id]))->assertOk()->getContent();
        $this->assertStringContainsString('odwiedzający trafiają na „<a', $card);
        $this->assertStringContainsString('>Cel skrótu</a>', $card);
        $this->assertStringContainsString('data-hidden-in-menu-note', $card);
        $this->assertStringContainsString('fa-diamond-turn-right', $card);

        $folderCard = $this->actingAs($admin)->get(route('admin.podstrony.index', ['wybrana' => $folder->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Folder systemowy — porządkuje strony w panelu', $folderCard);
        $this->assertStringContainsString('fa-box-archive', $folderCard);
    }

    public function test_kopia_galezi_kopiuje_bloki_ustawienia_etr_i_przepina_skroty_bez_profili_osob(): void
    {
        $publisher = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $src = $this->page('Dział', [
            'blocks' => [['type' => 'paragraph', 'text' => 'Akapit']], 'content' => '<p>Akapit</p>',
            'layout_data' => ['contact_name' => 'Anna Nowak'], 'nav_title' => 'Krótko',
            'published_by_id' => $publisher->id, 'pending_approval' => false,
        ]);
        $first = $this->page('Pierwsza', ['parent_id' => $src->id, 'order' => 0]);
        $this->page('Skrót', ['parent_id' => $src->id, 'order' => 1, 'type' => 'shortcut', 'type_data' => ['shortcut_mode' => 'page', 'target_page_id' => $first->id]]);
        Page::create(['title' => 'Jan Kowalski', 'slug' => 'dzial/osoba/jan-kowalski', 'type' => 'about_person', 'parent_id' => $src->id, 'is_published' => true]);
        EtrContent::create(['etrable_type' => $src->getMorphClass(), 'etrable_id' => $src->id, 'etr_content' => '<p>Łatwy tekst</p>', 'is_enabled' => true]);
        $dest = $this->page('Cel');

        $this->actingAs($this->admin())
            ->post(route('admin.podstrony.wklej-kopie', $src), ['parent_id' => $dest->id, 'position' => 0, 'z_podstronami' => 1])
            ->assertRedirect()->assertSessionHas('status');

        $copy = Page::where('title', 'Dział (kopia)')->firstOrFail();
        $this->assertSame([['type' => 'paragraph', 'text' => 'Akapit']], $copy->blocks);
        $this->assertSame('Anna Nowak', $copy->layout_data['contact_name']);
        $this->assertSame('Krótko', $copy->nav_title);
        $this->assertNull($copy->published_by_id);
        $this->assertSame('<p>Łatwy tekst</p>', $copy->etr()->first()?->etr_content);

        $copiedFirst = Page::where('parent_id', $copy->id)->where('title', 'Pierwsza')->firstOrFail();
        $copiedShortcut = Page::where('parent_id', $copy->id)->where('title', 'Skrót')->firstOrFail();
        $this->assertSame($copiedFirst->id, (int) $copiedShortcut->type_data['target_page_id']);
        // Profile osób (moduł „Osoby”) nie są dublowane.
        $this->assertFalse(Page::where('parent_id', $copy->id)->where('type', 'about_person')->exists());
    }
}
