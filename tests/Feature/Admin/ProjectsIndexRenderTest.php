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
}
