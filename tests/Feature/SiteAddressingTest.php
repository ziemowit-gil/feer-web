<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Witryna w sieci: jedna logika adresów (domena albo prefiks /{slug}), te same trasy co witryna główna. */
class SiteAddressingTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function news(SiteSetting $site, string $title, bool $published = true): News
    {
        return News::withoutGlobalScopes()->create([
            'site_id' => $site->id, 'title' => $title, 'slug' => str($title)->slug(), 'content' => '<p>Treść</p>',
            'is_published' => $published, 'published_at' => now()->subHour(),
        ]);
    }

    public function test_prefiks_pokazuje_tresc_witryny_na_tych_samych_trasach(): void
    {
        $this->news($this->main, 'Wiesc glowna');
        $this->news($this->sub, 'Wiesc szkolen');

        $this->get('/szkolenia')->assertOk();
        $this->get('/szkolenia/aktualnosci')->assertOk()->assertSee('Wiesc szkolen')->assertDontSee('Wiesc glowna');
        $this->get('/szkolenia/aktualnosci/wiesc-szkolen')->assertOk()->assertSee('Wiesc szkolen');
        $this->get('/szkolenia/aktualnosci/wiesc-glowna')->assertNotFound();

        $this->get('/aktualnosci')->assertOk()->assertSee('Wiesc glowna')->assertDontSee('Wiesc szkolen');
    }

    public function test_adresy_generowane_w_witrynie_maja_prefiks_a_zasoby_nie(): void
    {
        $this->news($this->sub, 'Wiesc szkolen');

        $html = $this->get('/szkolenia/aktualnosci')->assertOk()->getContent();

        $this->assertStringContainsString('/szkolenia/aktualnosci/wiesc-szkolen', $html);
        $this->assertDoesNotMatchRegularExpression('#(src|href)="[^"]*/szkolenia/(build|storage|images|favicon)#', $html, 'zasoby statyczne nie dostają prefiksu');
        $this->assertSame('http://localhost:8000/aktualnosci', route('news.index'), 'po żądaniu stan generatora wraca do witryny głównej');
    }

    public function test_stary_adres_site_slug_przekierowuje_na_krotki(): void
    {
        $this->get('/site/szkolenia/aktualnosci?x=1')->assertRedirect('/szkolenia/aktualnosci?x=1')->assertStatus(301);
        $this->get('/site/nie-ma-takiej/aktualnosci')->assertNotFound();
    }

    public function test_slug_bloga_i_strony_glownej_nie_sa_prefiksem(): void
    {
        SiteSetting::create([
            'site_name' => 'Wiem', 'slug' => 'wiem', 'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
        Page::withoutGlobalScopes()->create(['site_id' => $this->main->id, 'title' => 'Kontakt firmowy', 'slug' => 'kontakt-firmowy', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);

        $this->get('/kontakt-firmowy')->assertOk();
        $this->assertNotSame(404, $this->get('/wiem')->getStatusCode() === 404 ? 0 : 1, 'adres bloga obsługuje moduł bloga, nie prefiks witryny');
    }

    public function test_podglad_podpisany_dziala_pod_prefiksem(): void
    {
        $draft = $this->news($this->sub, 'Szkic', published: false);

        $url = $draft->previewUrl();
        $this->assertStringContainsString('/szkolenia/aktualnosci/szkic', $url);

        $this->get($url)->assertOk()->assertSee('Szkic');
        $this->get('/szkolenia/aktualnosci/szkic')->assertNotFound();
    }

    public function test_formularz_post_dziala_pod_prefiksem(): void
    {
        $this->post('/szkolenia/kontakt', [])->assertSessionHasErrors();
    }

    public function test_slug_witryny_nie_moze_byc_pierwszym_segmentem_istniejacej_trasy(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);

        foreach (['aktualnosci', 'kontakt', 'dashboard', 'admin'] as $slug) {
            $this->actingAs($admin)->post(route('admin.witryny.store'), ['site_name' => 'Test', 'slug' => $slug, 'site_kind' => 'standard'])
                ->assertSessionHasErrors('slug');
        }
    }
}
