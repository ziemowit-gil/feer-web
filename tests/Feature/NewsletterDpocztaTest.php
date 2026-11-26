<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
