<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsIndexRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_lista_projektow_w_panelu_i_widok_projektu_sie_renderuja(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        $project = Project::create(['title' => 'Audyt A', 'slug' => 'audyt-a', 'excerpt' => 'Krótki opis', 'is_published' => true, 'is_completed' => true, 'category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.projekty.index'))->assertOk()
            ->assertSee('Audyt A')->assertSee('Krótki opis')->assertSee('Zrealizowany')
            ->assertSee('Edytuj: Audyt A');

        $this->get(route('projects.show', $project))->assertOk()
            ->assertSee('Audyt A')->assertDontSee('Projekt zrealizowany')->assertSee('Krótki opis');
    }

    public function test_formularz_edycji_projektu_sie_renderuje(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        $project = Project::create(['title' => 'Audyt A', 'slug' => 'audyt-a', 'is_published' => true, 'category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.projekty.edit', $project))->assertOk()
            ->assertSee('data-ftab-btn="sekcje"', false)->assertSee('Anuluj');
    }

    public function test_publiczna_lista_projektow_ma_skroty_do_kategorii(): void
    {
        foreach (['Audyty' => 'audyty', 'Szkolenia' => 'szkolenia'] as $name => $slug) {
            $c = Category::create(['name' => $name, 'slug' => $slug]);
            Project::create(['title' => 'Projekt '.$name, 'slug' => 'p-'.$slug, 'is_published' => true, 'category_id' => $c->id]);
        }

        $this->get(route('projects.index'))->assertOk()
            ->assertSee('Przejdź do kategorii')->assertSee('Projekt Audyty')->assertSee('Projekt Szkolenia');
    }

    public function test_zakladki_na_stronie_projektu_maja_styl_jak_w_kontakcie(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        $project = Project::create([
            'title' => 'Audyt A', 'slug' => 'audyt-a', 'is_published' => true, 'category_id' => $category->id, 'sections_as_tabs' => true,
            'custom_sections' => [['title' => 'Harmonogram', 'content' => '<p>Terminy</p>'], ['title' => 'Cennik', 'content' => '<p>Ceny</p>']],
        ]);
        foreach (['Materiały', 'Nagrania'] as $i => $title) {
            \App\Models\Page::create(['title' => $title, 'slug' => 'p'.$i, 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>'.$title.' treść</p>']);
        }

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        // Jeden wspólny pasek zakładek (jak w kontakcie): „O projekcie”, sekcje własne i podstrony.
        $this->assertSame(1, substr_count($html, 'role="tablist"'));
        foreach (['tab-opis', 'tab-sekcja-0', 'tab-sekcja-1', 'tab-podstrona-0', 'tab-podstrona-1'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
        $this->assertStringContainsString('id="panel-podstrona-1"', $html);
        $this->assertStringContainsString('Nagrania treść', $html);
        $this->assertStringContainsString('bg-brand', $html);
    }

    public function test_tytul_projektu_jest_poza_panelami_zakladek_a_bez_kontaktu_nie_ma_panelu_bocznego(): void
    {
        $category = Category::create(['name' => 'Audyty', 'slug' => 'audyty']);
        $project = Project::create([
            'title' => 'Audyt A', 'slug' => 'audyt-a', 'is_published' => true, 'is_completed' => true, 'category_id' => $category->id,
            'custom_sections' => [['title' => 'Cennik', 'content' => '<p>Ceny</p>']], 'sections_as_tabs' => true, 'content' => '<p>Opis</p>',
        ]);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        $h1 = strpos($html, '<h1');
        $panel = strpos($html, 'id="panel-opis"');
        $this->assertNotFalse($h1);
        $this->assertNotFalse($panel);
        $this->assertLessThan($panel, $h1, 'Tytuł (hero) musi być przed panelem zakładki „O projekcie”, nie wewnątrz niego.');
        $this->assertStringNotContainsString('aria-label="Informacje o projekcie"', $html);
        $this->assertStringNotContainsString('Kategoria</dt>', $html);
    }
}
