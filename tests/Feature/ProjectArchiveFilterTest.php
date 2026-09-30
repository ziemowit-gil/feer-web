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

        $menu = NavItem::create(['label' => 'Projekty', 'type' => 'projects', 'location' => 'main', 'is_active' => true, 'is_mega' => true, 'order' => 1, 'mega_extra_title' => 'Archiwum projektów']);
        // Adres „przed 01.03" ustawia redakcja (tu: inna strona), „po 01.03" — archiwum z filtrem.
        NavItem::create(['label' => 'Projekty zrealizowane przed 01.03.2026', 'type' => 'link', 'url' => 'https://stare.example.org/projekty', 'location' => 'main', 'is_active' => true, 'parent_id' => $menu->id, 'order' => 1]);
        NavItem::create(['label' => 'Projekty archiwalne po 01.03.2026', 'type' => 'link', 'url' => '/projekty/archiwum?po=2026-03-01', 'location' => 'main', 'is_active' => true, 'parent_id' => $menu->id, 'order' => 2]);

        $html = $this->get('/')->assertOk()
            ->assertSee('Archiwum projektów')
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

    public function test_mega_side_card_shows_custom_links_instead_of_default_buttons_and_no_item_label(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        Project::create(['title' => 'Audyt A', 'slug' => 'audyt-a', 'is_published' => true, 'category_id' => $category->id]);

        NavItem::create([
            'label' => 'Nasze działania', 'type' => 'projects', 'location' => 'main', 'is_active' => true, 'is_mega' => true, 'order' => 1,
            'mega_side_title' => 'Działania na starej stronie',
            'mega_side_links' => [
                ['label' => 'Działania 2015–2025', 'url' => 'https://stara.example.org/dzialania', 'style' => 'button', 'new_tab' => true],
                ['label' => 'Kalendarz szkoleń', 'url' => '/wydarzenia', 'style' => 'link', 'new_tab' => false],
            ],
        ]);

        $html = $this->get('/')->assertOk()
            ->assertSee('Działania na starej stronie')
            ->assertSee('Działania 2015–2025')
            ->assertSee('https://stara.example.org/dzialania', false)
            ->getContent();

        // W panelu mega menu własne linki zastępują domyślny przycisk (zwykłe menu mobilne ma swój).
        preg_match('/nav-mega-panel.*?<\/li>/s', $html, $panel);
        $this->assertStringNotContainsString('Wszystkie projekty', $panel[0] ?? '');
        $this->assertStringNotContainsString('>Nasze działania<', $panel[0] ?? '');

        // Link w nowej karcie ma rel=noopener (dopisek dla czytników dodaje globalny skrypt);
        // link wewnętrzny nie ma target=_blank.
        $this->assertMatchesRegularExpression('/href="https:\/\/stara\.example\.org\/dzialania"[^>]*target="_blank"[^>]*rel="noopener"/', $html);
        $this->assertDoesNotMatchRegularExpression('/href="\/wydarzenia"[^>]*target="_blank"/', $html);
    }

    public function test_side_link_urls_reject_executable_schemes(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.pozycje-menu.store'), [
            'label' => 'Projekty', 'type' => 'projects', 'location' => 'main', 'is_active' => '1', 'is_mega' => '1',
            'mega_side_links' => [['label' => 'Zły', 'url' => 'javascript:alert(1)', 'style' => 'button']],
        ])->assertSessionHasErrors('mega_side_links.0.url');

        $this->actingAs($admin)->post(route('admin.pozycje-menu.store'), [
            'label' => 'Projekty', 'type' => 'projects', 'location' => 'main', 'is_active' => '1', 'is_mega' => '1',
            'mega_side_title' => 'Stara strona',
            'mega_side_links' => [
                ['label' => 'Działania', 'url' => 'https://stara.example.org', 'style' => 'button', 'new_tab' => '1'],
                ['label' => '', 'url' => '', 'style' => 'link'], // pusty wiersz jest pomijany
            ],
        ])->assertSessionHasNoErrors();

        $item = NavItem::where('type', 'projects')->first();
        $this->assertSame('Stara strona', $item->mega_side_title);
        $this->assertCount(1, $item->megaSideLinks());
        $this->assertTrue($item->megaSideLinks()[0]['new_tab']);
    }

    public function test_side_links_also_appear_in_the_plain_dropdown_for_mobile(): void
    {
        NavItem::create([
            'label' => 'Menu', 'type' => 'dropdown', 'location' => 'main', 'is_active' => true, 'is_mega' => true, 'order' => 1,
            'mega_side_links' => [['label' => 'Stara strona', 'url' => 'https://stara.example.org', 'style' => 'link', 'new_tab' => true]],
        ]);
        $parent = NavItem::where('label', 'Menu')->first();
        NavItem::create(['label' => 'Podpozycja', 'type' => 'link', 'url' => '/x', 'location' => 'main', 'is_active' => true, 'parent_id' => $parent->id, 'order' => 1]);

        // Ten sam odnośnik jest w mega panelu (desktop) i w zwykłym rozwijanym menu (mobile).
        $html = $this->get('/')->assertOk()->assertSee('Stara strona')->getContent();
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'https://stara.example.org'));
    }
}
