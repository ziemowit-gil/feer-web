<?php

namespace Tests\Feature;

use App\Mail\DonationThankYouMail;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\FundraisingTexts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Moduł „Fundraising i darowizny”: zbiórka → formularz → Przelewy24 → webhook
 * (idempotentny) → podziękowanie (strona, e-mail, PDF), teksty z CMS (globalne
 * i nadpisania w zbiórce), wplacam.ngo.pl, panel wpłat z eksportem.
 */
class FundraisingModuleTest extends TestCase
{
    use RefreshDatabase;

    private const CRC = 'test-crc';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettingsCache();

        config([
            'przelewy24.merchant_id' => '11111',
            'przelewy24.pos_id' => '11111',
            'przelewy24.crc' => self::CRC,
            'przelewy24.api_key' => 'key',
            'przelewy24.sandbox' => true,
            'szo.enabled' => false,
        ]);

        SiteSetting::current()->forceFill(['bank_account_number' => '95 1500 1126 1211 2008 8380 0000'])->save();
        $this->resetSettingsCache();
    }

    private function resetSettingsCache(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function campaign(array $attributes = []): Campaign
    {
        return Campaign::create($attributes + [
            'title' => 'Wózek dla Oli',
            'slug' => 'wozek-dla-oli',
            'status' => 'active',
            'goal_amount' => 10000,
            'external_amount' => 500,
            'amount_options' => '30, 50, 100, 200',
            'allow_custom_amount' => true,
            'allow_recurring' => true,
            'payment_mode' => 'online',
        ]);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'amount' => '50',
            'type' => 'one_time',
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna@example.com',
            'visibility' => 'name',
            'consent_rodo' => '1',
        ];
    }

    private function webhookPayload(Donation $donation, int $orderId = 555): array
    {
        $payload = [
            'merchantId' => 11111, 'posId' => 11111, 'sessionId' => $donation->session_id,
            'amount' => $donation->amount_grosze, 'originAmount' => $donation->amount_grosze,
            'currency' => 'PLN', 'orderId' => $orderId, 'methodId' => 154, 'statement' => 'p24-X',
        ];
        $payload['sign'] = hash('sha384', json_encode($payload + ['crc' => self::CRC]));

        return $payload;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ── Pełny przepływ ────────────────────────────────────────────────────

    public function test_full_donation_flow_from_form_to_thank_you(): void
    {
        Mail::fake();
        $campaign = $this->campaign();
        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/register' => Http::response(['data' => ['token' => 'TOK']]),
            'sandbox.przelewy24.pl/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']]),
        ]);

        $this->post(route('kampanie.donate', $campaign->slug), $this->form([
            'amount' => 'other', 'amount_other' => '75', 'type' => 'recurring',
            'is_company' => '1', 'company_name' => 'ACME sp. z o.o.', 'nip' => '526-000-12-46',
            'dedication' => 'Dla Oli od zespołu', 'dedication_public' => '1',
        ]))->assertRedirect('https://sandbox.przelewy24.pl/trnRequest/TOK');

        $donation = Donation::sole();
        $this->assertSame($campaign->id, $donation->campaign_id);
        $this->assertSame(7500, $donation->amount_grosze);
        $this->assertSame('recurring', $donation->type);
        $this->assertSame('5260001246', $donation->nip);
        $this->assertSame('pending', $donation->status);

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'transaction/register')
            && $r['urlReturn'] === route('kampanie.thanks', [$campaign->slug, $donation])
            && str_contains($r['description'], 'Wózek dla Oli'));

        // Powrót przed webhookiem: wariant „w toku”.
        $this->get(route('kampanie.thanks', [$campaign->slug, $donation]))->assertOk()
            ->assertSee('Czekamy na potwierdzenie płatności');

        $this->postJson(route('przelewy24.webhook'), $this->webhookPayload($donation))->assertOk();

        $donation->refresh();
        $this->assertTrue($donation->isPaid());
        $this->assertNotNull($donation->thanked_at);
        $this->assertSame(75 + 500, $campaign->fresh()->collected_amount);
        Mail::assertSent(DonationThankYouMail::class, fn ($m) => $m->hasTo('anna@example.com'));

        $this->get(route('kampanie.thanks', [$campaign->slug, $donation]))->assertOk()
            ->assertSee('Dziękujemy, Anna!')
            ->assertSee('75 zł')
            ->assertSee(route('donation.receipt', $donation), false)
            ->assertSee('facebook.com/sharer', false)
            ->assertSee('zlecenie stałe');

        $this->get(route('kampanie.show', $campaign->slug))->assertOk()
            ->assertSee('Anna K.')
            ->assertSee('Dla Oli od zespołu')
            ->assertSee('575 zł');
    }

    public function test_repeated_webhook_is_idempotent(): void
    {
        Mail::fake();
        $campaign = $this->campaign(['external_amount' => 0]);
        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'amount_grosze' => 10000,
            'first_name' => 'Jan', 'last_name' => 'Nowak', 'email' => 'jan@example.com',
        ]);
        Http::fake(['sandbox.przelewy24.pl/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']])]);

        $payload = $this->webhookPayload($donation);
        foreach (range(1, 3) as $i) {
            $this->postJson(route('przelewy24.webhook'), $payload)->assertOk();
        }

        $this->assertSame(100, $campaign->fresh()->collected_amount);
        $this->assertSame(1, Donation::where('status', 'paid')->count());
        Mail::assertSentCount(1);
        // Weryfikacja w P24 tylko raz — kolejne webhooki widzą opłaconą wpłatę.
        $this->assertCount(1, Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), 'transaction/verify')));
    }

    public function test_p24_order_id_cannot_be_booked_twice(): void
    {
        Donation::create(['amount_grosze' => 100, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com',
            'status' => 'paid', 'p24_order_id' => 999]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Donation::create(['amount_grosze' => 100, 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@example.com',
            'status' => 'paid', 'p24_order_id' => 999]);
    }

    public function test_failed_payment_shows_retry_which_starts_a_new_payment(): void
    {
        $campaign = $this->campaign();
        $failed = Donation::create(['campaign_id' => $campaign->id, 'amount_grosze' => 5000, 'status' => 'failed',
            'first_name' => 'Anna', 'last_name' => 'K', 'email' => 'anna@example.com']);
        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/by/sessionId/*' => Http::response(['error' => 'not found'], 404),
            'sandbox.przelewy24.pl/api/v1/transaction/register' => Http::response(['data' => ['token' => 'NEW']]),
        ]);

        $this->get(route('kampanie.thanks', [$campaign->slug, $failed]))->assertOk()
            ->assertSee('Płatność nie doszła do skutku')
            ->assertSee('Ponów płatność')
            ->assertSee(route('kampanie.retry', [$campaign->slug, $failed]), false);

        $this->post(route('kampanie.retry', [$campaign->slug, $failed]))
            ->assertRedirect('https://sandbox.przelewy24.pl/trnRequest/NEW');

        $this->assertSame(2, Donation::count());
        $retry = Donation::where('id', '!=', $failed->id)->sole();
        $this->assertSame('pending', $retry->status);
        $this->assertSame(5000, $retry->amount_grosze);
        $this->assertNotSame($failed->session_id, $retry->session_id);
    }

    public function test_validation_uses_cms_messages_and_rejects_bad_nip(): void
    {
        $campaign = $this->campaign(['texts' => ['error_consent' => 'Bez zgody nie przyjmiemy wpłaty.']]);

        $this->post(route('kampanie.donate', $campaign->slug), $this->form([
            'consent_rodo' => null, 'amount' => '33', 'is_company' => '1', 'company_name' => 'X', 'nip' => '1234567890',
        ]))->assertSessionHasErrors(['consent_rodo' => 'Bez zgody nie przyjmiemy wpłaty.', 'nip']);

        $this->post(route('kampanie.donate', $campaign->slug), $this->form(['amount' => '33', 'consent_rodo' => '1']))
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Donation::count());
    }

    public function test_closed_campaign_does_not_accept_donations(): void
    {
        $campaign = $this->campaign(['status' => 'closed']);

        $this->get(route('kampanie.show', $campaign->slug))->assertOk()
            ->assertSee('Ta zbiórka jest zakończona')
            ->assertDontSee('name="consent_rodo"', false);
        $this->post(route('kampanie.donate', $campaign->slug), $this->form())->assertNotFound();
    }

    // ── Teksty i grafiki z CMS ────────────────────────────────────────────

    public function test_pages_render_texts_from_global_settings_and_campaign_overrides(): void
    {
        SiteSetting::current()->forceFill(['fundraising_texts' => [
            'list_title' => 'Nasze zbiórki',
            'form_heading' => 'Wpłać teraz (globalnie)',
            'submit_label' => 'Wspieram',
            'success_title' => 'Super, {imie}!',
        ]])->save();
        $this->resetSettingsCache();

        $campaign = $this->campaign(['texts' => ['form_heading' => 'Pomóż Oli', 'amount_legend' => 'Ile wpłacasz?'],
            'image_alt' => 'Ola na wózku w parku']);

        $this->get(route('kampanie.index'))->assertOk()
            ->assertSee('Nasze zbiórki')
            ->assertSee('Wózek dla Oli');

        $this->get(route('kampanie.show', $campaign->slug))->assertOk()
            ->assertSee('Pomóż Oli')
            ->assertDontSee('Wpłać teraz (globalnie)')
            ->assertSee('Ile wpłacasz?')
            ->assertSee('Wspieram')
            ->assertSee('100 zł')
            ->assertSee('Co miesiąc');

        $paid = Donation::create(['campaign_id' => $campaign->id, 'amount_grosze' => 3000, 'status' => 'paid', 'paid_at' => now(),
            'first_name' => 'Ewa', 'last_name' => 'Z', 'email' => 'ewa@example.com']);
        $this->get(route('kampanie.thanks', [$campaign->slug, $paid]))->assertOk()->assertSee('Super, Ewa!');
    }

    public function test_thank_you_email_uses_cms_subject_body_and_image_alt(): void
    {
        $campaign = $this->campaign(['texts' => [
            'email_subject' => 'Dzięki, {imie}, za {kwota}!',
            'email_body' => 'Twoje {kwota} trafi na „{kampania}”.',
            'email_image_alt' => 'Uśmiechnięta Ola',
        ]]);
        $donation = Donation::create(['campaign_id' => $campaign->id, 'amount_grosze' => 4000, 'status' => 'paid', 'paid_at' => now(),
            'first_name' => 'Piotr', 'last_name' => 'W', 'email' => 'p@example.com', 'session_id' => (string) \Illuminate\Support\Str::uuid()]);
        \Illuminate\Support\Facades\Storage::fake('public');
        $campaign->addMedia(\Illuminate\Http\UploadedFile::fake()->image('ola.jpg', 600, 300))->toMediaCollection('thanks_email_image');

        $mail = new DonationThankYouMail($donation->fresh('campaign'));
        $mail->assertHasSubject('Dzięki, Piotr, za 40 zł!');
        $mail->assertSeeInHtml('Twoje 40 zł trafi na „Wózek dla Oli”.', false);
        $mail->assertSeeInHtml('alt="Uśmiechnięta Ola"', false);
        $mail->assertSeeInHtml(route('donation.receipt', $donation), false);
    }

    public function test_receipt_pdf_only_for_paid_donations(): void
    {
        $pending = Donation::create(['amount_grosze' => 1000, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com']);
        $this->get(route('donation.receipt', $pending))->assertNotFound();

        $paid = Donation::create(['amount_grosze' => 1000, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com',
            'status' => 'paid', 'paid_at' => now(), 'company_name' => 'ACME', 'nip' => '5260001246']);
        $response = $this->get(route('donation.receipt', $paid))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    // ── wplacam.ngo.pl ───────────────────────────────────────────────────

    public function test_wplacam_campaign_renders_embed_or_button(): void
    {
        $embed = $this->campaign(['slug' => 'embed', 'payment_mode' => 'wplacam', 'wplacam_display' => 'embed',
            'wplacam_url' => 'https://wplacam.ngo.pl/wplac/feer-ola']);
        $this->get(route('kampanie.show', $embed->slug))->assertOk()
            ->assertSee('<iframe src="https://wplacam.ngo.pl/wplac/feer-ola"', false)
            ->assertSee('title="Formularz wpłaty wplacam.ngo.pl"', false)
            ->assertDontSee('name="consent_rodo"', false);

        // Adres spoza wplacam.ngo.pl nie trafia do ramki.
        $evil = $this->campaign(['slug' => 'zly', 'payment_mode' => 'wplacam', 'wplacam_display' => 'embed',
            'wplacam_url' => 'https://evil.example/wplacam.ngo.pl']);
        $this->get(route('kampanie.show', $evil->slug))->assertOk()->assertDontSee('evil.example', false);
    }

    // ── Panel ─────────────────────────────────────────────────────────────

    public function test_admin_saves_campaign_with_texts_and_wplacam_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.kampanie.store'), [
            'title' => 'Nowa zbiórka', 'status' => 'active', 'goal_amount' => 5000, 'payment_mode' => 'wplacam',
            'wplacam_url' => 'https://example.com/x',
        ])->assertSessionHasErrors('wplacam_url');

        $this->actingAs($admin)->post(route('admin.kampanie.store'), [
            'title' => 'Nowa zbiórka', 'status' => 'active', 'goal_amount' => 5000, 'external_amount' => 1200,
            'payment_mode' => 'online', 'amount_options' => '20, 40', 'allow_recurring' => '1', 'image_alt' => 'Opis',
            'texts' => ['form_heading' => 'Moja wpłata', 'success_title' => ''],
        ])->assertRedirect();

        $campaign = Campaign::where('title', 'Nowa zbiórka')->sole();
        $this->assertTrue($campaign->is_published);
        $this->assertSame([20, 40], $campaign->amounts());
        $this->assertSame(['form_heading' => 'Moja wpłata'], $campaign->texts);
        $this->assertSame(1200, $campaign->collected_amount);

        $this->actingAs($admin)->get(route('admin.kampanie.edit', $campaign))->assertOk()
            ->assertSee('Moja wpłata')
            ->assertSee('Teksty tej zbiórki');
    }

    public function test_admin_global_texts_page_saves_texts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.zbiorki-teksty.edit'))->assertOk()
            ->assertSee('E-mail z podziękowaniem')
            ->assertSee('Potwierdzenie PDF');

        $this->actingAs($admin)->put(route('admin.zbiorki-teksty.update'), [
            'texts' => ['list_title' => 'Zbiórki FEER', 'nieznany_klucz' => 'x', 'pending_title' => ''],
        ])->assertRedirect(route('admin.zbiorki-teksty.edit'));

        $this->resetSettingsCache();
        $this->assertSame(['list_title' => 'Zbiórki FEER'], SiteSetting::current()->fundraising_texts);
        $this->assertSame('Zbiórki FEER', FundraisingTexts::for(null)->get('list_title'));
    }

    public function test_admin_adds_manual_donation_filters_and_exports(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $campaign = $this->campaign(['external_amount' => 0]);
        Donation::create(['amount_grosze' => 2000, 'first_name' => 'Online', 'last_name' => 'Ogólna', 'email' => 'o@example.com',
            'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($admin)->post(route('admin.darowizny.store'), [
            'campaign_id' => $campaign->id, 'amount' => '250,50', 'type' => 'one_time', 'paid_at' => now()->toDateString(),
            'company_name' => '=HYPERLINK("x")', 'email' => 'firma@example.com', 'send_thanks' => '1',
        ])->assertRedirect();

        $manual = Donation::where('source', 'manual')->sole();
        $this->assertTrue($manual->isPaid());
        $this->assertSame(25050, $manual->amount_grosze);
        $this->assertSame($admin->id, $manual->created_by);
        $this->assertSame(250, $campaign->fresh()->collected_amount);
        Mail::assertSent(DonationThankYouMail::class);

        $this->actingAs($admin)->get(route('admin.darowizny.index', ['kampania' => $campaign->id]))->assertOk()
            ->assertSee('250,50 zł')
            ->assertDontSee('Ogólna');

        $csv = $this->actingAs($admin)->get(route('admin.darowizny.export', ['kampania' => $campaign->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Wózek dla Oli', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Ogólna', $csv);

        $xlsx = $this->actingAs($admin)->get(route('admin.darowizny.export', ['format' => 'xlsx']))->assertOk();
        $file = $xlsx->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Wózek dla Oli', $sheet);
        $this->assertStringContainsString('Ogólna', $sheet);

        // Zwrot odejmuje wpłatę od paska.
        $this->actingAs($admin)->put(route('admin.darowizny.update', $manual), ['status' => 'refunded'])->assertRedirect();
        $this->assertSame('refunded', $manual->fresh()->status);
        $this->assertNotNull($manual->fresh()->refunded_at);
        $this->assertSame(0, $campaign->fresh()->collected_amount);
    }

    public function test_manual_donations_are_not_pushed_to_szo(): void
    {
        config(['szo.enabled' => true, 'szo.url' => 'https://szo.test', 'szo.token' => 'secret']);
        Http::fake();

        app(\App\Services\DonationService::class)->recordManual([
            'amount_grosze' => 1000, 'first_name' => 'Jan', 'email' => '',
        ], null, false);

        Http::assertNothingSent();
        $this->assertSame(0, Donation::pendingSzo()->count());
    }

    public function test_general_donation_works_before_fundraising_migration(): void
    {
        // Push wdraża kod przed ręczną migracją — ogólna darowizna nie może wtedy dawać 500.
        \Illuminate\Support\Facades\Schema::table('donations', function ($table) {
            $table->dropUnique('donations_p24_order_id_unique');
            $table->dropIndex('donations_campaign_id_index');
        });
        \Illuminate\Support\Facades\Schema::table('donations', function ($table) {
            $table->dropColumn(['campaign_id', 'type', 'source', 'company_name', 'nip', 'dedication',
                'dedication_public', 'admin_note', 'created_by', 'thanked_at', 'refunded_at']);
        });
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60'])->save();
        $this->resetSettingsCache();
        Mail::fake();
        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/register' => Http::response(['data' => ['token' => 'OLD']]),
            'sandbox.przelewy24.pl/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']]),
        ]);

        $this->post(route('donation.store'), $this->form(['amount' => '60']))
            ->assertRedirect('https://sandbox.przelewy24.pl/trnRequest/OLD');

        $donation = Donation::sole();
        $this->postJson(route('przelewy24.webhook'), $this->webhookPayload($donation))->assertOk();
        $this->assertSame('paid', $donation->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_retry_does_not_charge_twice_when_original_payment_went_through(): void
    {
        Mail::fake();
        $campaign = $this->campaign(['external_amount' => 0]);
        // „failed” po chwilowym błędzie transaction/verify — pieniądze w P24 już są.
        $failed = Donation::create(['campaign_id' => $campaign->id, 'amount_grosze' => 5000, 'status' => 'failed',
            'first_name' => 'Anna', 'last_name' => 'K', 'email' => 'anna@example.com']);
        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/by/sessionId/*' => Http::response(['data' => ['status' => 2, 'orderId' => 777]]),
            'sandbox.przelewy24.pl/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']]),
        ]);

        $this->post(route('kampanie.retry', [$campaign->slug, $failed]))
            ->assertRedirect(route('kampanie.thanks', [$campaign->slug, $failed]));

        $this->assertSame(1, Donation::count());
        $this->assertTrue($failed->fresh()->isPaid());
        $this->assertSame(50, $campaign->fresh()->collected_amount);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'transaction/register'));
    }

    public function test_retry_waits_while_payment_is_still_processing(): void
    {
        $campaign = $this->campaign(['texts' => ['error_processing' => 'Wpłata wciąż się przetwarza.']]);
        $failed = Donation::create(['campaign_id' => $campaign->id, 'amount_grosze' => 5000, 'status' => 'failed',
            'first_name' => 'Anna', 'last_name' => 'K', 'email' => 'anna@example.com']);
        Http::fake(['sandbox.przelewy24.pl/api/v1/transaction/by/sessionId/*' => Http::response(['data' => ['status' => 1, 'orderId' => 778]])]);

        $this->post(route('kampanie.retry', [$campaign->slug, $failed]))
            ->assertRedirect(route('kampanie.thanks', [$campaign->slug, $failed]))
            ->assertSessionHas('donation_error', 'Wpłata wciąż się przetwarza.');
        $this->assertSame(1, Donation::count());
    }

    public function test_paid_online_donation_can_only_be_refunded_in_panel(): void
    {
        $donation = Donation::create(['amount_grosze' => 1000, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com',
            'status' => 'paid', 'paid_at' => now(), 'source' => 'online']);

        $this->actingAs($this->admin())->put(route('admin.darowizny.update', $donation), ['status' => 'failed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('paid', $donation->fresh()->status);
    }

    public function test_fractional_amount_cannot_bypass_the_amount_list(): void
    {
        $campaign = $this->campaign(['allow_custom_amount' => false]);

        $this->post(route('kampanie.donate', $campaign->slug), $this->form(['amount' => '50.37']))
            ->assertSessionHasErrors('amount');
        $this->assertSame(0, Donation::count());
    }

    public function test_pending_szo_scope_skips_source_column_before_migration(): void
    {
        $this->assertStringContainsString('source', Donation::pendingSzo()->toSql());

        \Illuminate\Support\Facades\Schema::table('donations', fn ($t) => $t->dropColumn('source'));

        $this->assertStringNotContainsString('source', Donation::pendingSzo()->toSql());
    }

    public function test_campaign_without_status_column_behaves_like_before(): void
    {
        $legacy = new Campaign(['title' => 'Stara', 'slug' => 'stara', 'goal_amount' => 0, 'is_published' => true, 'donation_url' => 'https://pomagam.pl/x']);
        $legacy->setRawAttributes(['title' => 'Stara', 'slug' => 'stara', 'is_published' => 1, 'donation_url' => 'https://pomagam.pl/x']);

        $this->assertSame('active', $legacy->statusValue());
        $this->assertSame('external', $legacy->paymentMode());
        $this->assertFalse($legacy->acceptsOnline());
    }
}
