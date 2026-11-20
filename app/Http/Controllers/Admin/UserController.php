<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\RoleMenuPreview;
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
    /** Macierz uprawnień: kto ma dostęp do których modułów panelu oraz jaki zakres treści. */
    public function permissions()
    {
        $users = User::with(User::supportsExtraGroups() ? ['group', 'extraGroups'] : ['group'])->orderBy('name')->get();
        $categories = \App\Models\Category::pluck('name', 'id');

        return view('admin.users.permissions', [
            'users' => $users,
            'modules' => \App\Models\SiteSetting::MODULES,
            'categories' => $categories,
        ]);
    }

    /**
     * Podgląd menu panelu dla roli, grupy edytora albo użytkownika (patrz RoleMenuPreview): co widzi i czego nie widzi.
     * Tylko odczyt, bez logowania się na cudze konto.
     */
    public function menuPreview(Request $request)
    {
        $choice = (string) $request->query('jako', '');
        $subject = RoleMenuPreview::subject($choice);
        abort_if($choice !== '' && $subject === null, 404);

        return view('admin.users.menu-preview', [
            'options' => RoleMenuPreview::options(),
            'choice' => $choice,
            'subject' => $subject,
            'title' => $subject ? RoleMenuPreview::title($subject) : null,
            'preview' => $subject ? RoleMenuPreview::build($subject) : null,
            'members' => $subject ? RoleMenuPreview::members($subject) : collect(),
            'siteName' => SiteSetting::current()->site_name,
        ]);
    }

    /** Wyświetla listę użytkowników panelu z informacją o grupie. */
    public function index()
    {
        $users = User::with(User::supportsExtraGroups() ? ['group', 'extraGroups'] : ['group'])->orderBy('name')->get();

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
            // Gałęzie stron tej osoby (jak „DB mounts” w TYPO3) — tylko Edytor i Redaktor treści.
            'page_mounts' => ['sometimes', 'array'],
            'page_mounts.*' => ['integer', 'exists:pages,id'],
            // Dodatkowe grupy (uprawnienia się sumują) i ważność konta od–do (czas polski).
            'extra_group_ids' => ['sometimes', 'array'],
            'extra_group_ids.*' => ['integer', Rule::exists(UserGroup::class, 'id')],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after:active_from'],
        ], [
            'active_until.after' => 'Koniec ważności konta musi być późniejszy niż jego początek.',
            'name.regex' => 'Edytor BIP musi mieć podane imię i nazwisko (dwa wyrazy).',
        ]);

        if (in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_BIP_EDITOR], true)) {
            $data['user_group_id'] = null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'page_mounts')) {
            $data['page_mounts'] = in_array($data['role'], User::PAGE_MOUNT_ROLES, true)
                ? (array_values(array_map('intval', $data['page_mounts'] ?? [])) ?: null)
                : null;
        } else {
            unset($data['page_mounts']);
        }

        $data['local_login_allowed'] = $request->boolean('local_login_allowed');
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        $extra = $this->accountFields($request, $data);
        $user = User::create($data);
        $this->syncExtraGroups($user, $extra);

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
            // Schemat kolorów panelu tej osoby (to samo co Mój profil → Wygląd panelu).
            'admin_scheme' => ['nullable', Rule::in(array_keys(User::ADMIN_SCHEMES))],
            // Gałęzie stron tej osoby (jak „DB mounts” w TYPO3) — tylko Edytor i Redaktor treści.
            'page_mounts' => ['sometimes', 'array'],
            'page_mounts.*' => ['integer', 'exists:pages,id'],
            // Dodatkowe grupy (uprawnienia się sumują) i ważność konta od–do (czas polski).
            'extra_group_ids' => ['sometimes', 'array'],
            'extra_group_ids.*' => ['integer', Rule::exists(UserGroup::class, 'id')],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after:active_from'],
        ], [
            'active_until.after' => 'Koniec ważności konta musi być późniejszy niż jego początek.',
            'name.regex' => 'Edytor BIP musi mieć podane imię i nazwisko (dwa wyrazy).',
        ]);

        if (in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_BIP_EDITOR], true)) {
            $data['user_group_id'] = null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'page_mounts')) {
            $data['page_mounts'] = in_array($data['role'], User::PAGE_MOUNT_ROLES, true)
                ? (array_values(array_map('intval', $data['page_mounts'] ?? [])) ?: null)
                : null;
        } else {
            unset($data['page_mounts']);
        }

        $data['local_login_allowed'] = $request->boolean('local_login_allowed');

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $extra = $this->accountFields($request, $data, $user);
        $user->update($data);
        $this->syncExtraGroups($user, $extra);

        return redirect()->route('admin.uzytkownicy.index')->with('status', 'Użytkownik został zaktualizowany.');
    }

    /**
     * Ważność konta (od–do, wpisana w czasie polskim) i dodatkowe grupy z formularza. Zwraca numery dodatkowych grup
     * do zapisania po zapisie osoby; kolumny dodaje migracja 2026_11_20_120000 (kod bywa na serwerze przed nią).
     *
     * @return array<int>|null
     */
    private function accountFields(Request $request, array &$data, ?User $user = null): ?array
    {
        $extra = $data['role'] === User::ROLE_EDITOR
            ? array_values(array_diff(array_map('intval', $data['extra_group_ids'] ?? []), [(int) ($data['user_group_id'] ?? 0)]))
            : [];
        unset($data['extra_group_ids']);

        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'active_until')) {
            unset($data['active_from'], $data['active_until']);

            return User::supportsExtraGroups() ? $extra : null;
        }
        foreach (['active_from', 'active_until'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? \Carbon\Carbon::parse($data[$key], 'Europe/Warsaw')->utc() : null;
        }
        if ($user && $request->user()->is($user) && $data['active_until'] && $data['active_until']->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['active_until' => 'Nie ustawisz wygaśnięcia własnego konta w przeszłości — to od razu zablokowałoby Ci dostęp.']);
        }
        // Przedłużone albo przywrócone konto znów podlega wygaszaniu przez cron (users:wygas).
        if (! $data['active_until'] || $data['active_until']->isFuture()) {
            $data['expiry_handled_at'] = null;
        }

        return User::supportsExtraGroups() ? $extra : null;
    }

    /** Zapisuje dodatkowe grupy osoby; zmianę uprawnień odnotowuje w dzienniku zdarzeń (stare i nowe grupy). */
    private function syncExtraGroups(User $user, ?array $ids): void
    {
        if ($ids === null) {
            return;
        }
        $before = $user->extraGroups()->orderBy('name')->pluck('name')->all();
        $user->extraGroups()->sync($ids);
        $after = $user->extraGroups()->orderBy('name')->pluck('name')->all();
        if ($before !== $after) {
            activity('cms')->causedBy(auth()->user())->performedOn($user)
                ->withProperties(['label' => $user->name, 'old' => ['dodatkowe_grupy' => implode(', ', $before) ?: '—'], 'attributes' => ['dodatkowe_grupy' => implode(', ', $after) ?: '—']])
                ->event('groups_changed')->log('User groups_changed');
        }
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

        activity('cms')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperty('label', $user->name)
            ->event('microsoft_unlinked')
            ->log('User microsoft_unlinked');

        return redirect()->back()->with('status', "Konto Microsoft 365 zostało odłączone od użytkownika „{$user->name}\".");
    }
}
