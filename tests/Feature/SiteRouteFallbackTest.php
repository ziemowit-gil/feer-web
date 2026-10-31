<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** site_route() w witrynie z prefiksem ścieżki: trasy bez wariantu „site.” (np. materiały) nie wywalają strony. */
class SiteRouteFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function inSubSite(string $slug = 'szkolenia'): void
    {
        $request = Request::create('/'.$slug);
        $request->attributes->set('site_path_prefixed', true);
        $request->attributes->set('site_slug', $slug);
        app()->instance('request', $request);
    }

    public function test_trasa_bez_wariantu_site_wraca_do_zwyklej_trasy_zamiast_wyjatku(): void
    {
        $this->inSubSite();

        $this->assertSame(route('materials.index'), site_route('materials.index'));
    }

    public function test_trasa_z_wariantem_site_zostaje_w_witrynie(): void
    {
        $this->inSubSite();

        $this->assertSame(route('site.news.index', ['siteSlug' => 'szkolenia']), site_route('news.index'));
        $this->assertSame(url('/szkolenia'), site_route('home'));
    }

    public function test_poza_witryna_z_prefiksem_dziala_jak_zwykle_route(): void
    {
        app()->instance('request', Request::create('/'));

        $this->assertSame(route('materials.index'), site_route('materials.index'));
        $this->assertSame(route('news.index'), site_route('news.index'));
    }
}
