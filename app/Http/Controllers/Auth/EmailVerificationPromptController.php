<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wyświetla ekran z prośbą o weryfikację adresu e-mail; przekierowuje dalej, gdy już zweryfikowany.
 *
 * Metody: __invoke().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EmailVerificationPromptController extends Controller
{
    /** Wyświetla ekran z prośbą o weryfikację e-mail lub przekierowuje dalej, gdy jest już zweryfikowany. */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : view('auth.verify-email');
    }
}
