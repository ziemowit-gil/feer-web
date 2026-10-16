<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterForm;
use Modules\Newsletter\Models\NewsletterList;
use Tests\TestCase;

/** Publiczne strony zapisu na newsletter: /newsletter/{slug}, landing /newsletter/{slug}/dolacz i zapis z double opt-in. */
class NewsletterSignupPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Queue::fake();
    }

    private function form(array $attrs = []): NewsletterForm
    {
        $form = NewsletterForm::makeDefault();
        $form->fill(['site_id' => SiteSetting::current()->id] + $attrs)->save();

        return $form;
    }

    private function payload(NewsletterForm $form, array $overrides = []): array
    {
        return array_merge([
            'form_id' => $form->id,
            'source' => 'page_newsletter',
            'email' => 'Ala@Example.com',
            'name' => 'Ala',
            'topics' => ['news', 'events'],
            'consent' => '1',
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
        ], $overrides);
    }

    public function test_strona_zapisu_pokazuje_pola_w_logicznej_kolejnosci_i_informacje_o_potwierdzeniu(): void
    {
        $form = $this->form();

        $this->get(route('newsletter.form', $form))->assertOk()
            ->assertSee('<h1 class="nls-title">' . e($form->heading) . '</h1>', false)
            ->assertSeeInOrder(['Adres e-mail', 'Imię', 'O czym chcesz dostawać wiadomości?', 'Pytanie kontrolne', $form->consent_text, 'Co dalej?', 'Zapisz się'])
            ->assertSee('Zapis zacznie działać dopiero, gdy go klikniesz')
            ->assertSee('Jak to działa')
            ->assertSee('Dlaczego muszę potwierdzić zapis?')
            ->assertSee(route('newsletter.landing', $form), false)
            ->assertDontSee('ETR');
    }

    public function test_domyslna_strona_newsletter_uzywa_nowego_formularza(): void
    {
        $this->form();

        $this->get(route('newsletter.show'))->assertOk()->assertSee('data-nls-form', false)->assertSee('name="source" value="page_newsletter"', false);
    }

    public function test_landing_ma_hero_podglad_wydania_faq_i_dwa_formularze(): void
    {
        $form = $this->form();

        $this->get(route('newsletter.landing', $form))->assertOk()
            ->assertSee('nls-hero', false)
            ->assertSee('Tak wygląda wydanie')
            ->assertSee('Pytania i odpowiedzi')
            ->assertSee('Jak często wysyłacie newsletter?')
            ->assertSee('name="source" value="landing_top"', false)
            ->assertSee('name="source" value="landing_bottom"', false)
            ->assertSeeInOrder(['Zapisz się za darmo', 'Tak wygląda wydanie', 'Pytania i odpowiedzi', 'Dołącz teraz'])
            ->assertDontSee('Co znajdziesz w newsletterze');
    }

    public function test_landing_uzywa_tekstow_z_panelu(): void
    {
        $form = $this->form(['landing' => ['sample_title' => 'Zajrzyj do środka', 'cta_title' => 'Ostatni krok']]);

        $this->get(route('newsletter.landing', $form))->assertOk()
            ->assertSee('Zajrzyj do środka')->assertSee('Ostatni krok')
            ->assertDontSee('Tak wygląda wydanie')
            ->assertSee('Pytania i odpowiedzi'); // puste pole = tekst domyślny
    }

    public function test_wylaczony_formularz_nie_ma_strony_ani_landingu(): void
    {
        $form = $this->form(['is_active' => false]);
        $this->form(['slug' => 'inny', 'is_default' => false]);

        $this->get(route('newsletter.form', $form))->assertNotFound();
        $this->get(route('newsletter.landing', $form))->assertNotFound();
    }

    public function test_zapis_tworzy_oczekujacego_subskrybenta_zgode_i_wysyla_potwierdzenie(): void
    {
        $form = $this->form();
        $list = NewsletterList::create(['site_id' => SiteSetting::current()->id, 'name' => 'Główna', 'slug' => 'glowna']);
        $form->update(['list_ids' => [$list->id]]);

        $this->postJson(route('newsletter.subscribe'), $this->payload($form, ['source' => 'landing_top']))
            ->assertOk()->assertJson(['ok' => true]);

        $subscriber = Subscriber::findByEmail('ala@example.com');
        $this->assertNotNull($subscriber);
        $this->assertSame(Subscriber::STATUS_PENDING, $subscriber->status);
        $this->assertSame(['news', 'events'], $subscriber->topics);
        $this->assertSame('landing_top', $subscriber->source);
        $this->assertTrue($subscriber->lists()->whereKey($list->id)->exists());

        $consent = NewsletterConsent::where('subscriber_id', $subscriber->id)->first();
        $this->assertSame('doi_pending', $consent->method);
        $this->assertSame($form->consent_text, $consent->clause_text);
        $this->assertNull($consent->confirmed_at);

        Queue::assertPushed(SendDoubleOptIn::class);
        Queue::assertNotPushed(SyncSubscriberToCrm::class);
        $this->assertSame(1, $form->fresh()->submissions_count);
    }

    public function test_zapis_bez_js_przekierowuje_na_sprawdz_skrzynke(): void
    {
        $form = $this->form();

        $this->post(route('newsletter.subscribe'), $this->payload($form))->assertRedirect(route('newsletter.pending'));
        $this->get(route('newsletter.pending'))->assertOk()->assertSee('Sprawdź swoją skrzynkę')->assertSee('Bez potwierdzenia nie wyślemy Ci nic więcej');
    }

    public function test_bledy_walidacji_trafiaja_do_wyslanego_formularza_z_podsumowaniem(): void
    {
        $form = $this->form();
        $landing = route('newsletter.landing', $form);

        $this->from($landing)->post(route('newsletter.subscribe'), $this->payload($form, [
            'source' => 'landing_bottom', 'email' => 'zly-adres', 'topics' => [], 'consent' => null,
        ]))->assertRedirect($landing)->assertSessionHasErrors(['email', 'topics', 'consent']);
        $this->assertSame(0, Subscriber::count());
    }

    public function test_zla_odpowiedz_na_pytanie_kontrolne_pokazuje_blad(): void
    {
        $form = $this->form();

        $this->postJson(route('newsletter.subscribe'), $this->payload($form, [SpamGuard::ANSWER_FIELD => '3']))
            ->assertStatus(422)->assertJsonPath('errors.form_answer.0', fn ($m) => str_contains($m, 'zadanie antyspamowe'));
        $this->assertSame(0, Subscriber::count());
    }

    public function test_honeypot_udaje_sukces_i_nic_nie_zapisuje(): void
    {
        $form = $this->form();

        $this->postJson(route('newsletter.subscribe'), $this->payload($form, [SpamGuard::HONEYPOT_FIELD => 'http://spam.example']))->assertOk();
        $this->assertSame(0, Subscriber::count());
        Queue::assertNotPushed(SendDoubleOptIn::class);
    }

    public function test_potwierdzony_subskrybent_dopisuje_tematy_bez_ponownego_potwierdzenia(): void
    {
        $form = $this->form();
        Subscriber::create(['email' => 'ala@example.com', 'site_id' => SiteSetting::current()->id, 'status' => Subscriber::STATUS_CONFIRMED,
            'confirmed_at' => now(), 'topics' => ['materials'], 'token' => Subscriber::generateToken()]);

        $this->postJson(route('newsletter.subscribe'), $this->payload($form))->assertOk();

        $subscriber = Subscriber::findByEmail('ala@example.com');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $subscriber->status);
        $this->assertEqualsCanonicalizing(['materials', 'news', 'events'], $subscriber->topics);
        Queue::assertNotPushed(SendDoubleOptIn::class);
        Queue::assertPushed(SyncSubscriberToCrm::class);
    }

    public function test_klikniecie_linku_potwierdza_zapis_i_zgode(): void
    {
        $form = $this->form();
        $this->postJson(route('newsletter.subscribe'), $this->payload($form))->assertOk();
        $subscriber = Subscriber::findByEmail('ala@example.com');

        $this->get(route('newsletter.confirm', $subscriber->token))->assertOk();

        $this->assertSame(Subscriber::STATUS_CONFIRMED, $subscriber->fresh()->status);
        $this->assertNotNull(NewsletterConsent::where('subscriber_id', $subscriber->id)->value('confirmed_at'));
        Queue::assertPushed(SyncSubscriberToCrm::class);
    }

    public function test_panel_zapisuje_teksty_landingu(): void
    {
        $form = $this->form();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.newsletter.formularze.edit', $form))->assertOk()->assertSee('Landing page')->assertSee('landing[sample_title]', false);

        $this->actingAs($admin)->put(route('admin.newsletter.formularze.update', $form), [
            'name' => $form->name, 'slug' => $form->slug, 'heading' => $form->heading, 'consent_text' => $form->consent_text,
            'button_label' => 'Zapisz się', 'success_message' => $form->success_message, 'style' => 'band', 'is_active' => '1', 'is_default' => '1',
            'landing' => ['sample_title' => 'Zajrzyj do środka', 'cta_title' => ''],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(['sample_title' => 'Zajrzyj do środka'], $form->fresh()->landing);
        $this->assertSame('Dołącz teraz', $form->fresh()->landingText('cta_title'));
    }
}
