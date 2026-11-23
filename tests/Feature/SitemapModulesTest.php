<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\PageTypes\PageTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * sitemap.xml podaje tylko adresy, które odpowiadają treścią: bez stron typu zależnego od wyłączonego
 * modułu (Kalendarz warsztatów przy wyłączonych Warsztatach), bez /kontakt, gdy przekierowuje na stronę
 * „Kontakt”, i bez stron wyłączonych lub zarchiwizowanych — tak jak strona „Mapa strony”.
 */
class SitemapModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PageTypeRegistry::reset();
    }

    protected function tearDown(): void
    {
        PageTypeRegistry::reset();
        $this->forgetSite();
        parent::tearDown();
    }

    private function forgetSite(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function site(array $attributes): SiteSetting
    {
        $this->forgetSite();
        SiteSetting::current()->forceFill($attributes)->save();
        $this->forgetSite();
        PageTypeRegistry::reset();

        return SiteSetting::current();
    }

    private function page(string $slug, array $extra = []): Page
    {
        return Page::create($extra + ['title' => ucfirst($slug), 'slug' => $slug, 'is_published' => true, 'content' => '<p>Treść</p>']);
    }

    private function loc(string $path): string
    {
        return '<loc>'.url($path).'</loc>';
    }

    public function test_kalendarz_warsztatow_znika_z_mapy_przy_wylaczonym_module(): void
    {
        $this->site(['site_template' => 'kreatywni', 'disabled_modules' => []]);
        $this->page('kalendarz-warsztatow', ['title' => 'Kalendarz warsztatów', 'type' => 'kreatywni_workshops', 'content' => '']);

        $this->get('/sitemap.xml')->assertOk()->assertSee($this->loc('/kalendarz-warsztatow'), false);
        $this->get('/mapa-strony')->assertOk()->assertSee('Kalendarz warsztatów');

        $this->site(['disabled_modules' => ['workshops']]);

        $this->get('/kalendarz-warsztatow')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($this->loc('/kalendarz-warsztatow'), false);
        $this->get('/mapa-strony')->assertOk()->assertDontSee('Kalendarz warsztatów');
    }

    public function test_kontakt_tylko_gdy_nie_przekierowuje_na_strone_kontaktu(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee($this->loc('/kontakt'), false);

        $this->page('kontakt-2', ['title' => 'Kontakt', 'type' => 'contact']);

        $this->get('/kontakt')->assertRedirect(url('/kontakt-2'));
        $this->get('/sitemap.xml')->assertOk()
            ->assertDontSee($this->loc('/kontakt'), false)
            ->assertSee($this->loc('/kontakt-2'), false);
    }

    public function test_strony_wylaczone_i_zarchiwizowane_poza_mapa(): void
    {
        $this->page('o-nas');
        $this->page('stara-strona', ['is_archived' => true]);
        $off = $this->page('w-przygotowaniu', ['is_disabled' => true]);
        $this->page('podstrona-wylaczonej', ['parent_id' => $off->id]);
        $this->page('ukryta', ['hide_in_sitemap' => true]);
        $this->page('pozniej', ['publish_at' => now()->addDay()]);

        $xml = $this->get('/sitemap.xml')->assertOk();

        $xml->assertSee($this->loc('/o-nas'), false);
        // Sam slug, nie pełny adres — podstrona może mieć adres zagnieżdżony pod rodzicem.
        foreach (['stara-strona', 'w-przygotowaniu', 'podstrona-wylaczonej', 'ukryta', 'pozniej'] as $slug) {
            $xml->assertDontSee($slug, false);
        }
    }
}
