<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Support\Cbor;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * WebAuthn (passkeys): rejestracja klucza w profilu i logowanie bez hasła — YubiKey (FIDO2),
 * odcisk palca / Windows Hello / Touch ID, klucz w telefonie.
 *
 * Weryfikujemy to, co decyduje o bezpieczeństwie logowania: wyzwanie (jednorazowe, z sesji),
 * typ operacji, pochodzenie strony, skrót identyfikatora domeny (rpId), obecność i weryfikację
 * użytkownika (PIN / biometria), podpis kluczem zapisanym przy rejestracji i licznik podpisów.
 * Atestu urządzenia przy rejestracji nie sprawdzamy (attestation: none) — klucz dodaje
 * zalogowany użytkownik w swoim profilu, a nie anonimowy rejestrujący się.
 *
 * Algorytmy: ES256 (-7), RS256 (-257), EdDSA/Ed25519 (-8).
 */
class WebAuthn
{
    private const SESSION_REGISTER = 'webauthn.register';

    private const SESSION_LOGIN = 'webauthn.login';

    public function __construct(private Request $request) {}

    // ── Rejestracja ────────────────────────────────────────────────────────

    public function creationOptions(User $user): array
    {
        $challenge = random_bytes(32);
        $this->request->session()->put(self::SESSION_REGISTER, self::b64($challenge));

        return [
            'challenge' => self::b64($challenge),
            'rp' => ['name' => (string) config('app.name'), 'id' => $this->rpId()],
            'user' => [
                'id' => self::b64(hash('sha256', 'user:'.$user->id.':'.config('app.key'), true)),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -8],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout' => 120000,
            'attestation' => 'none',
            'authenticatorSelection' => ['residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => 'required'],
            'excludeCredentials' => $user->webauthnCredentials()->pluck('credential_id')
                ->map(fn ($id) => ['type' => 'public-key', 'id' => $id])->values()->all(),
        ];
    }

    /** Weryfikuje odpowiedź navigator.credentials.create() i zapisuje klucz. */
    public function register(User $user, array $payload, string $name): WebauthnCredential
    {
        $challenge = $this->request->session()->pull(self::SESSION_REGISTER);
        $response = $payload['response'] ?? [];
        $clientData = $this->clientData($response['clientDataJSON'] ?? '', 'webauthn.create', $challenge);

        $attestation = Cbor::decode(self::unb64($response['attestationObject'] ?? ''));
        $authData = (string) ($attestation['authData'] ?? '');
        $parsed = $this->authData($authData, attested: true);

        $credentialId = self::b64($parsed['credential_id']);
        if (! hash_equals($credentialId, (string) ($payload['id'] ?? ''))) {
            throw new RuntimeException('Identyfikator klucza nie zgadza się z danymi urządzenia.');
        }
        if (WebauthnCredential::where('credential_id', $credentialId)->exists()) {
            throw new RuntimeException('Ten klucz jest już dodany.');
        }

        [$publicKey, $algorithm] = $this->coseToKey($parsed['cose_key']);

        return $user->webauthnCredentials()->create([
            'name' => mb_substr(trim($name) ?: 'Klucz dostępu', 0, 120),
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'algorithm' => $algorithm,
            'sign_count' => $parsed['sign_count'],
            'transports' => array_values(array_filter((array) ($response['transports'] ?? []), 'is_string')),
        ]);
    }

    // ── Logowanie ──────────────────────────────────────────────────────────

    public function requestOptions(): array
    {
        $challenge = random_bytes(32);
        $this->request->session()->put(self::SESSION_LOGIN, self::b64($challenge));

        return [
            'challenge' => self::b64($challenge),
            'rpId' => $this->rpId(),
            'timeout' => 120000,
            'userVerification' => 'required',
            'allowCredentials' => [], // klucze rezydentne — przeglądarka sama pokaże konta
        ];
    }

    /** Weryfikuje odpowiedź navigator.credentials.get() i zwraca zalogowanego użytkownika. */
    public function authenticate(array $payload): User
    {
        $challenge = $this->request->session()->pull(self::SESSION_LOGIN);
        $response = $payload['response'] ?? [];

        $credential = WebauthnCredential::with('user')->where('credential_id', (string) ($payload['id'] ?? ''))->first();
        if (! $credential || ! $credential->user) {
            throw new RuntimeException('Ten klucz nie jest przypisany do żadnego konta w panelu.');
        }

        $clientDataRaw = self::unb64($response['clientDataJSON'] ?? '');
        $this->clientData($response['clientDataJSON'] ?? '', 'webauthn.get', $challenge);
        $authData = self::unb64($response['authenticatorData'] ?? '');
        $parsed = $this->authData($authData, attested: false);

        $signed = $authData.hash('sha256', $clientDataRaw, true);
        if (! $this->verifySignature($credential, $signed, self::unb64($response['signature'] ?? ''))) {
            throw new RuntimeException('Podpis klucza jest nieprawidłowy.');
        }

        // Licznik: gdy urządzenie go prowadzi, musi rosnąć (inaczej możliwy sklonowany klucz).
        if ($parsed['sign_count'] > 0 || $credential->sign_count > 0) {
            if ($parsed['sign_count'] <= $credential->sign_count) {
                throw new RuntimeException('Licznik klucza się nie zgadza — klucz mógł zostać skopiowany. Skontaktuj się z administratorem.');
            }
        }

        $credential->forceFill(['sign_count' => $parsed['sign_count'], 'last_used_at' => now()])->save();

        return $credential->user;
    }

    // ── Wspólne ────────────────────────────────────────────────────────────

    /** Domena strony bez portu — identyfikator, do którego przypisane są klucze. */
    public function rpId(): string
    {
        return $this->request->getHost();
    }

    private function origin(): string
    {
        return $this->request->getSchemeAndHttpHost();
    }

    private function clientData(string $b64, string $type, ?string $challenge): array
    {
        $data = json_decode(self::unb64($b64), true);
        if (! is_array($data)) {
            throw new RuntimeException('Nieprawidłowa odpowiedź przeglądarki.');
        }
        if (($data['type'] ?? null) !== $type) {
            throw new RuntimeException('Nieprawidłowy typ operacji.');
        }
        if (! $challenge || ! hash_equals($challenge, (string) ($data['challenge'] ?? ''))) {
            throw new RuntimeException('Wyzwanie wygasło albo jest nieprawidłowe — spróbuj ponownie.');
        }
        if (($data['origin'] ?? null) !== $this->origin()) {
            throw new RuntimeException('Klucz użyto na innej stronie niż ta.');
        }

        return $data;
    }

    /** authenticatorData: skrót rpId, flagi, licznik, (przy rejestracji) dane klucza. */
    private function authData(string $authData, bool $attested): array
    {
        if (strlen($authData) < 37) {
            throw new RuntimeException('Za krótkie dane urządzenia.');
        }
        if (! hash_equals(hash('sha256', $this->rpId(), true), substr($authData, 0, 32))) {
            throw new RuntimeException('Klucz należy do innej domeny.');
        }
        $flags = ord($authData[32]);
        if (! ($flags & 0x01)) {
            throw new RuntimeException('Nie potwierdzono obecności użytkownika (dotknij klucza).');
        }
        if (! ($flags & 0x04)) {
            throw new RuntimeException('Urządzenie nie zweryfikowało użytkownika (PIN lub biometria są wymagane).');
        }
        $signCount = unpack('N', substr($authData, 33, 4))[1];
        $out = ['sign_count' => $signCount];

        if ($attested) {
            if (! ($flags & 0x40)) {
                throw new RuntimeException('Brak danych klucza w odpowiedzi urządzenia.');
            }
            $len = unpack('n', substr($authData, 53, 2))[1];
            $out['credential_id'] = substr($authData, 55, $len);
            $offset = 55 + $len;
            $out['cose_key'] = Cbor::decode($authData, $offset);
        }

        return $out;
    }

    /** Klucz COSE → [klucz do weryfikacji, algorytm]. */
    private function coseToKey(array $cose): array
    {
        $kty = $cose[1] ?? null;
        $alg = $cose[3] ?? null;

        if ($kty === 2 && $alg === -7 && ($cose[-1] ?? null) === 1) { // EC2 P-256
            $point = "\x04".$cose[-2].$cose[-3];
            $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$point;

            return [self::pem($der), -7];
        }
        if ($kty === 3 && $alg === -257) { // RSA
            $der = self::rsaSpki($cose[-1], $cose[-2]);

            return [self::pem($der), -257];
        }
        if ($kty === 1 && $alg === -8 && ($cose[-1] ?? null) === 6) { // OKP Ed25519
            return [base64_encode($cose[-2]), -8];
        }

        throw new RuntimeException('Ten typ klucza nie jest obsługiwany — użyj innego urządzenia.');
    }

    private function verifySignature(WebauthnCredential $credential, string $data, string $signature): bool
    {
        if ($credential->algorithm === -8) {
            return function_exists('sodium_crypto_sign_verify_detached')
                && sodium_crypto_sign_verify_detached($signature, $data, base64_decode($credential->public_key));
        }

        return openssl_verify($data, $signature, $credential->public_key, OPENSSL_ALGO_SHA256) === 1;
    }

    private static function pem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private static function rsaSpki(string $n, string $e): string
    {
        $int = fn (string $v) => "\x02".self::derLen(strlen($v = (ord($v[0]) > 0x7F ? "\x00".$v : $v))).$v;
        $rsaKey = "\x30".self::derLen(strlen($body = $int($n).$int($e))).$body;
        $bitString = "\x03".self::derLen(strlen($rsaKey) + 1)."\x00".$rsaKey;
        $algId = hex2bin('300d06092a864886f70d0101010500');

        return "\x30".self::derLen(strlen($algId.$bitString)).$algId.$bitString;
    }

    private static function derLen(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        $bytes = ltrim(pack('N', $len), "\x00");

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    public static function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function unb64(string $b64): string
    {
        $decoded = base64_decode(strtr($b64, '-_', '+/').str_repeat('=', (4 - strlen($b64) % 4) % 4), true);
        if ($decoded === false) {
            throw new RuntimeException('Nieprawidłowe dane w odpowiedzi przeglądarki.');
        }

        return $decoded;
    }
}
