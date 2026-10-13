<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\SzkolenieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SzkolenieSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tworzy_odplatne_szkolenie_z_cennikiem_i_podstronami(): void
    {
        $this->seed(SzkolenieSeeder::class);
        $this->seed(SzkolenieSeeder::class);

        $project = Project::where('slug', 'szkolenia-informatyka-programowanie-tyfloinformatyka')->sole();
        $this->assertTrue($project->isPaidOffer());
        $this->assertSame('szkolenia-i-warsztaty', $project->category->slug);
        $this->assertSame(['Zakres wsparcia i program zajęć', 'Prowadzący'], $project->pageTree(true)->pluck('title')->all());
        $this->assertSame(2, \App\Models\Page::where('project_id', $project->id)->count());

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~<p class="font-bold leading-snug text-ink text-base">Szkolenia z informatyki</p>~', $html);
        foreach (['za opłatą', 'Cennik', 'Szkolenie zdalne', '85 zł', 'do ustalenia', 'Python', 'VoiceOver', 'Ziemowit Gil', 'aria-label="Sekcje działania"'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
