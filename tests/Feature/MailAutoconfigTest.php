<?php

namespace Tests\Feature;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\MailProviderDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailAutoconfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_rozpoznaje_dostawce_po_domenie(): void
    {
        $r = (new MailProviderDetector(fn () => []))->detect('Jan@Gmail.com');

        $this->assertTrue($r['detected']);
        $this->assertSame('google', $r['provider']['key']);
        $this->assertSame('smtp.gmail.com', $r['provider']['settings']['host']);
        $this->assertSame('jan@gmail.com', $r['provider']['settings']['username']);
    }

    public function test_domena_wlasna_na_microsoft_365_jest_rozpoznana_po_mx(): void
    {
        $r = (new MailProviderDetector(fn () => ['feer-org-pl.mail.protection.outlook.com.']))->detect('biuro@feer.org.pl');

        $this->assertSame('m365', $r['provider']['key']);
        $this->assertTrue($r['provider']['graph']);
        $this->assertSame('mx', $r['via']);
    }

    public function test_nieznany_dostawca_dostaje_typowa_podpowiedz(): void
    {
        $r = (new MailProviderDetector(fn () => ['mail.example-host.net']))->detect('a@moja-domena.pl');

        $this->assertFalse($r['detected']);
        $this->assertSame('custom', $r['provider']['key']);
        $this->assertSame('smtp.moja-domena.pl', $r['provider']['settings']['host']);
        $this->assertSame(587, $r['provider']['settings']['port']);
    }

    public function test_endpoint_wykrywania_przekierowuje_goscia(): void
    {
        $this->post(route('admin.ustawienia.mail-detect'), ['email' => 'a@gmail.com'])->assertRedirect();
    }

    public function test_endpoint_wykrywania_odrzuca_niepoprawny_adres(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.ustawienia.mail-detect'), ['email' => 'nie-adres'])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Wpisz poprawny adres e-mail.');
    }

    public function test_endpoint_wykrywania_zwraca_dostawce(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.ustawienia.mail-detect'), ['email' => 'jan@gmail.com'])
            ->assertOk()->assertJsonPath('provider.key', 'google');
    }

    private function jwt(array $roles): string
    {
        $b64 = fn ($a) => rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '=');

        return $b64(['alg' => 'none']).'.'.$b64(['roles' => $roles]).'.sig';
    }

    private function graphCfg(): array
    {
        return ['tenant_id' => '11111111-2222-3333-4444-555555555555', 'client_id' => 'c', 'client_secret' => 's', 'sender' => 'a@feer.org.pl'];
    }

    public function test_diagnoza_graph_potwierdza_uprawnienie_mail_send(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => $this->jwt(['Mail.Send'])])]);

        $r = MicrosoftGraphTransport::diagnose($this->graphCfg());

        $this->assertTrue($r['ok']);
        $this->assertSame('success', $r['level']);
    }

    public function test_diagnoza_graph_ostrzega_o_braku_uprawnienia(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => $this->jwt(['User.Read.All'])])]);

        $r = MicrosoftGraphTransport::diagnose($this->graphCfg());

        $this->assertFalse($r['ok']);
        $this->assertSame('warning', $r['level']);
        $this->assertStringContainsString('Mail.Send', $r['message']);
    }

    public function test_diagnoza_graph_zglasza_bledny_sekret_i_tenant_common(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['error_description' => "AADSTS7000215: Invalid client secret provided.\r\nTrace ID: x"], 401)]);

        $r = MicrosoftGraphTransport::diagnose($this->graphCfg());
        $this->assertSame('error', $r['level']);
        $this->assertStringContainsString('AADSTS7000215', $r['message']);
        $this->assertStringNotContainsString('Trace ID', $r['message']);

        $this->assertStringContainsString('common', MicrosoftGraphTransport::diagnose(['tenant_id' => 'common'] + $this->graphCfg())['message']);
    }

    public function test_endpoint_sprawdzenia_graph_uzupelnia_puste_pola_zapisana_konfiguracja(): void
    {
        SiteSetting::current()->update([
            'msgraph_tenant_id' => '11111111-2222-3333-4444-555555555555', 'msgraph_client_id' => 'c',
            'msgraph_client_secret' => 's', 'msgraph_sender' => 'a@feer.org.pl',
        ]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => $this->jwt(['Mail.Send'])])]);

        $this->actingAs($this->admin())->postJson(route('admin.ustawienia.mail-graph-check'), [])
            ->assertOk()->assertJsonPath('ok', true);
    }

    public function test_zakladka_poczty_renderuje_kreator_bez_zagniezdzonych_formularzy(): void
    {
        $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))
            ->assertOk()->assertSee('Autokonfigurator poczty')->assertSee('Sprawdź połączenie z Graph');
    }
}
