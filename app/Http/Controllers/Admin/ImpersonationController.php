<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Przełączanie na konto redaktora (jak „Switch user” w TYPO3): administrator jednym kliknięciem widzi panel tak,
 * jak widzi go dana osoba — bez jej hasła — żeby zdiagnozować zgłoszony problem z dostępem. W sesji zostaje
 * `impersonator_id`, pasek na dole ekranu przypomina o przełączeniu i daje „Wróć do swojego konta”, a początek
 * i koniec trafiają do dziennika zdarzeń (kto, na kogo, IP). Tylko na konta nie-administratorów, bez łańcuchów
 * przełączeń; w trakcie nie da się zmieniać hasła ani zabezpieczeń konta (App\Http\Middleware\GuardAccountSession).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    /** Trwa przełączanie konta — dziennik logowań (AppServiceProvider) pomija wtedy zwykłe „Logowanie”. */
    public static bool $switching = false;

    private static function switchTo(User $user): void
    {
        self::$switching = true;
        try {
            Auth::guard('web')->login($user);
        } finally {
            self::$switching = false;
        }
    }

    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403);
        abort_if($request->session()->has(self::SESSION_KEY), 403, 'Najpierw wróć do swojego konta.');

        $refusal = match (true) {
            $admin->is($user) => 'To Twoje konto.',
            $user->isAdmin() || (bool) $user->is_super_admin => 'Na konto innego administratora nie można się przełączyć.',
            ! $user->isAccountActive() => 'To konto jest poza okresem ważności — nie da się na nie przełączyć.',
            default => null,
        };
        if ($refusal) {
            return back()->with('error', $refusal);
        }

        $session = $request->session();
        $session->put([
            self::SESSION_KEY => $admin->id,
            'impersonator_login_method' => $session->get('login_method'),
            'impersonation_started_at' => now()->toIso8601String(),
        ]);
        self::switchTo($user);
        $session->put('login_method', 'impersonation');

        activity('auth')->causedBy($admin)->performedOn($user)
            ->withProperties(['label' => $user->name])
            ->event('impersonation_started')
            ->log('Przełączenie na konto: '.$user->name);

        return redirect()->route('admin.dashboard')->with('status', "Pracujesz teraz jako „{$user->name}”. Widzisz panel tak, jak ta osoba.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $session = $request->session();
        $adminId = $session->pull(self::SESSION_KEY);
        abort_unless($adminId, 404);

        $impersonated = $request->user();
        $admin = User::find($adminId);
        $method = $session->pull('impersonator_login_method');
        $session->forget('impersonation_started_at');

        // Konto administratora zniknęło albo straciło uprawnienia w międzyczasie — kończymy sesję zupełnie.
        if (! $admin || ! $admin->isAdmin()) {
            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login');
        }

        self::switchTo($admin);
        $session->put('login_method', $method);

        activity('auth')->causedBy($admin)->performedOn($impersonated)
            ->withProperties(['label' => $impersonated?->name])
            ->event('impersonation_stopped')
            ->log('Powrót z konta: '.$impersonated?->name);

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Jesteś z powrotem na swoim koncie.');
    }

    /** Powrót administratora na jego konto bez żądania „Wróć” (np. konto osoby wygasło w trakcie). */
    public static function restoreAdmin(Request $request): ?User
    {
        $session = $request->session();
        $admin = User::find($session->pull(self::SESSION_KEY));
        $method = $session->pull('impersonator_login_method');
        $session->forget('impersonation_started_at');
        if (! $admin || ! $admin->isAdmin()) {
            return null;
        }
        $impersonated = $request->user();
        self::switchTo($admin);
        $session->put('login_method', $method);
        activity('auth')->causedBy($admin)->performedOn($impersonated)->withProperties(['label' => $impersonated?->name])
            ->event('impersonation_stopped')->log('Powrót z konta: '.$impersonated?->name);

        return $admin;
    }
}
