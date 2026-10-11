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
        // Stan po pierwszej wersji seedera: działanie w kategorii „Dla instytucji i organizacji”.
        $old = \App\Models\Category::create(['name' => 'Dla instytucji i organizacji', 'slug' => 'dla-instytucji']);
        $keep = \App\Models\Category::create(['name' => 'Inna pusta', 'slug' => 'inna-pusta']);

        $this->seed(DzialaniaPartnershipSeeder::class);
        $this->seed(DzialaniaPartnershipSeeder::class);

        $this->assertModelMissing($old);
        $this->assertModelExists($keep);

        $project = Project::where('slug', 'dostepnosc-w-praktyce')->sole();
        $this->assertSame('wsparcie-dla-organizacji', $project->category->slug);
        $this->assertSame('sidebar', $project->sectionsNavMode());
        $this->assertCount(1, $project->custom_sections);
        $this->assertSame(['Zakres współpracy', 'Inne formy współpracy', 'Wsparcie dla osób fizycznych', 'Zasady współpracy', 'Dla kogo'], $project->pageTree(true)->pluck('title')->all());
        $this->assertSame(5, \App\Models\Page::where('project_id', $project->id)->count(), 'bez duplikatów po ponownym uruchomieniu');

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Sekcje działania"', $html);
        foreach (['Współpraca tylko w ramach partnerstw', 'Zakres współpracy', 'Zasady współpracy', 'Dla kogo', 'Tajemniczy klient', 'Konsulting w zakresie nowoczesnych technologii w NGO', 'Bez niespodzianek', 'Organizacje pozarządowe', 'Inne formy współpracy', 'partnerstwo w projektach', 'Wsparcie dla osób fizycznych', 'włączenia społecznego osób z niepełnosprawnościami (OzN)', 'W różny sposób wspieramy', 'nie prowadzimy subkont', 'widzimy w nich interes społeczny'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
