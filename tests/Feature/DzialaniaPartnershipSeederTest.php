<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\DzialaniaPartnershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DzialaniaPartnershipSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tworzy_dzialanie_partnerskie_i_strona_sie_wyswietla(): void
    {
        $this->seed(DzialaniaPartnershipSeeder::class);
        $this->seed(DzialaniaPartnershipSeeder::class);

        $project = Project::where('slug', 'dostepnosc-w-praktyce')->sole();
        $this->assertSame('dla-instytucji', $project->category->slug);
        $this->assertCount(3, $project->custom_sections);
        $this->assertFalse($project->is_paid);

        $this->get(route('projects.show', $project))->assertOk()
            ->assertSee('Dostępność w praktyce — wdrożenia i konsulting')
            ->assertSee('Współpraca tylko w ramach partnerstw')
            ->assertSee('Tajemniczy klient')
            ->assertSee('Bez niespodzianek')
            ->assertSee('Organizacje pozarządowe');
    }
}
