<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\News;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Portal kategorii działań: /portal/{adres}, kanał RSS, przekierowanie subdomeny i ustawienia w panelu. */
class CategoryPortalTest extends TestCase
{
    use RefreshDatabase;

    private function portal(array $extra = []): Category
    {
        $category = Category::create($extra + ['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-i-warsztaty-test', 'portal_slug' => 'szkoleniatest']);
        $project = Project::create(['title' => 'Kurs WCAG dla NGO', 'slug' => 'kurs-wcag-ngo', 'is_published' => true, 'category_id' => $category->id, 'coordinator_name' => 'Anna Testowa', 'coordinator_email' => 'anna@example.org']);
        News::create(['title' => 'Ruszają zapisy na kurs', 'slug' => 'ruszaja-zapisy-kurs', 'content' => '<p>Treść</p>', 'is_published' => true, 'published_at' => now()->subDay(), 'project_id' => $project->id]);

        return $category;
    }

    public function test_portal_pokazuje_dzialania_aktualnosci_i_widzety(): void
    {
        $this->portal();

        $this->get('/portal/szkoleniatest')->assertOk()
            ->assertSee('Szkolenia i warsztaty')
            ->assertSee('Kurs WCAG dla NGO')          // menu działań i kafelki
            ->assertSee('Ruszają zapisy na kurs')     // aktualności z działań kategorii
            ->assertSee('Anna Testowa')               // widżet kontaktu
            ->assertSee('Na skróty')
            ->assertSee('Pozostańmy w kontakcie')
            ->assertSee(route('portal.feed', 'szkoleniatest'), false);

        $this->get('/portal/nie-ma-takiego')->assertNotFound();
    }

    public function test_widzety_w_ustawionej_kolejnosci_i_wylaczone_znikaja(): void
    {
        $this->portal(['portal_widgets' => ['stats', 'news', 'projects']]);

        $html = $this->get('/portal/szkoleniatest')->assertOk()->getContent();
        $this->assertStringNotContainsString('Anna Testowa', $html);          // kontakt wyłączony
        $this->assertLessThan(strpos($html, 'id="portal-w-projects"'), strpos($html, 'id="portal-w-stats"'));
    }

    public function test_bez_slidera_i_aktualnosci_dzialania_wchodza_do_glownej_kolumny(): void
    {
        $this->portal(['portal_widgets' => ['projects', 'stats']]);

        $html = $this->get('/portal/szkoleniatest')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="portal-w-projects"'));
        $this->assertLessThan(strpos($html, 'id="portal-nav"'), strpos($html, 'id="portal-w-projects"'));
    }

    public function test_zewnetrzny_rss_w_widzecie(): void
    {
        Http::fake(['https://example.org/*' => Http::response('<?xml version="1.0"?><rss version="2.0"><channel><item><title>Wpis z zewnątrz</title><link>https://example.org/wpis</link><pubDate>Tue, 13 Oct 2026 10:00:00 +0200</pubDate></item><item><title>Zły link</title><link>javascript:alert(1)</link></item></channel></rss>')]);
        $this->portal(['portal_rss_url' => 'https://example.org/rss.xml', 'portal_widgets' => ['rss']]);

        $this->get('/portal/szkoleniatest')->assertOk()->assertSee('Wpis z zewnątrz')->assertSee('https://example.org/wpis', false)->assertDontSee('javascript:alert', false);
    }

    public function test_kanal_rss_portalu(): void
    {
        $this->portal();

        $this->get('/portal/szkoleniatest/rss.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<title>Ruszają zapisy na kurs</title>', false)
            ->assertSee('<title>Kurs WCAG dla NGO</title>', false);
    }

    public function test_subdomena_przekierowuje_na_portal(): void
    {
        $this->portal();
        config(['app.url' => 'https://feer.test']);

        $this->get('https://szkoleniatest.feer.test/?utm=x')->assertRedirect('https://feer.test/portal/szkoleniatest?utm=x');
        // Subdomena bez portalu i domena główna — bez przekierowania na portal.
        $this->assertStringNotContainsString('/portal/', (string) $this->get('https://nieznana.feer.test/')->headers->get('Location'));
        $this->assertNotSame('https://feer.test/portal/szkoleniatest', $this->get('https://feer.test/')->headers->get('Location'));
    }

    public function test_panel_zapisuje_ustawienia_portalu_i_odrzuca_zarezerwowany_adres(): void
    {
        $category = Category::create(['name' => 'Edukacja i materiały', 'slug' => 'edukacja-i-materialy-test']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.kategorie.update', $category), [
            'name' => 'Edukacja i materiały', 'slug' => 'edukacja-i-materialy-test',
            'portal_slug' => 'cms2',
        ])->assertSessionHasErrors('portal_slug');

        $this->actingAs($admin)->put(route('admin.kategorie.update', $category), [
            'name' => 'Edukacja i materiały', 'slug' => 'edukacja-i-materialy-test',
            'portal_slug' => 'edukacjatest', 'portal_intro' => 'Materiały i kursy.',
            'portal_widgets' => ['news', 'projects'], 'portal_widgets_order' => 'projects,stats,news',
        ])->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('edukacjatest', $category->portal_slug);
        $this->assertSame(['projects', 'news'], $category->portal_widgets);
        $this->actingAs($admin)->get(route('admin.kategorie.edit', $category))->assertOk()->assertSee('Portal kategorii');
    }
}
