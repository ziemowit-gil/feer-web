<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona główna pokazuje tylko działania zaznaczone „Pokaż na stronie głównej”. */
class ProjectShowOnHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_marked_projects_on_home(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $cat = \App\Models\Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);
        Project::create(['category_id' => $cat->id, 'title' => 'Na głównej', 'slug' => 'na-glownej', 'is_published' => true, 'show_on_home' => true]);
        Project::create(['category_id' => $cat->id, 'title' => 'Tylko na liście', 'slug' => 'tylko-lista', 'is_published' => true]);

        $this->get('/')->assertOk()->assertSee('Na głównej')->assertDontSee('Tylko na liście');
    }
}
