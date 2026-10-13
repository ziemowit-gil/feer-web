<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProjectImageHeroTest extends TestCase
{
    use RefreshDatabase;

    private function project(bool $hero): Project
    {
        $category = Category::create(['name' => 'Dla każdego', 'slug' => 'dla-kazdego']);
        $project = Project::create(['title' => 'Edukacja w social media', 'slug' => 'edukacja', 'category_id' => $category->id,
            'content' => '<p>Opis</p>', 'is_published' => true, 'image_alt' => 'Telefon w dłoni', 'image_hero' => $hero]);
        $project->addMedia(UploadedFile::fake()->image('foto.jpg', 1200, 800))->toMediaCollection('image');

        return $project->fresh();
    }

    public function test_zdjecie_jako_hero_to_szeroki_baner_nad_tytulem(): void
    {
        $p = $this->project(true);
        $p->update(['hero_style' => 'band']);
        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();

        // Zdjęcie jako niski pasek na całą szerokość, tytuł pod nim (bez karty nachodzącej na zdjęcie).
        $this->assertStringContainsString('h-40 w-full object-cover sm:h-48 lg:h-56', $html);
        $this->assertStringNotContainsString('relative -mt-16 sm:-mt-24', $html);
        $this->assertStringNotContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'alt="Telefon w dłoni"'));
    }

    public function test_bez_hero_zdjecie_obok_tytulu(): void
    {
        $html = $this->get(route('projects.show', $this->project(false)))->assertOk()->getContent();

        $this->assertStringContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertStringNotContainsString('lg:h-56', $html);
    }

    public function test_przy_hero_naglowek_nad_menu_i_trescia(): void
    {
        $project = $this->project(true);
        $project->update(['sections_nav' => 'sidebar', 'hero_style' => 'band']);
        \App\Models\Page::create(['title' => 'Zakres', 'slug' => 'zakres', 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>Zakres</p>']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        // Kolejność: zdjęcie-pasek → nagłówek (h1) → menu sekcji obok treści.
        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'h-40 w-full object-cover'));
        $this->assertLessThan(strpos($html, 'aria-label="Sekcje działania"'), strpos($html, '<h1'));
    }

    public function test_klasyczny_uklad_karta_na_zdjeciu_do_wyboru(): void
    {
        $project = $this->project(true);
        $project->update(['sections_nav' => 'sidebar']); // bez wyboru — domyślnie klasyczny
        \App\Models\Page::create(['title' => 'Zakres', 'slug' => 'zakres', 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>Zakres</p>']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        $this->assertStringContainsString('lg:h-[24rem]', $html);
        $this->assertStringContainsString('mb-8 rounded-md bg-white p-6 sm:p-8', $html);
        // Nagłówek i menu sekcji w siatce nachodzącej na zdjęcie.
        $grid = strpos($html, 'relative -mt-16 sm:-mt-24');
        $this->assertNotFalse($grid);
        $this->assertGreaterThan($grid, strpos($html, '<h1'));
    }
}
