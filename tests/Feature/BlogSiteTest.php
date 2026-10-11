<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\SiteSetting;

class BlogSiteTest extends BlogTestCase
{
    public function test_adresy_wiem_i_przekierowania_ze_starych(): void
    {
        $this->article('Mój wpis');

        $this->get('/wiem')->assertOk()->assertSee('Mój wpis');
        $this->get('/wiem/moj-wpis')->assertOk()->assertSee('<h1 id="blog-article-title"', false);
        $this->assertSame(url('/wiem/moj-wpis'), route('blog.show', 'moj-wpis'));
        $this->assertSame(url('/wiem/rss.xml'), route('blog.feed'));

        $this->get('/blog')->assertStatus(301)->assertRedirect(url('/wiem'));
        $this->get('/blog/moj-wpis?x=1')->assertStatus(301)->assertRedirect(url('/wiem/moj-wpis') . '?x=1');
        $this->get('/wiem-feer')->assertStatus(301)->assertRedirect(url('/wiem'));
        $this->get('/wiem-feer/moj-wpis?kategoria=a&page=2')->assertStatus(301)->assertRedirect(url('/wiem/moj-wpis') . '?kategoria=a&page=2');
        $this->get('/wiem-feer/autor/anna')->assertStatus(301)->assertRedirect(url('/wiem/autor/anna'));
    }

    public function test_bez_witryny_wiem_blog_ma_oprawe_witryny_glownej(): void
    {
        SiteSetting::current()->update(['site_name' => 'Fundacja Główna']);
        $this->article('Wpis');

        $this->get('/wiem')->assertOk()->assertSee('<title>Wiem FEER — Fundacja Główna</title>', false);
    }

    public function test_z_witryna_wiem_blog_ma_jej_oprawe(): void
    {
        SiteSetting::current()->update(['site_name' => 'Fundacja Główna']);
        $this->artisan('blog:witryna')->assertSuccessful();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->article('Wpis');

        $this->get('/wiem')->assertOk()
            ->assertSee('<title>Wiem FEER</title>', false)
            ->assertSee('<meta property="og:site_name" content="Wiem FEER">', false)
            ->assertDontSee('<title>Wiem FEER — Fundacja Główna</title>', false);

        $this->get('/wiem/wpis')->assertOk()->assertSee('<title>Wpis — Wiem FEER</title>', false);
    }

    public function test_komenda_blog_witryna_jest_idempotentna(): void
    {
        $main = SiteSetting::current();
        $main->update(['brand_color' => '#1d1d1a']);

        $this->artisan('blog:witryna')->assertSuccessful();
        $this->artisan('blog:witryna')->assertSuccessful();

        $sites = SiteSetting::where('slug', 'wiem')->get();
        $this->assertCount(1, $sites);
        $site = $sites->first();
        $this->assertSame('Wiem FEER', $site->site_name);
        $this->assertSame($main->id, $site->parent_site_id);
        $this->assertSame('#1d1d1a', $site->brand_color);

        $items = NavItem::where('site_id', $site->id)->get();
        $this->assertCount(8, $items);
        $this->assertTrue($items->contains(fn ($i) => $i->url === '/wiem' && $i->location === 'main'));
        $this->assertTrue($items->contains(fn ($i) => $i->url === '/' && $i->label === 'Fundacja FEER'));
    }
}
