<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Profil zalogowanego użytkownika panelu: edycja danych osobowych,
 * preferencje powiadomień e-mail i usunięcie własnego konta.
 *
 * Metody: edit(), update(), updateNotifications(), updateEditor(), dismissEditorPrompt(), destroy().
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

    /** Zapisz domyślny edytor treści (z profilu albo z okna wyboru po zalogowaniu). */
    /** Mój profil → Wygląd panelu: schemat kolorów (User::ADMIN_SCHEMES). */
    public function updateScheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_scheme' => ['required', Rule::in(array_keys(User::ADMIN_SCHEMES))],
        ], [
            'admin_scheme.required' => 'Wybierz schemat kolorów.',
            'admin_scheme.in' => 'Wybierz schemat kolorów z listy.',
        ]);

        $request->user()->update($data);

        return Redirect::to(route('profile.edit').'#wyglad-panelu')
            ->with('status', 'Schemat kolorów panelu: '.User::ADMIN_SCHEMES[$data['admin_scheme']][0].'.');
    }

    /** Mój profil → Wygląd panelu: styl menu bocznego (User::ADMIN_NAV_STYLES). */
    public function updateNavStyle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_nav_style' => ['required', Rule::in(array_keys(User::ADMIN_NAV_STYLES))],
        ], [
            'admin_nav_style.required' => 'Wybierz styl menu.',
            'admin_nav_style.in' => 'Wybierz styl menu z listy.',
        ]);

        $request->user()->update($data);

        return Redirect::to(route('profile.edit').'#menu-panelu')
            ->with('status', 'Styl menu panelu: '.User::ADMIN_NAV_STYLES[$data['admin_nav_style']][0].'.');
    }

    public function updateEditor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_editor' => ['required', Rule::in(array_keys(User::EDITORS))],
        ], [
            'default_editor.required' => 'Wybierz domyślny edytor.',
            'default_editor.in' => 'Wybierz domyślny edytor z listy.',
        ]);

        $request->user()->update($data);

        $message = 'Domyślny edytor: '.User::EDITORS[$data['default_editor']].'. Możesz to zmienić w Mój profil.';

        return $request->boolean('from_prompt')
            ? Redirect::back()->with('status', $message)
            : Redirect::to(route('profile.edit').'#domyslny-edytor')->with('status', $message);
    }

    /** „Zdecyduję później” — nie pytaj o edytor do końca tej sesji (zapyta po następnym zalogowaniu). */
    public function dismissEditorPrompt(Request $request): RedirectResponse
    {
        $request->session()->put('default_editor_prompt_dismissed', true);

        return Redirect::back();
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
