<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavItem;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectArchiveFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function completed(string $title, ?string $date): Project
    {
        $category = Category::firstOrCreate(['slug' => 'archiwum'], ['name' => 'Archiwum']);

        return Project::create([
            'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title), 'category_id' => $category->id,
            'is_published' => true, 'is_completed' => true, 'completed_at' => $date,
        ]);
    }

    public function test_archive_splits_projects_by_completion_date(): void
    {
        $this->completed('Stary projekt', '2025-11-10');
        $this->completed('Nowy projekt', '2026-05-20');
        $this->completed('Bez daty', null);

        $this->get(route('projects.archive'))->assertOk()
            ->assertSee('Stary projekt')->assertSee('Nowy projekt')->assertSee('Bez daty');

        $this->get(route('projects.archive', ['przed' => '2026-03-01']))->assertOk()
            ->assertSee('zrealizowane przed 1 marca 2026')
            ->assertSee('Stary projekt')
            ->assertSee('Bez daty')
            ->assertDontSee('Nowy projekt');

        $this->get(route('projects.archive', ['po' => '2026-03-01']))->assertOk()
            ->assertSee('zrealizowane od 1 marca 2026')
            ->assertSee('Nowy projekt')
            ->assertDontSee('Stary projekt')
            ->assertDontSee('Bez daty');

        // Zły format daty = brak filtra, bez błędu.
        $this->get(route('projects.archive', ['przed' => 'nie-data']))->assertOk()->assertSee('Nowy projekt');
    }

    public function test_projects_mega_menu_shows_custom_links_column(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        Project::create(['title' => 'Audyt A', 'slug' => 'audyt-a', 'is_published' => true, 'category_id' => $category->id]);

        $menu = NavItem::create(['label' => 'Projekty', 'type' => 'projects', 'location' => 'main', 'is_active' => true, 'is_mega' => true, 'order' => 1, 'mega_extra_title' => 'Nasze działania']);
        // Adres „przed 01.03" ustawia redakcja (tu: inna strona), „po 01.03" — archiwum z filtrem.
        NavItem::create(['label' => 'Projekty zrealizowane przed 01.03.2026', 'type' => 'link', 'url' => 'https://stare.example.org/projekty', 'location' => 'main', 'is_active' => true, 'parent_id' => $menu->id, 'order' => 1]);
        NavItem::create(['label' => 'Projekty archiwalne po 01.03.2026', 'type' => 'link', 'url' => '/projekty/archiwum?po=2026-03-01', 'location' => 'main', 'is_active' => true, 'parent_id' => $menu->id, 'order' => 2]);

        $html = $this->get('/')->assertOk()
            ->assertSee('Nasze działania')
            ->assertSee('Projekty zrealizowane przed 01.03.2026')
            ->assertSee('Projekty archiwalne po 01.03.2026')
            ->assertSee('https://stare.example.org/projekty', false)
            ->assertSee('/projekty/archiwum?po=2026-03-01', false)
            ->getContent();

        // Kolumna ma nagłówek powiązany z listą (aria-labelledby) — czytelne dla czytników ekranu.
        $this->assertStringContainsString('aria-labelledby="mega-done-' . $menu->id . '"', $html);
        // Te same linki w zwykłym rozwijanym menu (mobile / bez mega).
        $this->assertStringContainsString('aria-labelledby="projects-done-' . $menu->id . '"', $html);
    }
}
