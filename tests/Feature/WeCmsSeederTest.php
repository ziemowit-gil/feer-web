<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\WeCmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeCmsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tworzy_zapowiedz_dzialania_wecms(): void
    {
        $this->seed(WeCmsSeeder::class);
        $this->seed(WeCmsSeeder::class);

        $project = Project::where('slug', 'wecms')->sole();
        $this->assertSame('wsparcie-dla-organizacji', $project->category->slug);
        $this->assertFalse($project->is_paid);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();
        foreach (['weCMS — system do stron organizacji społecznych', 'mieliśmy dość WordPressa', 'czysty, płaski design', 'prawie zerowe koszty', 'Szczegóły już wkrótce'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
