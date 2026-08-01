<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('group')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'groups' => UserGroup::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
            'user_group_id' => ['nullable', Rule::exists(UserGroup::class, 'id')],
            'password' => ['required', 'string', 'min:8'],
            'local_login_allowed' => ['sometimes', 'boolean'],
        ]);

        if ($data['role'] === User::ROLE_ADMIN) {
            $data['user_group_id'] = null;
        }

        $data['local_login_allowed'] = $request->boolean('local_login_allowed');
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        User::create($data);

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został utworzony.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'groups' => UserGroup::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EDITOR])],
            'user_group_id' => ['nullable', Rule::exists(UserGroup::class, 'id')],
            'password' => ['nullable', 'string', 'min:8'],
            'local_login_allowed' => ['sometimes', 'boolean'],
        ]);

        if ($data['role'] === User::ROLE_ADMIN) {
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

    public function destroy(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 403, 'Nie możesz usunąć własnego konta.');

        $user->delete();

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został usunięty.');
    }

    public function unlinkMicrosoft(User $user)
    {
        $user->forceFill(['microsoft_id' => null, 'avatar' => null])->save();

        return redirect()->back()->with('status', "Konto Microsoft 365 zostało odłączone od użytkownika „{$user->name}\".");
    }
}
