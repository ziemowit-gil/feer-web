<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SuperAdminCertificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Logowanie głównego administratora certyfikatem klienta (.pfx) pod /super —
 * niezależna od zwykłego logowania hasłem ścieżka dostępu awaryjnego/nadrzędnego.
 *
 * Metody: create(), store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SuperAdminController extends Controller
{
    /** Wyświetla formularz logowania certyfikatem. */
    public function create(): View
    {
        return view('auth.super-login');
    }

    /** Weryfikuje przesłany certyfikat .pfx i loguje przypisanego super-admina. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'certificate' => ['required', 'file', 'max:100'],
            'passphrase' => ['required', 'string'],
        ]);

        $fingerprint = SuperAdminCertificate::fingerprintFromUpload(
            $request->file('certificate')->get(),
            $request->string('passphrase')->value()
        );

        $user = $fingerprint
            ? User::where('is_super_admin', true)->where('certificate_fingerprint', $fingerprint)->first()
            : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'certificate' => 'Certyfikat nieprawidłowy lub nierozpoznany.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
