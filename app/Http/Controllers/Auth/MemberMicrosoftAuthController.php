<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

/**
 * Logowanie współpracowników do strefy wewnętrznej przez Microsoft 365
 * (guard „member" — oddzielony od panelu administracyjnego).
 *
 * Metody: create(), redirect(), callback(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MemberMicrosoftAuthController extends Controller
{
    /** Ekran logowania do strefy wewnętrznej. */
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect()->intended('/');
        }

        return view('auth.member-login');
    }

    /** Przekieruj do logowania Microsoft 365. */
    public function redirect(): RedirectResponse
    {
        if (! SiteSetting::current()->memberLoginEnabled()) {
            return redirect()->route('member.login')->with('error', 'Logowanie do strefy wewnętrznej jest obecnie wyłączone.');
        }

        $this->applyConfig();

        return Socialite::driver('microsoft')->redirect();
    }

    /** Obsłuż powrót z Microsoft 365 i zaloguj współpracownika. */
    public function callback(): RedirectResponse
    {
        if (! SiteSetting::current()->memberLoginEnabled()) {
            return redirect()->route('member.login')->with('error', 'Logowanie do strefy wewnętrznej jest obecnie wyłączone.');
        }

        $this->applyConfig();

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Throwable $e) {
            Log::warning('Logowanie MS365 do strefy wewnętrznej nie powiodło się.', ['exception' => $e->getMessage()]);

            return redirect()->route('member.login')->with('error', 'Logowanie przez Microsoft 365 nie powiodło się. Spróbuj ponownie.');
        }

        $settings = SiteSetting::current();
        $email = $microsoftUser->getEmail();

        $invitationToken = session()->pull('member_invitation_token');
        $hasInvitation = $invitationToken
            && MemberInvitation::findValidByToken($invitationToken)?->email === strtolower(trim((string) $email));

        if (! $settings->memberEmailAllowed($email) && ! $hasInvitation && ! MemberInvitation::emailHasValidInvitation((string) $email)) {
            return redirect()->route('member.login')->with('error', 'To konto Microsoft 365 nie ma dostępu do strefy wewnętrznej. Użyj konta z domeny organizacji.');
        }

        // Dowolne konto z dozwolonej domeny/tenanta uzyskuje dostęp — konto
        // współpracownika jest zakładane automatycznie przy pierwszym logowaniu.
        $member = Member::where('microsoft_id', $microsoftUser->getId())->first()
            ?? Member::where('email', $email)->first()
            ?? new Member;

        $member->forceFill([
            'name' => $microsoftUser->getName() ?: ($member->name ?: $email),
            'email' => $email,
            'microsoft_id' => $microsoftUser->getId(),
            'avatar' => $microsoftUser->getAvatar() ?: $member->avatar,
            'last_login_at' => now(),
        ])->save();

        // Oznacz zaproszenie jako użyte (jeśli logowanie przez link zaproszenia).
        if ($invitationToken) {
            MemberInvitation::findValidByToken($invitationToken)?->markUsed();
        }

        Auth::guard('member')->login($member, remember: true);

        request()->session()->regenerate();

        return redirect()->intended('/');
    }

    /** Wyloguj współpracownika ze strefy wewnętrznej. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();

        $request->session()->regenerate();

        return redirect('/')->with('status', 'Wylogowano ze strefy wewnętrznej.');
    }

    /** Wstrzyknij konfigurację MS365 (z osobnym adresem powrotu) do Socialite. */
    private function applyConfig(): void
    {
        config(['services.microsoft' => SiteSetting::current()->memberMicrosoftConfig()]);
    }
}
