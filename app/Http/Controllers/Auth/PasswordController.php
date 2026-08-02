<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Zmiana hasła przez zalogowanego użytkownika z poziomu strony profilu.
 *
 * Metody: update().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PasswordController extends Controller
{
    /** Aktualizuje hasło zalogowanego użytkownika po weryfikacji aktualnego. */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
