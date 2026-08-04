<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandAccessUser;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Panel admin: zarządzanie indywidualnymi danymi logowania do stron
 * z zasobami marki (generowanie, resetowanie, dezaktywacja).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BrandAccessUserController extends Controller
{
    /** Lista użytkowników dostępu dla danej strony. */
    public function index(Page $page)
    {
        abort_unless($page->isBrandAssets(), 404);

        $users = $page->brandAccessUsers()->orderBy('name')->get();
        $newCredentials = session('brand_new_credentials');

        return view('admin.pages.brand-access', compact('page', 'users', 'newCredentials'));
    }

    /** Tworzy nowego użytkownika z wygenerowanymi danymi logowania. */
    public function store(Request $request, Page $page)
    {
        abort_unless($page->isBrandAssets(), 404);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plainPassword = BrandAccessUser::generatePassword();
        $login = $this->uniqueLogin($page);

        BrandAccessUser::create([
            'page_id'  => $page->id,
            'name'     => $data['name'],
            'login'    => $login,
            'password' => Hash::make($plainPassword),
            'notes'    => $data['notes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.podstrony.dostep.index', $page)
            ->with('brand_new_credentials', ['login' => $login, 'password' => $plainPassword, 'name' => $data['name']]);
    }

    /** Resetuje hasło użytkownika i zwraca nowe jednorazowo w sesji. */
    public function resetPassword(Page $page, BrandAccessUser $user)
    {
        abort_unless($user->page_id === $page->id, 404);

        $plainPassword = BrandAccessUser::generatePassword();
        $user->update(['password' => Hash::make($plainPassword)]);

        return redirect()
            ->route('admin.podstrony.dostep.index', $page)
            ->with('brand_new_credentials', ['login' => $user->login, 'password' => $plainPassword, 'name' => $user->name]);
    }

    /** Przełącza aktywność konta użytkownika. */
    public function toggleActive(Page $page, BrandAccessUser $user)
    {
        abort_unless($user->page_id === $page->id, 404);

        $user->update(['is_active' => ! $user->is_active]);

        $msg = $user->is_active
            ? 'Konto "' . $user->name . '" zostało aktywowane.'
            : 'Konto "' . $user->name . '" zostało dezaktywowane.';

        return redirect()->route('admin.podstrony.dostep.index', $page)->with('status', $msg);
    }

    /** Usuwa użytkownika dostępu. */
    public function destroy(Page $page, BrandAccessUser $user)
    {
        abort_unless($user->page_id === $page->id, 404);

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.podstrony.dostep.index', $page)
            ->with('status', 'Użytkownik "' . $name . '" został usunięty.');
    }

    /** Eksportuje listę użytkowników do CSV (bez haseł). */
    public function export(Page $page)
    {
        abort_unless($page->isBrandAssets(), 404);

        $users = $page->brandAccessUsers()->orderBy('name')->get();

        $rows = [['Imię i nazwisko / nazwa', 'Login', 'Aktywny', 'Ostatnie logowanie', 'Notatki']];
        foreach ($users as $user) {
            $rows[] = [
                $user->name,
                $user->login,
                $user->is_active ? 'Tak' : 'Nie',
                $user->last_login_at?->format('Y-m-d H:i') ?? '—',
                $user->notes ?? '',
            ];
        }

        $csv = collect($rows)
            ->map(fn ($r) => implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $r)))
            ->implode("\n");

        $slug = $page->slug;

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"dostep-marka-{$slug}-" . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    private function uniqueLogin(Page $page): string
    {
        do {
            $login = BrandAccessUser::generateLogin();
        } while (BrandAccessUser::where('page_id', $page->id)->where('login', $login)->exists());

        return $login;
    }
}
