<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectContentTrimTest extends TestCase
{
    use RefreshDatabase;

    public function test_puste_akapity_na_koncu_opisu_nie_robia_dziury(): void
    {
        $category = Category::create(['name' => 'Wsparcie', 'slug' => 'wsparcie']);
        $project = Project::create(['title' => 'Wsparcie IT', 'slug' => 'wsparcie-it', 'category_id' => $category->id, 'is_published' => true,
            'content' => '<p>Pierwszy.</p><p>&nbsp;</p><p>Drugi.</p><p>&nbsp;</p><p><br></p>', 'why' => 'Bo tak.']);

        $html = $this->get(route('projects.show', $project))->assertOk()->getContent();

        $this->assertStringContainsString('<p>Pierwszy.</p><p>&nbsp;</p><p>Drugi.</p>', $html, 'puste akapity w środku zostają');
        $this->assertStringNotContainsString('<p>Drugi.</p><p>&nbsp;</p>', $html);
    }
}
