<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\Auth\WebAuthn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Klucze dostępu (WebAuthn) z programowym „urządzeniem” P-256: rejestracja w profilu, logowanie bez hasła,
 * odrzucenie złego wyzwania, obcej domeny i niezmienionego licznika (sklonowany klucz).
 */
class PasskeyLoginTest extends TestCase
{
    use RefreshDatabase;

    private \OpenSSLAsymmetricKey $key;

    private string $credId;

    private string $origin;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->credId = random_bytes(16);
        $this->origin = rtrim(config('app.url'), '/');
    }

    // ── mały koder CBOR (tylko to, czego potrzebuje test) ──
    private function cbor(mixed $v): string
    {
        $head = function (int $major, int $len) {
            if ($len < 24) return chr(($major << 5) | $len);
            if ($len < 256) return chr(($major << 5) | 24).chr($len);
            return chr(($major << 5) | 25).pack('n', $len);
        };
        if (is_int($v)) return $v >= 0 ? $head(0, $v) : $head(1, -1 - $v);
        if (is_string($v) && str_starts_with($v, 'txt:')) return $head(3, strlen(substr($v, 4))).substr($v, 4);
        if (is_string($v)) return $head(2, strlen($v)).$v;
        if (is_array($v)) {
            $out = $head(5, count($v));
            foreach ($v as $k => $val) $out .= $this->cbor(is_string($k) ? 'txt:'.$k : $k).$this->cbor($val);
            return $out;
        }
        throw new \LogicException('cbor');
    }

    private function host(): string
    {
        return parse_url($this->origin, PHP_URL_HOST);
    }

    private function authData(int $flags, int $counter, bool $withKey = false, ?string $rpId = null): string
    {
        $data = hash('sha256', $rpId ?? $this->host(), true).chr($flags).pack('N', $counter);
        if ($withKey) {
            $d = openssl_pkey_get_details($this->key)['ec'];
            $cose = $this->cbor([1 => 2, 3 => -7, -1 => 1, -2 => str_pad($d['x'], 32, "\0", STR_PAD_LEFT), -3 => str_pad($d['y'], 32, "\0", STR_PAD_LEFT)]);
            $data .= str_repeat("\0", 16).pack('n', strlen($this->credId)).$this->credId.$cose;
        }

        return $data;
    }

    private function clientData(string $type, string $challenge, ?string $origin = null): string
    {
        return json_encode(['type' => $type, 'challenge' => $challenge, 'origin' => $origin ?? $this->origin]);
    }

    private function register(User $user): void
    {
        $opts = $this->actingAs($user)->postJson(route('passkeys.options'))->assertOk()->json();
        $clientData = $this->clientData('webauthn.create', $opts['challenge']);
        $att = $this->cbor(['fmt' => 'txt:none', 'attStmt' => [], 'authData' => $this->authData(0x45, 0, withKey: true)]);

        $this->actingAs($user)->postJson(route('passkeys.store'), [
            'name' => 'YubiKey testowy',
            'credential' => ['id' => WebAuthn::b64($this->credId), 'rawId' => WebAuthn::b64($this->credId), 'type' => 'public-key',
                'response' => ['clientDataJSON' => WebAuthn::b64($clientData), 'attestationObject' => WebAuthn::b64($att), 'transports' => ['usb']]],
        ])->assertOk();
    }

    private function assertion(int $counter, ?string $challenge = null, ?string $rpId = null): array
    {
        $challenge ??= $this->postJson(route('passkey.login.options'))->assertOk()->json('challenge');
        $clientData = $this->clientData('webauthn.get', $challenge);
        $authData = $this->authData(0x05, $counter, rpId: $rpId);
        openssl_sign($authData.hash('sha256', $clientData, true), $sig, $this->key, OPENSSL_ALGO_SHA256);

        return ['credential' => ['id' => WebAuthn::b64($this->credId), 'type' => 'public-key', 'response' => [
            'clientDataJSON' => WebAuthn::b64($clientData), 'authenticatorData' => WebAuthn::b64($authData), 'signature' => WebAuthn::b64($sig),
        ]]];
    }

    public function test_register_and_login_with_passkey(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->register($user);
        $this->assertSame('YubiKey testowy', WebauthnCredential::first()->name);
        auth()->logout();

        $this->postJson(route('passkey.login'), $this->assertion(1))->assertOk()->assertJsonStructure(['redirect']);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('passkey', session('login_method'));
        $this->assertSame(1, WebauthnCredential::first()->sign_count);
    }

    public function test_rejects_replayed_counter_wrong_challenge_and_foreign_domain(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->register($user);
        auth()->logout();
        $this->postJson(route('passkey.login'), $this->assertion(5))->assertOk();
        auth()->logout();

        $this->postJson(route('passkey.login'), $this->assertion(5))->assertStatus(422); // licznik nie wzrósł
        $this->postJson(route('passkey.login.options'));
        $this->postJson(route('passkey.login'), $this->assertion(6, challenge: WebAuthn::b64(random_bytes(32))))->assertStatus(422);
        $this->postJson(route('passkey.login'), $this->assertion(7, rpId: 'obca-domena.pl'))->assertStatus(422);
        $this->assertGuest();
    }

    public function test_login_page_shows_passkey_button(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Zaloguj się kluczem dostępu');
    }

    public function test_passkey_button_also_in_microsoft_only_mode_and_profile_for_microsoft_users(): void
    {
        SiteSetting::current()->update(['microsoft_login_enabled' => true, 'microsoft_only_login' => true, 'microsoft_client_id' => 'x', 'microsoft_client_secret' => 'y', 'microsoft_tenant_id' => 'z']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get(route('login'))->assertOk()
            ->assertSee('Zaloguj się przez Microsoft 365')
            ->assertSee('Zaloguj się kluczem dostępu');

        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'microsoft_id' => 'ms-1']);
        $this->actingAs($user)->withSession(['login_method' => 'microsoft'])->get(route('profile.edit'))
            ->assertOk()->assertSee('Klucze dostępu')->assertSee('Dodaj klucz dostępu');
    }
}
