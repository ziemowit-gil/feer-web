<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Profil zalogowanego użytkownika panelu: edycja danych osobowych,
 * preferencje powiadomień e-mail i usunięcie własnego konta.
 *
 * Metody: edit(), update(), updateNotifications(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ProfileController extends Controller
{
    /** Wyświetla formularz edycji profilu zalogowanego użytkownika. */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /** Zapisuje zaktualizowane dane profilu; resetuje weryfikację e-mail, gdy adres się zmienił. */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /** Zapisz preferencje powiadomień e-mail. */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->user()->update([
            'notification_preferences' => [
                'task_assigned' => $request->boolean('task_assigned'),
                'task_due_soon' => $request->boolean('task_due_soon'),
            ],
        ]);

        return Redirect::route('profile.edit')->with('status', 'notifications-updated');
    }

    /** Usuwa konto zalogowanego użytkownika po weryfikacji hasła i inwaliduje sesję. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
