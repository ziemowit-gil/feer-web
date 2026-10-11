<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Database\Seeders\ProjectCategoriesByFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCategoriesByFormSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_przypisuje_dzialania_do_nowych_kategorii_i_jest_idempotentny(): void
    {
        $old = Category::create(['name' => 'Dla NGO', 'slug' => 'dla-ngo']);
        $make = fn (string $title) => Project::create(['title' => $title, 'slug' => \Illuminate\Support\Str::slug($title), 'category_id' => $old->id, 'content' => '<p>x</p>', 'is_published' => true]);
        $akademia = $make('Akademia Dostępności w NGO');
        $social = $make('Edukacja w social media');
        $materialy = $make('Materiały edukacyjne');
        $it = $make('Wsparcie technologiczne dla NGO');
        $komputer = $make('Szkolenia z obsługi komputera i sprzętu wspomagającego');
        $inne = $make('Festyn rodzinny');

        $this->seed(ProjectCategoriesByFormSeeder::class);
        $this->seed(ProjectCategoriesByFormSeeder::class);

        $slugOf = fn (Project $p) => $p->fresh()->category->slug;
        $this->assertSame('szkolenia-i-warsztaty', $slugOf($akademia));
        $this->assertSame('szkolenia-i-warsztaty', $slugOf($komputer));
        $this->assertSame('edukacja-i-materialy', $slugOf($social));
        $this->assertSame('edukacja-i-materialy', $slugOf($materialy));
        $this->assertSame('wsparcie-dla-organizacji', $slugOf($it));
        $this->assertSame('dla-ngo', $slugOf($inne), 'niepasujące działanie zostaje');
        $this->assertSame(1, Category::where('slug', 'szkolenia-i-warsztaty')->count());
    }
}
