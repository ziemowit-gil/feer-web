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
                'shortcuts' => "Regulamin | /regulamin\n\nKurs online | https://example.org/kurs",
                'contact_text' => 'Masz pytanie? Napisz.', 'contact_email' => 'szkolenia@example.org', 'contact_phone' => '+48 600 100 200',
                'stay_enabled' => '0', 'stay_title' => '',
                'titles' => ['news' => 'Co nowego', 'contact' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $options = $category->refresh()->portal_options;
        $this->assertSame('Zapisy do 30 listopada', $options['notice']);
        $this->assertSame([['label' => 'Regulamin', 'url' => '/regulamin'], ['label' => 'Kurs online', 'url' => 'https://example.org/kurs']], $options['shortcuts']);
        $this->assertFalse($options['stay_enabled']);
        $this->assertSame(['news' => 'Co nowego'], $options['titles']);
        $this->assertArrayNotHasKey('stay_title', $options);

        $this->get(route('admin.kategorie.edit', $category))->assertOk()
            ->assertSee('Komunikat nad portalem')->assertSee('Regulamin | /regulamin');
    }

    public function test_panel_odrzuca_zle_skroty_i_adres(): void
    {
        $category = $this->portal();
        $base = ['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-opcje-test', 'portal_slug' => 'opcjetest'];

        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), $base + ['portal_options' => ['shortcuts' => 'Zły | javascript:alert(1)']])
            ->assertSessionHasErrors('portal_options.shortcuts');
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
                'shortcuts' => [['label' => 'Regulamin kursu', 'url' => '/regulamin']],
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
}
