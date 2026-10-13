<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * SSO przez OpenID Connect — dla organizacji z własnym dostawcą tożsamości (Keycloak, Okta,
 * Microsoft Entra ID, Google Workspace, Authentik…). Konfiguracja w Ustawienia → Logowanie:
 * adres dostawcy (issuer), identyfikator i sekret klienta, zakresy.
 *
 * Przepływ „authorization code” z PKCE (S256), parametrem state i nonce. Tożsamość bierzemy
 * z endpointu userinfo dostawcy (połączenie serwer–serwer z tokenem z wymiany kodu), a wpuszczamy
 * wyłącznie konta istniejące w panelu — dopasowanie po identyfikatorze SSO (sub) albo e-mailu.
 */
class OidcAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $settings = SiteSetting::current();
        if (! $settings->ssoLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie SSO jest obecnie wyłączone.');
        }

        try {
            $config = $this->discovery($settings);
        } catch (Throwable $e) {
            Log::warning('SSO: nie udało się pobrać konfiguracji dostawcy.', ['error' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Dostawca logowania SSO nie odpowiada. Spróbuj za chwilę.');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $request->session()->put('sso', compact('state', 'nonce', 'verifier'));

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $settings->sso_client_id,
            'redirect_uri' => route('auth.sso.callback'),
            'scope' => trim((string) $settings->sso_scopes) ?: 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away($config['authorization_endpoint'].(str_contains($config['authorization_endpoint'], '?') ? '&' : '?').$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $settings = SiteSetting::current();
        if (! $settings->ssoLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie SSO jest obecnie wyłączone.');
        }

        $saved = $request->session()->pull('sso', []);
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Dostawca SSO odmówił logowania: '.Str::limit((string) $request->input('error_description', $request->input('error')), 160));
        }
        if (! ($saved['state'] ?? null) || ! hash_equals($saved['state'], (string) $request->input('state'))) {
            return redirect()->route('login')->with('error', 'Sesja logowania SSO wygasła — spróbuj ponownie.');
        }

        try {
            $config = $this->discovery($settings);
            $token = Http::asForm()->acceptJson()->timeout(15)->post($config['token_endpoint'], [
                'grant_type' => 'authorization_code',
                'code' => (string) $request->input('code'),
                'redirect_uri' => route('auth.sso.callback'),
                'client_id' => $settings->sso_client_id,
                'client_secret' => $settings->sso_client_secret,
                'code_verifier' => $saved['verifier'],
            ]);
            if (! $token->successful() || ! $token->json('access_token')) {
                throw new RuntimeException('Wymiana kodu: HTTP '.$token->status());
            }
            $this->checkNonce((string) $token->json('id_token'), $saved['nonce']);

            $info = Http::withToken($token->json('access_token'))->acceptJson()->timeout(15)->get($config['userinfo_endpoint']);
            if (! $info->successful() || ! $info->json('sub')) {
                throw new RuntimeException('Userinfo: HTTP '.$info->status());
            }
        } catch (Throwable $e) {
            Log::warning('SSO: logowanie nie powiodło się.', ['error' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Logowanie SSO nie powiodło się. Spróbuj ponownie.');
        }

        $sub = (string) $info->json('sub');
        $email = strtolower(trim((string) $info->json('email')));
        $verified = $info->json('email_verified');

        $user = User::where('oidc_sub', $sub)->first();
        // Dopasowanie po e-mailu tylko dla adresów potwierdzonych przez dostawcę (albo gdy go nie podaje — np. Entra).
        if (! $user && $email !== '' && $verified !== false) {
            $user = User::whereRaw('lower(email) = ?', [$email])->first();
        }
        if (! $user) {
            return redirect()->route('login')->with('error', 'To konto nie ma dostępu do panelu. Skontaktuj się z administratorem.');
        }

        $user->forceFill(['oidc_sub' => $sub, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        // Dostawca tożsamości ma własne MFA — logowanie SSO pomija nasze 2FA (jak Microsoft i Google).
        $request->session()->put('login_method', 'sso');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Konfiguracja dostawcy z /.well-known/openid-configuration (w pamięci podręcznej na godzinę). */
    private function discovery(SiteSetting $settings): array
    {
        $issuer = rtrim(trim((string) $settings->sso_issuer), '/');
        $url = str_contains($issuer, '/.well-known/') ? $issuer : $issuer.'/.well-known/openid-configuration';

        return Cache::remember('sso-discovery:'.md5($url), now()->addHour(), function () use ($url) {
            $res = Http::acceptJson()->timeout(10)->get($url);
            $json = $res->json();
            foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
                if (! $res->successful() || empty($json[$key]) || ! str_starts_with((string) $json[$key], 'https://')) {
                    throw new RuntimeException("Brak {$key} w konfiguracji dostawcy ({$url}).");
                }
            }

            return $json;
        });
    }

    /** Nonce z id_token (jeśli dostawca go zwraca) musi zgadzać się z tym z przekierowania. */
    private function checkNonce(string $idToken, string $nonce): void
    {
        if ($idToken === '' || substr_count($idToken, '.') !== 2) {
            return;
        }
        $payload = json_decode(base64_decode(strtr(explode('.', $idToken)[1], '-_', '+/')), true);
        if (isset($payload['nonce']) && ! hash_equals($nonce, (string) $payload['nonce'])) {
            throw new RuntimeException('Nonce w id_token się nie zgadza.');
        }
    }
}
