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
        $html = $this->get(route('projects.show', $this->project(true)))->assertOk()->getContent();

        $this->assertStringContainsString('lg:h-[24rem]', $html);
        $this->assertStringContainsString('mb-8 rounded-md bg-white p-6 sm:p-8', $html);
        $this->assertStringContainsString('relative -mt-16 sm:-mt-24', $html);
        $this->assertStringNotContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'alt="Telefon w dłoni"'));
    }

    public function test_bez_hero_zdjecie_obok_tytulu(): void
    {
        $html = $this->get(route('projects.show', $this->project(false)))->assertOk()->getContent();

        $this->assertStringContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertStringNotContainsString('lg:h-[24rem]', $html);
    }

    public function test_przy_hero_menu_boczne_zaczyna_sie_obok_naglowka(): void
    {
        $project = $this->project(true);
        $project->update(['sections_nav' => 'sidebar']);
        \App\Models\Page::create(['title' => 'Zakres', 'slug' => 'zakres', 'type' => 'standard', 'is_published' => true, 'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>Zakres</p>']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        // Nagłówek (h1) i menu sekcji w tej samej siatce, nachodzącej na zdjęcie.
        $grid = strpos($html, 'relative -mt-16 sm:-mt-24');
        $this->assertNotFalse($grid);
        $this->assertGreaterThan($grid, strpos($html, '<h1'));
        $this->assertGreaterThan($grid, strpos($html, 'aria-label="Sekcje działania"'));
    }
}
