<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Mail\FormSubmissionNotification;
use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MsGraphMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flush();
    }

    private function flush(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Cache::flush();
    }

    private function configureGraph(array $extra = []): void
    {
        SiteSetting::current()->update(array_merge([
            'msgraph_tenant_id' => '11111111-2222-3333-4444-555555555555',
            'msgraph_client_id' => 'client-id',
            'msgraph_client_secret' => 'super-secret',
            'msgraph_sender' => 'powiadomienia@feer.org.pl',
        ], $extra));
        $this->flush();
    }

    private function fakeGraph(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'graph.microsoft.com/*' => Http::response('', 202),
        ]);
    }

    private function form(array $settings = []): FormDefinition
    {
        return FormDefinition::create([
            'title' => 'Kontakt', 'slug' => 'kontakt-test', 'is_active' => true,
            'fields' => [
                ['label' => 'Imię', 'type' => 'text', 'required' => true],
                ['label' => 'Adres e-mail', 'type' => 'email', 'required' => true],
            ],
            'settings' => array_merge(['notification_email' => 'biuro@feer.org.pl, drugi@feer.org.pl'], $settings),
        ]);
    }

    private function submit(FormDefinition $form)
    {
        return $this->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => ['imie' => 'Anna', 'adres_e_mail' => 'anna@example.com'],
        ]);
    }

    public function test_formularz_nie_wysyla_przez_graph_mimo_konfiguracji(): void
    {
        $this->configureGraph();
        $this->fakeGraph();
        Mail::fake();

        // Dawne ustawienie formularza „mailer: msgraph” też jest pomijane.
        $this->submit($this->form(['mailer' => 'msgraph', 'send_copy_to_submitter' => true]))->assertRedirect();

        $this->assertSame(1, FormSubmission::count());
        Http::assertNothingSent();
        Mail::assertSent(FormSubmissionNotification::class, 2); // redakcja + kopia dla zgłaszającego
    }

    public function test_graph_wysyla_wiadomosc_i_buforuje_token(): void
    {
        $this->configureGraph();
        $this->fakeGraph();

        foreach ([1, 2] as $i) {
            Mail::mailer('msgraph')->raw('Treść '.$i, fn ($m) => $m->to('biuro@feer.org.pl')->replyTo('anna@example.com')->subject('Test '.$i));
        }

        Http::assertSentCount(3); // 1 token + 2 wiadomości
        Http::assertSent(fn ($request) => str_contains($request->url(), '/users/powiadomienia%40feer.org.pl/sendMail')
            && $request->hasHeader('Authorization', 'Bearer tok')
            && $request['message']['subject'] === 'Test 1'
            && $request['message']['replyTo'][0]['emailAddress']['address'] === 'anna@example.com'
            && $request['saveToSentItems'] === true);
    }

    public function test_blad_serwera_smtp_nie_psuje_potwierdzenia_zgloszenia(): void
    {
        // Nieosiągalny serwer SMTP: wysyłka się nie uda, ale zgłoszenie zostaje zapisane.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

        $this->submit($this->form())->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_tenant_common_nie_liczy_sie_jako_skonfigurowany(): void
    {
        $this->configureGraph(['msgraph_tenant_id' => 'common']);

        $this->assertFalse(SiteSetting::current()->msGraphConfigured());
    }

    private function jwtWithRoles(array $roles): string
    {
        $b64 = fn ($a) => rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '=');

        return $b64(['alg' => 'none']).'.'.$b64(['roles' => $roles]).'.sig';
    }

    public function test_403_odswieza_token_i_ponawia_wysylke_raz(): void
    {
        $this->configureGraph();
        Http::fake([
            'login.microsoftonline.com/*' => Http::sequence()
                ->push(['access_token' => $this->jwtWithRoles([]), 'expires_in' => 3600])
                ->push(['access_token' => $this->jwtWithRoles(['Mail.Send']), 'expires_in' => 3600]),
            'graph.microsoft.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Access is denied. Check credentials and try again.']], 403)
                ->push('', 202),
        ]);

        Mail::mailer('msgraph')->raw('x', fn ($m) => $m->to('biuro@feer.org.pl')->subject('t'));

        // 2 tokeny + 2 próby sendMail (pierwsza odrzucona, druga przyjęta).
        Http::assertSentCount(4);
    }

    public function test_403_bez_mail_send_w_tokenie_daje_czytelna_wskazowke(): void
    {
        $this->configureGraph();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => $this->jwtWithRoles(['User.Read.All']), 'expires_in' => 3600]),
            'graph.microsoft.com/*' => Http::response(['error' => ['message' => 'Access is denied. Check credentials and try again.']], 403),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::mailer('msgraph')->raw('x', fn ($m) => $m->to('a@example.com')->subject('t'));
            $this->fail('Oczekiwano wyjątku transportu.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            $this->assertStringContainsString('HTTP 403', $e->getMessage());
            $this->assertStringContainsString('nie zawiera uprawnienia aplikacyjnego Mail.Send', $e->getMessage());
        }
    }

    public function test_403_z_mail_send_wskazuje_na_dostep_do_skrzynki(): void
    {
        $this->configureGraph();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => $this->jwtWithRoles(['Mail.Send']), 'expires_in' => 3600]),
            'graph.microsoft.com/*' => Http::response(['error' => ['message' => 'Access is denied.']], 403),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::mailer('msgraph')->raw('x', fn ($m) => $m->to('a@example.com')->subject('t'));
            $this->fail('Oczekiwano wyjątku transportu.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            $this->assertStringContainsString('ApplicationAccessPolicy', $e->getMessage());
            $this->assertStringContainsString('powiadomienia@feer.org.pl', $e->getMessage());
        }
    }
}
