<?php

namespace Tests\Feature;

use App\Support\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Donation;
use App\Models\SiteSetting;
use Tests\TestCase;

class EditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Edition::flush();
    }

    protected function tearDown(): void
    {
        @unlink(\App\Support\LicenseOnlineCheck::path());
        Edition::flush();

        parent::tearDown();
    }

    private function useEdition(?string $forced, string $appUrl = 'https://klient.example.org', string $key = ''): void
    {
        config([
            'app.url' => $appUrl,
            'editions.forced' => $forced,
            'editions.license_key' => $key,
        ]);
        Edition::flush();
    }

    public function test_feer_org_pl_is_always_pro_even_when_forced_free(): void
    {
        $this->useEdition('free', 'https://feer.org.pl');
        $this->assertTrue(Edition::isPro());

        $this->useEdition('free', 'https://wiem.feer.org.pl');
        $this->assertTrue(Edition::isPro());
    }

    public function test_local_environment_is_pro(): void
    {
        $this->useEdition(null, 'http://localhost');

        $this->assertTrue(Edition::isPro());
    }

    public function test_installation_without_license_key_is_free(): void
    {
        $this->useEdition(null);

        $this->assertFalse(Edition::isPro());
        $this->assertFalse(Edition::allows('newsletter'));
        $this->assertTrue(Edition::allows('news'), 'funkcje spoza listy są darmowe');
        $this->assertFalse(Edition::allowsTemplate('municipality'));
        $this->assertTrue(Edition::allowsTemplate('ngo'));
    }

    public function test_unverifiable_license_key_does_not_unlock_pro(): void
    {
        config(['license.public_key' => null]);
        $this->useEdition(null, key: 'WCMS-COKOLWIEK');

        $this->assertFalse(Edition::isPro());
    }

    public function test_signed_license_key_gives_pro_and_free_key_gives_free(): void
    {
        $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        config(['license.public_key' => openssl_pkey_get_details($private)['key']]);

        $this->useEdition(null, 'https://www.klient.example.org',
            strtolower($this->licenseKey($private, ['d' => 'klient.example.org', 'e' => '2099-12-31', 'x' => 'premium'])));
        $this->assertTrue(Edition::isPro());

        $this->useEdition(null, 'https://klient.example.org',
            $this->licenseKey($private, ['d' => 'klient.example.org', 'e' => null, 'x' => 'free']));
        $this->assertFalse(Edition::isPro());

        $this->useEdition(null, 'https://klient.example.org',
            $this->licenseKey($private, ['d' => 'klient.example.org', 'e' => '2020-01-01', 'x' => 'premium']));
        $this->assertFalse(Edition::isPro(), 'wygasły klucz');
    }

    public function test_online_check_revocation_downgrades_but_outage_and_not_found_do_not(): void
    {
        $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        config(['license.public_key' => openssl_pkey_get_details($private)['key'], 'editions.check_url' => 'https://panel.example/api/v1/licenses/check']);
        $key = $this->licenseKey($private, ['d' => 'klient.example.org', 'e' => '2099-12-31', 'x' => 'premium']);
        $this->useEdition(null, 'https://klient.example.org', $key);

        \Illuminate\Support\Facades\Http::fakeSequence('panel.example/*')
            ->push('', 500)
            ->push(['valid' => false, 'reason' => 'not_found'])
            ->push(['valid' => false, 'reason' => 'revoked', 'edition' => 'premium'])
            ->pushFailedConnection();

        // Panel niedostępny → nic się nie zmienia.
        $this->artisan('license:check')->assertFailed();
        $this->assertTrue(Edition::isPro());

        // Panel nie zna klucza → ignorowane.
        $this->artisan('license:check')->assertSuccessful();
        $this->assertTrue(Edition::isPro());

        // Unieważniona → wersja darmowa.
        $this->artisan('license:check')->assertSuccessful();
        $this->assertFalse(Edition::isPro());
        $this->assertStringContainsString('unieważniona', Edition::reason());

        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request['key'] === $key && $request['domain'] === 'https://klient.example.org');

        // Kolejna awaria panelu → zostaje ostatni znany wynik (blokada).
        $this->artisan('license:check')->assertFailed();
        Edition::flush();
        $this->assertFalse(Edition::isPro());

        // Nowy klucz nie dziedziczy blokady starego.
        $this->useEdition(null, 'https://klient.example.org',
            $this->licenseKey($private, ['d' => 'klient.example.org', 'e' => '2100-01-01', 'x' => 'premium']));
        $this->assertTrue(Edition::isPro());
    }

    /** Klucz w formacie panelu licencji / scripts/generate-license.php. */
    private function licenseKey(\OpenSSLAsymmetricKey $private, array $data): string
    {
        $payload = json_encode($data + ['n' => '', 'i' => date('Y-m-d')]);
        openssl_sign($payload, $signature, $private, OPENSSL_ALGO_SHA256);

        $base32 = function (string $raw): string {
            $bits = '';
            foreach (str_split($raw) as $char) {
                $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
            }

            return implode('', array_map(
                fn ($chunk) => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'[bindec(str_pad($chunk, 5, '0'))],
                str_split($bits, 5),
            ));
        };

        return 'WCMS-'.implode('-', str_split($base32($payload), 8)).'.'.implode('-', str_split($base32($signature), 8));
    }

    /** Darowizny (edition:donations) — strona widoczna, wysłanie formularza zablokowane. */
    public function test_free_edition_keeps_paid_feature_readable_but_blocks_writes(): void
    {
        $this->useEdition('free', 'http://localhost');
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60'])->save();

        $this->get(route('donation.show'))->assertOk();

        $this->from(route('donation.show'))->post(route('donation.store'), ['amount' => '60'])
            ->assertRedirect(route('donation.show'))
            ->assertSessionHasErrors('edition');

        $this->assertSame(0, Donation::count());
    }

    public function test_pro_edition_passes_writes_to_the_controller(): void
    {
        $this->useEdition('pro', 'http://localhost');
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60'])->save();

        $this->post(route('donation.store'), ['amount' => '60'])
            ->assertSessionHasErrors('first_name')
            ->assertSessionDoesntHaveErrors('edition');
    }
}
