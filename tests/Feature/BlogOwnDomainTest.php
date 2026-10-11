<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;

/** Blog Wiem FEER na własnej domenie witryny „wiem": / = lista wpisów, /{slug} = wpis (gdy nie ma strony). */
class BlogOwnDomainTest extends BlogTestCase
{
    private const HOST = 'wiem.feer.test';

    private function blogSite(?string $domain = self::HOST): SiteSetting
    {
        $main = SiteSetting::current();
        $site = SiteSetting::create([
            'site_name' => 'Wiem FEER', 'slug' => config('blog.site_slug', 'wiem'),
            'domain' => $domain, 'parent_site_id' => $main->id,
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return $site;
    }

    public function test_strona_glowna_wlasnej_domeny_to_lista_wpisow(): void
    {
        $this->blogSite();
        $this->article('Wpis na domenie');

        $this->get('http://' . self::HOST . '/')->assertOk()
            ->assertSee('aria-label="Kolumna boczna Wiem FEER"', false)
            ->assertSee('Wpis na domenie')
            ->assertSee('<title>Wiem FEER</title>', false);
    }

    public function test_krotki_adres_wpisu_na_wlasnej_domenie(): void
    {
        $site = $this->blogSite();
        $this->article('Mój wpis');
        $this->article('Szkic domeny', ['is_published' => false]);

        $this->get('http://' . self::HOST . '/moj-wpis')->assertOk()
            ->assertSee('<h1 id="blog-article-title"', false)
            ->assertSee('<title>Mój wpis — Wiem FEER</title>', false);
        // Pełny adres /wiem/… nadal działa na tej domenie.
        $this->get('http://' . self::HOST . '/wiem/moj-wpis')->assertOk()->assertSee('<title>Mój wpis — Wiem FEER</title>', false);
        // Niewidoczny wpis i nieistniejący slug — 404.
        $this->get('http://' . self::HOST . '/szkic-domeny')->assertNotFound();
        $this->get('http://' . self::HOST . '/nie-ma-takiego')->assertNotFound();
    }

    public function test_strona_witryny_ma_pierwszenstwo_przed_wpisem(): void
    {
        $site = $this->blogSite();
        $this->article('O nas', ['slug' => 'o-nas']);
        Page::create(['title' => 'Strona O nas', 'slug' => 'o-nas', 'content' => '<p>Treść strony</p>', 'is_published' => true, 'site_id' => $site->id]);

        $this->get('http://' . self::HOST . '/o-nas')->assertOk()
            ->assertSee('Treść strony')
            ->assertDontSee('<h1 id="blog-article-title"', false);
    }

    public function test_domena_glowna_bez_zmian(): void
    {
        $this->blogSite();
        $this->article('Mój wpis');

        $this->get('/')->assertOk()->assertDontSee('aria-label="Kolumna boczna Wiem FEER"', false);
        $this->get('/moj-wpis')->assertNotFound();
        $this->get('/wiem/moj-wpis')->assertOk();
    }

    public function test_witryna_bloga_bez_domeny_nie_zmienia_strony_glownej(): void
    {
        $this->blogSite(null);
        $this->article('Mój wpis');

        $this->get('http://' . self::HOST . '/')->assertOk()->assertDontSee('aria-label="Kolumna boczna Wiem FEER"', false);
        $this->get('http://' . self::HOST . '/moj-wpis')->assertNotFound();
    }

    public function test_licznik_wyswietlen_dziala_na_krotkim_adresie(): void
    {
        $this->blogSite();
        $article = $this->article('Mój wpis');

        $this->get('http://' . self::HOST . '/moj-wpis')->assertOk();
        $this->get('http://' . self::HOST . '/moj-wpis')->assertOk();

        $this->assertSame(1, $article->fresh()->views);
    }
}
