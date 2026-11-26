<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Modules\Newsletter\Channels\OutboundMessage;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Providers\NewsletterServiceProvider;
use Tests\TestCase;

/** Newsletter wysyłany ze skrzynki w dPoczcie (smtp.dpoczta.pl:587, STARTTLS). */
class NewsletterDpocztaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forgetSettings();
    }

    private function forgetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function save(array $extra)
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        return $this->actingAs($admin)->from(route('admin.newsletter.ustawienia.edit'))->put(route('admin.newsletter.ustawienia.update'), [
            'newsletter_rate_per_minute' => 10, 'newsletter_doi_ttl_days' => 7, 'newsletter_retention_days' => 365,
        ] + $extra);
    }

    private function applyMailer(): array
    {
        $this->forgetSettings();
        $p = new NewsletterServiceProvider($this->app);
        \Closure::bind(function () { $this->applyMailerConfig(); }, $p, NewsletterServiceProvider::class)();

        return config('mail.mailers.newsletter');
    }

    public function test_ustawienia_pokazuja_dpoczte_i_wymagaja_skrzynki_i_hasla(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.newsletter.ustawienia.edit'))->assertOk()
            ->assertSee('dPoczta (skrzynka w dhosting.pl)')->assertSee('smtp.dpoczta.pl');

        $this->save(['newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => 'newsletter'])
            ->assertSessionHasErrors(['newsletter_smtp_username', 'newsletter_smtp_password']);
    }

    public function test_mailer_newsletter_laczy_sie_z_dpoczta(): void
    {
        $this->save(['newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => ' newsletter@feer.org.pl ', 'newsletter_smtp_password' => 'tajne-haslo'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $m = $this->applyMailer();
        $this->assertSame('smtp', $m['transport']);
        $this->assertSame('smtp.dpoczta.pl', $m['host']);
        $this->assertSame(587, $m['port']);
        $this->assertSame('newsletter@feer.org.pl', $m['username']);
        $this->assertSame('tajne-haslo', $m['password']);

        // Puste hasło przy kolejnym zapisie zostawia poprzednie.
        $this->save(['newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => 'newsletter@feer.org.pl'])->assertSessionHasNoErrors();
        $this->assertSame('tajne-haslo', $this->applyMailer()['password']);
    }

    public function test_nadawca_to_zawsze_skrzynka_a_inny_adres_idzie_do_odpowiedzi(): void
    {
        SiteSetting::current()->forceFill([
            'newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => 'newsletter@feer.org.pl',
            'newsletter_from_address' => 'biuro@feer.org.pl', 'newsletter_from_name' => 'Fundacja FEER',
        ])->save();
        $this->forgetSettings();

        $env = (new CampaignMail(new OutboundMessage('email', 'Temat', '<p>x</p>', 'x')))->envelope();
        $this->assertSame('newsletter@feer.org.pl', $env->from->address);
        $this->assertSame('Fundacja FEER', $env->from->name);
        $this->assertSame('biuro@feer.org.pl', $env->replyTo[0]->address);
    }
    private function testConnection(array $form)
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        return $this->actingAs($admin)->from(route('admin.newsletter.ustawienia.edit'))
            ->put(route('admin.newsletter.ustawienia.polaczenie'), $form + ['newsletter_rate_per_minute' => 10])
            ->assertRedirect(route('admin.newsletter.ustawienia.edit'));
    }

    public function test_przycisk_testuj_polaczenie_jest_w_ustawieniach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.newsletter.ustawienia.edit'))->assertOk()
            ->assertSee('formaction="'.route('admin.newsletter.ustawienia.polaczenie').'"', false)
            ->assertSee('Testuj połączenie');
    }

    public function test_polaczenie_dpoczta_udane_na_danych_z_formularza_bez_zapisu(): void
    {
        $seen = null;
        Mail::shouldReceive('createSymfonyTransport')->once()->andReturnUsing(function (array $config) use (&$seen) {
            $seen = $config;

            return new class('localhost') extends EsmtpTransport {
                public function start(): void {}
                public function stop(): void {}
            };
        });

        $res = $this->testConnection(['newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => 'newsletter@feer.org.pl', 'newsletter_smtp_password' => 'tajne']);
        $conn = session('connection_test');
        $this->assertTrue($conn['ok'], $conn['message']);
        $this->assertStringContainsString('smtp.dpoczta.pl:587', $conn['message']);
        $this->assertStringContainsString('zalogowano jako newsletter@feer.org.pl', $conn['message']);
        $this->assertSame(['smtp.dpoczta.pl', 587, 'tajne', 15], [$seen['host'], $seen['port'], $seen['password'], $seen['timeout']]);
        // Nic nie zapisane, a hasło nie wraca do formularza.
        $this->forgetSettings();
        $this->assertNull(SiteSetting::current()->newsletter_mailer ?: null);
        $res->assertSessionHasInput('newsletter_smtp_username', 'newsletter@feer.org.pl');
        $this->assertNull(session()->getOldInput('newsletter_smtp_password'));
    }

    public function test_polaczenie_nieudane_i_przypadki_bez_testu(): void
    {
        // Zamknięty port na localhost — prawdziwa próba połączenia.
        $this->testConnection(['newsletter_mailer' => 'smtp', 'newsletter_smtp_host' => '127.0.0.1', 'newsletter_smtp_port' => 1, 'newsletter_smtp_encryption' => 'tls']);
        $this->assertFalse(session('connection_test')['ok']);
        $this->assertStringContainsString('Połączenie z 127.0.0.1:1 nie działa', session('connection_test')['message']);

        $this->testConnection(['newsletter_mailer' => 'dpoczta', 'newsletter_smtp_username' => 'newsletter']);
        $this->assertStringContainsString('pełny adres skrzynki', session('connection_test')['message']);

        $this->testConnection(['newsletter_mailer' => 'mailgun']);
        $this->assertStringContainsString('nie ma testu połączenia', session('connection_test')['message']);
    }
}
