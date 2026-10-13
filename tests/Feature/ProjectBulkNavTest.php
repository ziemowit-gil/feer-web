<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Masowa zmiana nawigacji po sekcjach na liście działań. */
class ProjectBulkNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_sets_sections_nav(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cat = Category::create(['name' => 'Kat', 'slug' => 'kat']);
        $a = Project::create(['category_id' => $cat->id, 'title' => 'A', 'slug' => 'a-p', 'is_published' => true]);
        $b = Project::create(['category_id' => $cat->id, 'title' => 'B', 'slug' => 'b-p', 'is_published' => true]);

        $this->actingAs($admin)->post(route('admin.projekty.bulk'), ['action' => 'sections_nav', 'sections_nav' => 'sidebar', 'ids' => [$a->id]])->assertRedirect();

        $this->assertSame('sidebar', $a->fresh()->sections_nav);
        $this->assertNull($b->fresh()->sections_nav);
    }
}
