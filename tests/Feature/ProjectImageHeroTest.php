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

        $this->assertStringContainsString('sm:aspect-[21/8]', $html);
        $this->assertStringNotContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'alt="Telefon w dłoni"'));
    }

    public function test_bez_hero_zdjecie_obok_tytulu(): void
    {
        $html = $this->get(route('projects.show', $this->project(false)))->assertOk()->getContent();

        $this->assertStringContainsString('aspect-[4/3] w-full rounded-md object-cover', $html);
        $this->assertStringNotContainsString('sm:aspect-[21/8]', $html);
    }
}
