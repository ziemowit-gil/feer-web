<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Panel admin: CRUD użytkowników panelu z przypisywaniem ról i grup
 * oraz odłączaniem konta Microsoft 365.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), unlinkMicrosoft().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class UserController extends Controller
{
    /** Wyświetla listę użytkowników panelu z informacją o grupie. */
    public function index()
    {
        $users = User::with('group')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    /** Wyświetla formularz tworzenia nowego użytkownika. */
    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'groups' => UserGroup::orderBy('name')->get()]);
    }

    /** Tworzy nowego użytkownika z haszowanym hasłem i weryfikacją e-mail. */
    public function store(Request $request)
    {
        $isBipEditor = $request->input('role') === User::ROLE_BIP_EDITOR;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', $isBipEditor ? 'regex:/^\S+\s+\S+/' : 'min:1'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'user_group_id' => ['nullable', Rule::exists(UserGroup::class, 'id')],
            'password' => ['required', 'string', 'min:8'],
            'local_login_allowed' => ['sometimes', 'boolean'],
        ], [
            'name.regex' => 'Edytor BIP musi mieć podane imię i nazwisko (dwa wyrazy).',
        ]);

        if (in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_BIP_EDITOR], true)) {
            $data['user_group_id'] = null;
        }

        $data['local_login_allowed'] = $request->boolean('local_login_allowed');
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        User::create($data);

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został utworzony.');
    }

    /** Wyświetla formularz edycji użytkownika. */
    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'groups' => UserGroup::orderBy('name')->get()]);
    }

    /** Aktualizuje dane użytkownika; puste hasło = bez zmian. */
    public function update(Request $request, User $user)
    {
        $isBipEditor = $request->input('role') === User::ROLE_BIP_EDITOR;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', $isBipEditor ? 'regex:/^\S+\s+\S+/' : 'min:1'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'user_group_id' => ['nullable', Rule::exists(UserGroup::class, 'id')],
            'password' => ['nullable', 'string', 'min:8'],
            'local_login_allowed' => ['sometimes', 'boolean'],
        ], [
            'name.regex' => 'Edytor BIP musi mieć podane imię i nazwisko (dwa wyrazy).',
        ]);

        if (in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_BIP_EDITOR], true)) {
            $data['user_group_id'] = null;
        }

        $data['local_login_allowed'] = $request->boolean('local_login_allowed');

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został zaktualizowany.');
    }

    /** Usuwa użytkownika; blokuje samousunięcie. */
    public function destroy(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 403, 'Nie możesz usunąć własnego konta.');

        $user->delete();

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został usunięty.');
    }

    /** Odłącza konto Microsoft 365 od użytkownika (usuwa microsoft_id i avatar). */
    public function unlinkMicrosoft(User $user)
    {
        $user->forceFill(['microsoft_id' => null, 'avatar' => null])->save();

        return redirect()->back()->with('status', "Konto Microsoft 365 zostało odłączone od użytkownika „{$user->name}\".");
    }
}
