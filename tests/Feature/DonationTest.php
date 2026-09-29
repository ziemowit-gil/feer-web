<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DonationTest extends TestCase
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
            'szo.enabled' => true,
            'szo.url' => 'https://szo.test',
            'szo.token' => 'secret',
            'szo.donation_form' => 'darowizna',
        ]);

        SiteSetting::current()->forceFill([
            'bank_account_number' => '95 1500 1126 1211 2008 8380 0000',
            'donation_amounts' => '30, 60, 250',
        ])->save();
        $this->resetSettingsCache();
    }

    private function resetSettingsCache(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function validForm(array $overrides = []): array
    {
        return $overrides + [
            'amount' => '60',
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna@example.com',
            'visibility' => 'name',
            'consent_rodo' => '1',
            'consent_newsletter' => '1',
        ];
    }

    /** Podpisany webhook P24 dla danej darowizny (ten sam algorytm co Przelewy24Client). */
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

    public function test_page_shows_amounts_bank_account_and_iban(): void
    {
        $this->get(route('donation.show'))->assertOk()
            ->assertSee('Darowizna jednorazowa')
            ->assertSee('250 zł')
            ->assertSee('95 1500 1126 1211 2008 8380 0000')
            ->assertSee('PL95 1500 1126 1211 2008 8380 0000')
            ->assertSee('name="consent_rodo"', false);
    }

    public function test_consent_and_personal_data_are_required(): void
    {
        $this->post(route('donation.store'), ['amount' => '60'])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'visibility', 'consent_rodo']);

        $this->assertSame(0, Donation::count());
    }

    public function test_amount_outside_the_list_is_rejected(): void
    {
        $this->post(route('donation.store'), $this->validForm(['amount' => '7']))
            ->assertSessionHasErrors('amount');
    }

    public function test_store_registers_transaction_and_redirects_to_przelewy24(): void
    {
        Http::fake(['sandbox.przelewy24.pl/api/v1/transaction/register' => Http::response(['data' => ['token' => 'TOKEN123']])]);

        $this->post(route('donation.store'), $this->validForm(['amount' => 'other', 'amount_other' => '42.5']))
            ->assertRedirect('https://sandbox.przelewy24.pl/trnRequest/TOKEN123');

        $donation = Donation::sole();
        $this->assertSame(4250, $donation->amount_grosze);
        $this->assertSame('pending', $donation->status);
        $this->assertFalse($donation->is_anonymous);

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'transaction/register')
            && $r['sessionId'] === $donation->session_id
            && $r['amount'] === 4250
            && $r['urlStatus'] === route('przelewy24.webhook'));
    }

    public function test_webhook_marks_paid_and_pushes_once_to_szo(): void
    {
        $donation = Donation::create([
            'amount_grosze' => 6000, 'first_name' => 'Anna', 'last_name' => 'Kowalska',
            'email' => 'anna@example.com', 'is_anonymous' => false, 'consent_newsletter' => true,
        ]);

        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/verify' => Http::response(['data' => ['status' => 'success']]),
            'szo.test/api/v1/donations.php' => Http::response(['ok' => true, 'duplicate' => false, 'donation_id' => 77, 'contact_id' => 9], 201),
        ]);

        $payload = $this->webhookPayload($donation);
        $this->postJson(route('przelewy24.webhook'), $payload)->assertOk();
        $this->postJson(route('przelewy24.webhook'), $payload)->assertOk();

        $donation->refresh();
        $this->assertTrue($donation->isPaid());
        $this->assertSame(555, $donation->p24_order_id);
        $this->assertSame(77, (int) $donation->szo_donation_id);
        $this->assertNotNull($donation->szo_synced_at);

        $szoCalls = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), 'szo.test'));
        $this->assertCount(1, $szoCalls);
        [$request] = $szoCalls->first();
        $this->assertSame('p24:' . $donation->session_id, $request['external_id']);
        $this->assertSame(60, (int) $request['amount']);
        $this->assertSame(['rodo', 'newsletter'], $request['consents']);
        $this->assertSame('Bearer secret', $request->header('Authorization')[0]);
    }

    public function test_szo_failure_keeps_donation_paid_and_sync_retries(): void
    {
        $donation = Donation::create([
            'amount_grosze' => 3000, 'first_name' => 'Jan', 'last_name' => 'Nowak',
            'email' => 'jan@example.com', 'is_anonymous' => true,
        ]);

        Http::fakeSequence('szo.test/*')
            ->push(['error' => 'down'], 503)
            ->push(['ok' => true, 'donation_id' => 5, 'contact_id' => 2], 201);
        Http::fake(['sandbox.przelewy24.pl/*' => Http::response(['data' => ['status' => 'success']])]);

        $this->postJson(route('przelewy24.webhook'), $this->webhookPayload($donation))->assertOk();

        $donation->refresh();
        $this->assertTrue($donation->isPaid());
        $this->assertNull($donation->szo_synced_at);
        $this->assertNotNull($donation->szo_error);

        $this->artisan('donations:sync')->assertSuccessful();

        $this->assertNotNull($donation->fresh()->szo_synced_at);
    }

    public function test_invalid_signature_is_ignored(): void
    {
        $donation = Donation::create([
            'amount_grosze' => 3000, 'first_name' => 'Jan', 'last_name' => 'Nowak', 'email' => 'jan@example.com',
        ]);
        Http::fake();

        $payload = $this->webhookPayload($donation);
        $payload['amount'] = 1;
        $this->postJson(route('przelewy24.webhook'), $payload)->assertOk();

        $this->assertSame('pending', $donation->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_recent_list_respects_anonymity(): void
    {
        Donation::create(['amount_grosze' => 6000, 'first_name' => 'Anna', 'last_name' => 'Kowalska', 'email' => 'a@example.com',
            'is_anonymous' => false, 'status' => 'paid', 'paid_at' => now()]);
        Donation::create(['amount_grosze' => 12000, 'first_name' => 'Tajny', 'last_name' => 'Darczyńca', 'email' => 't@example.com',
            'is_anonymous' => true, 'status' => 'paid', 'paid_at' => now()]);
        Donation::create(['amount_grosze' => 9900, 'first_name' => 'Niezapłacony', 'last_name' => 'X', 'email' => 'x@example.com',
            'is_anonymous' => false, 'status' => 'pending']);

        $this->get(route('donation.show'))->assertOk()
            ->assertSee('Anna K.')
            ->assertSee('Wpłata anonimowa')
            ->assertSee('120 zł')
            ->assertDontSee('Tajny')
            ->assertDontSee('Niezapłacony');
    }

    public function test_transfer_slip_is_a_pdf(): void
    {
        $response = $this->get(route('donation.slip', ['kwota' => '60']))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
