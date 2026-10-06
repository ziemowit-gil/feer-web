<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\CleanTalkGuard;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CleanTalkFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        config(['cleantalk.enabled' => true, 'cleantalk.apikey' => 'test-key']);
    }

    private function form(array $settings = []): FormDefinition
    {
        return FormDefinition::create([
            'title' => 'Kontakt', 'slug' => 'kontakt', 'is_active' => true, 'settings' => $settings,
            'fields' => [
                ['label' => 'Imię', 'type' => 'text', 'required' => true],
                ['label' => 'Adres e-mail', 'type' => 'email', 'required' => true],
                ['label' => 'Wiadomość', 'type' => 'textarea', 'required' => true],
            ],
        ]);
    }

    private function submit(FormDefinition $form, array $extra = [])
    {
        return $this->post(route('formularz.store', $form->slug), $extra + [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'ct_bot_detector_event_token' => str_repeat('a', 64),
            'data' => ['imie' => 'Anna', 'adres_e_mail' => 'anna@example.com', 'wiadomosc' => 'Dzień dobry, mam pytanie o szkolenie.'],
        ]);
    }

    public function test_spam_wedlug_cleantalk_nie_zostaje_zapisany_i_pokazuje_komunikat(): void
    {
        Http::fake(['moderate.cleantalk.org/*' => Http::response(['allow' => 0, 'comment' => 'Spam', 'id' => 'abc123'])]);

        $this->submit($this->form())->assertSessionHasErrors('spam');

        $this->assertSame(0, FormSubmission::count());
        Http::assertSent(function ($request) {
            $b = $request->data();

            return $request->url() === 'https://moderate.cleantalk.org/api2.0'
                && $b['method_name'] === 'check_message'
                && $b['auth_key'] === 'test-key'
                && $b['sender_email'] === 'anna@example.com'
                && $b['sender_nickname'] === 'Anna'
                && str_contains($b['message'], 'pytanie o szkolenie')
                && $b['js_on'] === 1
                && strlen($b['event_token']) === 64;
        });
    }

    public function test_poprawne_zgloszenie_jest_zapisane(): void
    {
        Http::fake(['moderate.cleantalk.org/*' => Http::response(['allow' => 1, 'comment' => ''])]);

        $this->submit($this->form())->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_awaria_i_bledy_uslugi_nie_blokuja_zgloszenia(): void
    {
        $form = $this->form();

        Http::fake(['moderate.cleantalk.org/*' => Http::sequence()
            ->push('', 500)
            ->push(['errno' => 1, 'errstr' => 'Wrong access key', 'allow' => 0])]);
        $this->submit($form)->assertSessionHas('success');
        $this->submit($form, ['data' => ['imie' => 'Ola', 'adres_e_mail' => 'ola@example.com', 'wiadomosc' => 'Inna treść zgłoszenia.']])->assertSessionHas('success');

        $this->assertSame(2, FormSubmission::count());
    }

    public function test_wylaczona_ochrona_lub_formularz_wylaczony_nie_wola_cleantalk(): void
    {
        Http::fake();

        config(['cleantalk.enabled' => false]);
        $this->submit($this->form())->assertSessionHas('success');

        config(['cleantalk.enabled' => true]);
        $skip = FormDefinition::create(['title' => 'Wewn', 'slug' => 'wewn', 'is_active' => true, 'settings' => ['cleantalk_disabled' => true],
            'fields' => [['label' => 'Imię', 'type' => 'text', 'required' => true]]]);
        $this->post(route('formularz.store', 'wewn'), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8', 'data' => ['imie' => 'Jan'],
        ])->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_skrypt_cleantalk_laduje_sie_tylko_gdy_ochrona_wlaczona(): void
    {
        $form = $this->form();

        $this->get(route('formularz.show', $form->slug))->assertOk()->assertSee('fd.cleantalk.org/ct-bot-detector-wrapper.js', false);

        config(['cleantalk.enabled' => false]);
        $this->get(route('formularz.show', $form->slug))->assertOk()->assertDontSee('fd.cleantalk.org', false);

        config(['cleantalk.enabled' => true]);
        $off = FormDefinition::create(['title' => 'Wewn', 'slug' => 'wewn', 'is_active' => true, 'settings' => ['cleantalk_disabled' => true],
            'fields' => [['label' => 'Imię', 'type' => 'text', 'required' => true]]]);
        $this->get(route('formularz.show', $off->slug))->assertOk()->assertDontSee('fd.cleantalk.org', false);
    }

    public function test_diagnoza_klucza(): void
    {
        // Jedna atrapa z kolejką odpowiedzi — kolejne wywołania Http::fake() nie nadpisują pierwszej.
        Http::fake(['moderate.cleantalk.org/*' => Http::sequence()
            ->push(['allow' => 0, 'comment' => 'Spam'])
            ->push(['errno' => 1, 'errstr' => 'Wrong access key', 'allow' => 0])
            ->push(['allow' => 1])]);

        $this->assertSame('success', CleanTalkGuard::diagnose('dobry')['level']);

        $bad = CleanTalkGuard::diagnose('zly');
        $this->assertSame('error', $bad['level']);
        $this->assertStringContainsString('Wrong access key', $bad['message']);

        $this->assertSame('warning', CleanTalkGuard::diagnose('dziwny')['level']);

        $this->assertSame('error', CleanTalkGuard::diagnose('  ')['level']);
    }

    public function test_ustawienia_z_panelu_nadpisuja_env_a_klucz_jest_szyfrowany(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        SiteSetting::current()->update(['cleantalk_enabled' => true, 'cleantalk_access_key' => 'tajny-klucz']);

        $raw = \DB::table('site_settings')->value('cleantalk_access_key');
        $this->assertStringNotContainsString('tajny-klucz', (string) $raw);

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $overrides = SiteSetting::current()->cleantalkConfigOverrides();
        $this->assertSame(['cleantalk.enabled' => true, 'cleantalk.apikey' => 'tajny-klucz'], $overrides);

        Http::fake(['moderate.cleantalk.org/*' => Http::response(['allow' => 0])]);
        $this->actingAs($admin)->postJson(route('admin.ustawienia.cleantalk-check'), [])->assertOk()->assertJsonPath('ok', true);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'login']))
            ->assertOk()->assertSee('Ochrona antyspamowa CleanTalk')->assertSee('name="cleantalk_access_key"', false);
    }

    private function contactPayload(array $extra = []): array
    {
        return $extra + [
            'name' => 'Anna Kowalska', 'email' => 'anna@example.com', 'subject' => 'Pytanie o szkolenie',
            'message' => 'Dzień dobry, chciałabym zapytać o termin szkolenia.', 'rodo_consent' => '1',
            'ct_bot_detector_event_token' => str_repeat('b', 64),
        ];
    }

    public function test_formularz_kontaktowy_odrzuca_spam_wedlug_cleantalk(): void
    {
        Http::fake(['moderate.cleantalk.org/*' => Http::response(['allow' => 0, 'comment' => 'Spam', 'id' => 'x1'])]);

        $this->from(route('contact.show'))->post(route('contact.store'), $this->contactPayload())
            ->assertRedirect(route('contact.show'))
            ->assertSessionHasErrors('message');

        $this->assertSame(0, \App\Models\ContactMessage::count());
        Http::assertSent(function ($request) {
            $b = $request->data();

            return $b['method_name'] === 'check_message'
                && $b['sender_email'] === 'anna@example.com'
                && $b['sender_nickname'] === 'Anna Kowalska'
                && str_contains($b['message'], 'Pytanie o szkolenie')
                && str_contains($b['message'], 'termin szkolenia')
                && $b['js_on'] === 1;
        });
    }

    public function test_formularz_kontaktowy_zapisuje_wiadomosc_gdy_cleantalk_przepuszcza_lub_zawodzi(): void
    {
        Http::fake(['moderate.cleantalk.org/*' => Http::sequence()
            ->push(['allow' => 1])
            ->push('', 503)]);

        $this->post(route('contact.store'), $this->contactPayload())->assertSessionHasNoErrors();
        $this->post(route('contact.store'), $this->contactPayload(['message' => 'Druga wiadomość testowa.']))->assertSessionHasNoErrors();

        $this->assertSame(2, \App\Models\ContactMessage::count());
    }

    public function test_formularz_kontaktowy_bez_ochrony_nie_wola_cleantalk_i_nie_laduje_skryptu(): void
    {
        Http::fake();
        config(['cleantalk.enabled' => false]);

        $this->post(route('contact.store'), $this->contactPayload())->assertSessionHasNoErrors();
        Http::assertNothingSent();
        $this->get(route('contact.show'))->assertOk()->assertDontSee('fd.cleantalk.org', false);

        config(['cleantalk.enabled' => true]);
        $this->get(route('contact.show'))->assertOk()->assertSee('fd.cleantalk.org/ct-bot-detector-wrapper.js', false);
    }
}
