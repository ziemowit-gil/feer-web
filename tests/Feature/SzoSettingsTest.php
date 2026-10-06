<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SzoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SzoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCache();
    }

    private function resetCache(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_ustawienia_z_panelu_nadpisuja_env_a_puste_dziedzicza(): void
    {
        config(['szo.url' => 'https://z-env.test', 'szo.token' => 'env-token', 'szo.enabled' => false, 'szo.timeout' => 5]);

        SiteSetting::current()->update([
            'szo_api_url' => 'https://panel.test/',
            'szo_token' => 'panel-token',
            'szo_enabled' => true,
            'szo_default_form' => 'kontakt',
        ]);
        $this->resetCache();

        $overrides = SiteSetting::current()->szoConfigOverrides();

        $this->assertSame('https://panel.test', $overrides['szo.url']);
        $this->assertSame('panel-token', $overrides['szo.token']);
        $this->assertTrue($overrides['szo.enabled']);
        $this->assertSame('kontakt', $overrides['szo.default_form']);
        $this->assertArrayNotHasKey('szo.timeout', $overrides);
        $this->assertArrayNotHasKey('szo.donation_form', $overrides);
    }

    public function test_token_jest_szyfrowany_w_bazie(): void
    {
        SiteSetting::current()->update(['szo_token' => 'tajny-token']);

        $raw = \DB::table('site_settings')->value('szo_token');
        $this->assertNotSame('tajny-token', $raw);
        $this->assertStringNotContainsString('tajny-token', (string) $raw);
    }

    public function test_zapis_ustawien_nie_kasuje_tokenu_przy_pustym_polu(): void
    {
        $admin = $this->admin();
        SiteSetting::current()->update(['szo_token' => 'zostaje']);
        $this->resetCache();

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            'site_name' => 'Fundacja Test', 'brand_color' => '#c31432', 'header_layout' => 'classic',
            'content_editor' => 'tinymce', 'mail_transport' => 'default',
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
            'szo_api_url' => 'https://szo.test', 'szo_token' => '', 'szo_enabled' => '1',
        ])->assertSessionHasNoErrors();
        $this->resetCache();

        $this->assertSame('zostaje', SiteSetting::current()->szo_token);
        $this->assertSame('https://szo.test', SiteSetting::current()->szo_api_url);
        $this->assertTrue(SiteSetting::current()->szo_enabled);
    }

    public function test_diagnoza_brak_adresu_i_zly_token(): void
    {
        $this->assertSame('error', SzoClient::diagnose('', 'x')['level']);
        $this->assertSame('error', SzoClient::diagnose('ftp://szo', 'x')['level']);

        Http::fake([
            'szo.test/klauzule.json' => Http::response(['ok' => true]),
            'szo.test/api/v1/forms.php' => Http::response(['error' => 'no'], 401),
        ]);
        $r = SzoClient::diagnose('https://szo.test/', 'zly');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('odrzuciło token', $r['message']);
    }

    public function test_diagnoza_sukces_i_ostrzezenie_bez_tokenu(): void
    {
        Http::fake([
            'szo.test/klauzule.json' => Http::response(['ok' => true]),
            'szo.test/api/v1/forms.php' => Http::response(['forms' => [['slug' => 'a'], ['slug' => 'b']]]),
        ]);

        $ok = SzoClient::diagnose('https://szo.test', 'dobry');
        $this->assertTrue($ok['ok']);
        $this->assertStringContainsString('2', $ok['message']);

        $warn = SzoClient::diagnose('https://szo.test', '');
        $this->assertSame('warning', $warn['level']);
    }

    public function test_endpoint_diagnozy_uzywa_zapisanej_konfiguracji(): void
    {
        config(['szo.url' => 'https://szo.test', 'szo.token' => 'tok']);
        Http::fake([
            'szo.test/klauzule.json' => Http::response(['ok' => true]),
            'szo.test/api/v1/forms.php' => Http::response(['forms' => []]),
        ]);

        $this->actingAs($this->admin())->postJson(route('admin.ustawienia.szo-check'), [])
            ->assertOk()->assertJsonPath('ok', true);
    }

    public function test_sekcja_szo_jest_widoczna_zawsze_w_ustawieniach(): void
    {
        $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))
            ->assertOk()->assertSee('Integracja z SZO')->assertSee('name="szo_token"', false);
    }
}
