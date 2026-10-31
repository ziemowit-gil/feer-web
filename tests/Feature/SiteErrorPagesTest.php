<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Strony błędów (404, 403, 429, 500, 503, błędy panelu) pokazują się w oprawie witryny, w której wystąpił błąd. */
class SiteErrorPagesTest extends TestCase
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
        $this->main->forceFill(['site_name' => 'Glowna FEER'])->save();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia XYZ', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => '#aa0000', 'site_template' => $this->main->site_template,
        ]);
        config(['app.debug' => false]);
        Route::get('/zz/403', fn () => abort(403))->middleware('web');
        Route::get('/zz/429', fn () => abort(429))->middleware('web');
        Route::get('/zz/500', fn () => throw new \RuntimeException('x'))->middleware('web');
    }

    public function test_bledy_w_obszarze_podwitryny_maja_jej_oprawe_i_linki_z_prefiksem(): void
    {
        foreach ([403, 429, 500] as $code) {
            $response = $this->get('http://localhost/szkolenia/zz/'.$code)->assertStatus($code);
            $response->assertSee('Szkolenia XYZ')->assertDontSee('Glowna FEER');
            $this->assertStringContainsString('href="http://localhost/szkolenia"', $response->getContent(), "link „Strona główna” zostaje w witrynie ({$code})");
        }

        $this->get('http://localhost/szkolenia/nie-ma-takiej')->assertNotFound()->assertSee('Szkolenia XYZ');
        $this->get('http://szkolenia.example.test/zz/403')->assertForbidden()->assertSee('Szkolenia XYZ');
        $this->get('http://localhost/zz/403')->assertForbidden()->assertDontSee('Szkolenia XYZ');
    }

    public function test_kod_bez_wlasnego_widoku_nie_ma_domyslnej_strony_frameworka(): void
    {
        $this->get('http://localhost/szkolenia/zz/429')->assertStatus(429)->assertSee('Zbyt wiele żądań')->assertDontSee('Too Many Requests');
    }

    public function test_przerwa_techniczna_dotyczy_tylko_witryny_z_wlaczonym_trybem(): void
    {
        $this->sub->forceFill(['maintenance_mode' => true])->save();

        $this->get('http://localhost/szkolenia/kontakt')->assertStatus(503)->assertSee('Szkolenia XYZ');
        $this->get('http://localhost/kontakt')->assertOk();
    }

    public function test_blad_404_w_panelu_pokazuje_aktywna_witryne_a_publiczne_404_nie_zalezy_od_sesji_panelu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $r = $this->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->sub->id])
            ->get('http://localhost/admin/nie-ma');
        $r->assertNotFound()->assertSee('Szkolenia XYZ');

        // Kontener testowy trzyma witrynę z poprzedniego żądania; w produkcji każde żądanie startuje od zera.
        app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);

        $this->get('http://localhost/nie-ma/takiej')->assertNotFound()->assertDontSee('Szkolenia XYZ');
    }
}
