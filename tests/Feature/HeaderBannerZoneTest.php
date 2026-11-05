<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerZone;
use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strefy banerów: „Nagłówek” pod nagłówkiem, „Pod okruszkami” pod ścieżką nawigacji i „Stopka” w stopce. */
class HeaderBannerZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_zone_banner_renders_under_header_and_footer_zone_in_footer(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $top = Banner::create(['name' => 'Góra', 'type' => 'image', 'image_path' => 'banery/g.webp', 'image_alt' => 'Baner pod nagłówkiem', 'link_url' => '/g', 'link_target' => '_self', 'is_active' => true]);
        $bottom = Banner::create(['name' => 'Dół', 'type' => 'image', 'image_path' => 'banery/d.webp', 'image_alt' => 'Baner w stopce', 'link_url' => '/d', 'link_target' => '_self', 'is_active' => true]);
        BannerZone::where('slug', 'header')->firstOrFail()->banners()->attach($top->id, ['priority' => 1]);
        $crumbs = Banner::create(['name' => 'Okruszki', 'type' => 'image', 'image_path' => 'banery/o.webp', 'image_alt' => 'Baner pod okruszkami', 'link_url' => '/o', 'link_target' => '_self', 'is_active' => true]);
        BannerZone::where('slug', 'breadcrumbs')->firstOrFail()->banners()->attach($crumbs->id, ['priority' => 1]);
        BannerZone::where('slug', 'footer')->firstOrFail()->banners()->attach($bottom->id, ['priority' => 1]);

        $cat = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);
        $p = Project::create(['category_id' => $cat->id, 'title' => 'Akademia', 'slug' => 'akademia', 'content' => '<p>Opis</p>', 'is_published' => true]);

        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();
        $header = strpos($html, 'Baner pod nagłówkiem');
        $nav = strpos($html, 'aria-label="Ścieżka nawigacyjna"');
        $under = strpos($html, 'Baner pod okruszkami');
        $this->assertNotFalse($under);
        $this->assertTrue($header < $nav && $nav < $under, 'kolejność: nagłówek → okruszki → baner pod okruszkami');
        $this->assertLessThan(strpos($html, 'Baner w stopce'), $under);

        foreach ([route('projects.show', $p), url('/')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('Baner pod nagłówkiem', $html, $url);
            $this->assertStringContainsString('Baner w stopce', $html, $url);
            $this->assertGreaterThan(strpos($html, '<main'), strpos($html, 'Baner pod nagłówkiem'), 'pod nagłówkiem, w <main>');
            $this->assertLessThan(strpos($html, 'Baner w stopce'), strpos($html, 'Baner pod nagłówkiem'));
        }
    }
}
