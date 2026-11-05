<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerZone;
use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona działania: dowolne banery i strefy z modułu Banery wybrane w formularzu działania. */
class ProjectBannersTest extends TestCase
{
    use RefreshDatabase;

    private function project(?array $banners): Project
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $cat = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);

        return Project::create(['category_id' => $cat->id, 'title' => 'Akademia', 'slug' => 'akademia', 'content' => '<p>Opis</p>', 'is_published' => true, 'banners' => $banners])->fresh();
    }

    public function test_selected_banners_and_zones_are_rendered_and_inactive_are_skipped(): void
    {
        $a = Banner::create(['name' => 'Paszporty', 'type' => 'image', 'image_path' => 'banery/a.webp', 'image_alt' => 'Baner A', 'link_url' => 'https://example.pl', 'link_target' => '_self', 'is_active' => true]);
        $off = Banner::create(['name' => 'Stary', 'type' => 'image', 'image_path' => 'banery/off.webp', 'image_alt' => 'Baner wyłączony', 'link_url' => '/x', 'is_active' => false]);
        $inZone = Banner::create(['name' => 'Strefowy', 'type' => 'image', 'image_path' => 'banery/z.webp', 'image_alt' => 'Baner ze strefy', 'link_url' => '/z', 'is_active' => true]);
        $zone = BannerZone::create(['slug' => 'dla-dzialan', 'label' => 'Dla działań', 'max_concurrent' => 3]);
        $zone->banners()->attach($inZone->id, ['priority' => 1]);

        $p = $this->project(['banners' => [$a->id, $off->id], 'zones' => ['dla-dzialan']]);
        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();

        $this->assertStringContainsString('Baner A', $html);
        $this->assertStringContainsString('Baner ze strefy', $html);
        $this->assertStringNotContainsString('Baner wyłączony', $html);
    }

    public function test_no_banners_means_no_region(): void
    {
        $p = $this->project(null);
        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();
        $this->assertStringNotContainsString('proj-banners', $html);
    }

    public function test_banners_render_in_feer_template(): void
    {
        $a = Banner::create(['name' => 'Feer', 'type' => 'image', 'image_path' => 'banery/f.webp', 'image_alt' => 'Baner w szablonie feer', 'link_url' => 'https://example.pl', 'link_target' => '_self', 'is_active' => true]);
        $p = $this->project(['banners' => [$a->id], 'zones' => []]);
        SiteSetting::query()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();
        $this->assertStringContainsString('Baner w szablonie feer', $html);
    }
}
