<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\News;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Edytowalne elementy portalu kategorii (portal_options): zapis w panelu i renderowanie /portal. */
class PortalOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function portal(array $extra = []): Category
    {
        $category = Category::create($extra + ['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-opcje-test', 'portal_slug' => 'opcjetest']);
        $project = Project::create(['title' => 'Kurs WCAG dla NGO', 'slug' => 'kurs-wcag-opcje', 'is_published' => true, 'category_id' => $category->id]);
        News::create(['title' => 'Ruszają zapisy', 'slug' => 'ruszaja-zapisy-opcje', 'content' => '<p>Treść</p>', 'is_published' => true, 'published_at' => now()->subDay(), 'project_id' => $project->id]);

        return $category;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_panel_zapisuje_elementy_portalu(): void
    {
        $category = $this->portal();

        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), [
            'name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-opcje-test', 'portal_slug' => 'opcjetest',
            'portal_widgets_order' => 'contact,news', 'portal_widgets' => ['contact', 'news'],
            'portal_options' => [
                'notice' => 'Zapisy do 30 listopada', 'notice_link_label' => 'Zapisz się', 'notice_link_url' => '/zapisy',
                'shortcuts_json' => json_encode([['label' => 'Regulamin', 'url' => '/regulamin', 'icon' => 'fa-regular fa-file-lines'], ['label' => '', 'url' => ''], ['label' => 'Kurs online', 'url' => 'https://example.org/kurs', 'icon' => 'fa-solid fa-hack']]),
                'contact_text' => 'Masz pytanie? Napisz.', 'contact_email' => 'szkolenia@example.org', 'contact_phone' => '+48 600 100 200',
                'stay_enabled' => '0', 'stay_title' => '',
                'titles' => ['news' => 'Co nowego', 'contact' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $options = $category->refresh()->portal_options;
        $this->assertSame('Zapisy do 30 listopada', $options['notice']);
        // Pusty wiersz pominięty, ikona spoza listy odrzucona.
        $this->assertSame([['label' => 'Regulamin', 'url' => '/regulamin', 'icon' => 'fa-regular fa-file-lines'], ['label' => 'Kurs online', 'url' => 'https://example.org/kurs']], $options['shortcuts']);
        $this->assertFalse($options['stay_enabled']);
        $this->assertSame(['news' => 'Co nowego'], $options['titles']);
        $this->assertArrayNotHasKey('stay_title', $options);

        $this->get(route('admin.kategorie.edit', $category))->assertOk()
            ->assertSee('Komunikat nad portalem')->assertSee('Menu portalu')->assertSee('Układ portalu')->assertSee('Regulamin');
    }

    public function test_panel_odrzuca_zle_skroty_i_adres(): void
    {
        $category = $this->portal();
        $base = ['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-opcje-test', 'portal_slug' => 'opcjetest'];

        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), $base + ['portal_options' => ['shortcuts_json' => json_encode([['label' => 'Zły', 'url' => 'javascript:alert(1)']])]])
            ->assertSessionHasErrors('portal_options.shortcuts_json');
        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), $base + ['portal_options' => ['nav_json' => json_encode([['type' => 'link', 'label' => 'X', 'url' => 'javascript:alert(1)']])]])
            ->assertSessionHasErrors('portal_options.nav_json');
        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), $base + ['portal_options' => ['layout' => ['sidebar' => 'gora']]])
            ->assertSessionHasErrors('portal_options.layout.sidebar');
        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), $base + ['portal_options' => ['notice_link_url' => 'javascript:alert(1)', 'contact_email' => 'nie-email']])
            ->assertSessionHasErrors(['portal_options.notice_link_url', 'portal_options.contact_email']);
    }

    public function test_portal_pokazuje_elementy_z_panelu(): void
    {
        $this->portal([
            'portal_intro' => "Pierwszy akapit wstępu.\n\nDrugi akapit wstępu.",
            'portal_widgets' => ['contact', 'news'],
            'portal_options' => [
                'notice' => 'Zapisy do 30 listopada', 'notice_link_label' => 'Zapisz się', 'notice_link_url' => '/zapisy',
                'shortcuts' => [['label' => 'Regulamin kursu', 'url' => '/regulamin', 'icon' => 'fa-regular fa-file-lines']],
                'contact_text' => 'Masz pytanie? Napisz.', 'contact_email' => 'szkolenia@example.org',
                'stay_enabled' => false,
                'titles' => ['news' => 'Co nowego'],
            ],
        ]);

        $html = $this->get('/portal/opcjetest')->assertOk()
            ->assertSee('Zapisy do 30 listopada')->assertSee('href="/zapisy"', false)
            ->assertSee('<p>Pierwszy akapit wstępu.</p>', false)->assertSee('<p>Drugi akapit wstępu.</p>', false)
            ->assertSee('Regulamin kursu')->assertDontSee('Kanał RSS portalu')   // własne „Na skróty” zastępują domyślne
            ->assertSee('mailto:szkolenia@example.org', false)
            ->assertSee('Co nowego')
            ->assertDontSee('id="portal-stay"', false)
            ->getContent();

        // Kolejność z panelu: kontakt przed aktualnościami.
        $this->assertLessThan(strpos($html, 'id="portal-news"'), strpos($html, 'id="portal-w-contact"'));
    }

    public function test_domyslnie_bez_liczb_i_z_sekcja_kontaktu(): void
    {
        $this->portal();

        $this->get('/portal/opcjetest')->assertOk()
            ->assertDontSee('id="portal-w-stats"', false)
            ->assertDontSee('role="region" aria-label="Komunikat"', false)
            ->assertSee('Pozostańmy w kontakcie')
            ->assertSee('Kanał RSS portalu');
    }

    public function test_menu_portalu_kolejnosc_ukrywanie_nazwy_i_wlasne_linki(): void
    {
        $category = $this->portal();
        $first = Project::where('slug', 'kurs-wcag-opcje')->first();
        $second = Project::create(['title' => 'Warsztaty z audiodeskrypcji', 'slug' => 'warsztaty-ad-opcje', 'is_published' => true, 'category_id' => $category->id]);
        $hidden = Project::create(['title' => 'Stary projekt archiwalny', 'slug' => 'stary-opcje', 'is_published' => true, 'category_id' => $category->id]);
        $new = Project::create(['title' => 'Nowe działanie bez ustawień', 'slug' => 'nowe-opcje', 'is_published' => true, 'category_id' => $category->id]);

        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), [
            'name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-opcje-test', 'portal_slug' => 'opcjetest',
            'portal_options' => [
                'nav_title' => 'Nasze szkolenia',
                'nav_json' => json_encode([
                    ['type' => 'project', 'id' => $second->id, 'label' => 'Audiodeskrypcja', 'subs' => 'expanded'],
                    ['type' => 'link', 'label' => 'Zapisy online', 'url' => 'https://example.org/zapisy'],
                    ['type' => 'project', 'id' => $first->id, 'label' => '', 'subs' => 'collapsed'],
                    ['type' => 'project', 'id' => $hidden->id, 'hidden' => true],
                ]),
            ],
        ])->assertSessionHasNoErrors();

        $html = $this->get('/portal/opcjetest')->assertOk()->getContent();
        $nav = substr($html, strpos($html, 'id="portal-nav"'), 4000);
        $this->assertStringContainsString('Nasze szkolenia', $nav);
        $this->assertStringNotContainsString('Stary projekt archiwalny', $nav);
        $positions = array_map(fn ($t) => strpos($nav, $t), ['Audiodeskrypcja', 'Zapisy online', 'Kurs WCAG dla NGO', 'Nowe działanie bez ustawień']);
        $sorted = $positions;
        sort($sorted);
        $this->assertNotContains(false, $positions);
        $this->assertSame($sorted, $positions);
    }

    public function test_uklad_kolumna_po_lewej_kafle_akcji_i_widzety_na_pol(): void
    {
        $this->portal([
            'portal_widgets' => ['news', 'contact', 'projects'],
            'portal_options' => [
                'contact_email' => 'szkolenia@example.org',
                'shortcuts' => [['label' => 'Zapisz się teraz', 'url' => '/zapisy', 'icon' => 'fa-regular fa-pen-to-square']],
                'layout' => ['sidebar' => 'left', 'slider' => 'hide', 'shortcuts' => 'tiles', 'widths' => ['news' => 'half', 'contact' => 'half']],
            ],
        ]);

        $html = $this->get('/portal/opcjetest')->assertOk()->getContent();
        $this->assertStringContainsString('lg:grid-cols-[20rem_minmax(0,1fr)]', $html);
        // Kolumna boczna po lewej: tytuł strony nadal przed menu, menu przed treścią główną.
        $this->assertLessThan(strpos($html, 'id="portal-nav"'), strpos($html, '<h1'));
        // Szybkie akcje jako kafle (nagłówek h2), nie w kolumnie bocznej.
        $this->assertStringContainsString('<h2 id="portal-shortcuts"', $html);
        $this->assertStringContainsString('fa-regular fa-pen-to-square', $html);
        // Aktualności i kontakt w jednym wierszu na pół.
        $this->assertMatchesRegularExpression('/lg:grid-cols-2 lg:gap-10">\s*<section aria-labelledby="portal-news"/', $html);
        // Slider wyłączony — działania w głównej kolumnie.
        $this->assertStringNotContainsString('portal-slider', $html);
        $this->assertStringContainsString('id="portal-w-projects"', $html);
    }

    public function test_wylaczenie_portali_w_ustawieniach(): void
    {
        $category = $this->portal();
        $project = Project::where('slug', 'kurs-wcag-opcje')->first();
        \App\Models\SiteSetting::current()->update(['portals_enabled' => false]);

        $this->get('/portal/opcjetest')->assertNotFound();
        $this->get('/portal/opcjetest/rss.xml')->assertNotFound();
        $this->assertFalse($category->fresh()->hasPortal());
        // Linki działania prowadzą do zwykłej strony kategorii.
        $this->get(route('projects.show', $project))->assertOk()->assertDontSee(route('portal.show', 'opcjetest'), false);

        \App\Models\SiteSetting::current()->update(['portals_enabled' => true]);
        $this->get('/portal/opcjetest')->assertOk();
    }

    public function test_panel_ustawien_zapisuje_wlacznik_portali(): void
    {
        $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))->assertOk()->assertSee('Portale kategorii');
    }
}
