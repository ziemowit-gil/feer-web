<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\WebauthnCredential;
use App\Services\Auth\WebAuthn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klucze dostępu (passkeys / WebAuthn): dodawanie i usuwanie w profilu, logowanie bez hasła.
 *
 * Metody: options(), store(), destroy(), loginOptions(), login().
 */
class PasskeyController extends Controller
{
    /** Opcje rejestracji nowego klucza (profil). */
    public function options(Request $request, WebAuthn $webauthn): JsonResponse
    {
        return response()->json($webauthn->creationOptions($request->user()));
    }

    /** Zapisuje klucz po potwierdzeniu na urządzeniu. */
    public function store(Request $request, WebAuthn $webauthn): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'credential' => ['required', 'array'],
        ]);

        try {
            $credential = $webauthn->register($request->user(), $data['credential'], (string) ($data['name'] ?? ''));
        } catch (Throwable $e) {
            Log::info('Rejestracja klucza dostępu nie powiodła się.', ['user' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        activity('auth')->causedBy($request->user())->event('passkey_added')->withProperty('label', $credential->name)->log('Dodano klucz dostępu');
        session()->flash('status', 'passkey-added');

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, WebauthnCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);
        $credential->delete();
        activity('auth')->causedBy($request->user())->event('passkey_removed')->withProperty('label', $credential->name)->log('Usunięto klucz dostępu');

        return back()->with('status', 'passkey-removed');
    }

    /** Opcje logowania kluczem (strona logowania). */
    public function loginOptions(WebAuthn $webauthn): JsonResponse
    {
        abort_unless(SiteSetting::current()->passkeyLoginEnabled(), 404);

        return response()->json($webauthn->requestOptions());
    }

    public function login(Request $request, WebAuthn $webauthn): JsonResponse
    {
        abort_unless(SiteSetting::current()->passkeyLoginEnabled(), 404);
        $data = $request->validate(['credential' => ['required', 'array']]);

        try {
            $user = $webauthn->authenticate($data['credential']);
        } catch (Throwable $e) {
            Log::info('Logowanie kluczem dostępu nie powiodło się.', ['ip' => $request->ip(), 'error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        // Klucz dostępu to dwa składniki naraz (urządzenie + PIN/biometria) — nie wymagamy osobnego 2FA.
        $request->session()->put('login_method', 'passkey');

        return response()->json(['redirect' => redirect()->intended(route('dashboard', absolute: false))->getTargetUrl()]);
    }
}
