<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel admin: zarządzanie grupami użytkowników i ich uprawnieniami do modułów panelu.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class UserGroupController extends Controller
{
    /** Wyświetla listę grup użytkowników z liczbą przypisanych osób. */
    public function index()
    {
        $groups = UserGroup::withCount('users')->orderBy('name')->get();

        return view('admin.user-groups.index', compact('groups'));
    }

    /** Wyświetla formularz tworzenia nowej grupy użytkowników. */
    public function create()
    {
        return view('admin.user-groups.form', ['group' => new UserGroup]);
    }

    /** Tworzy nową grupę użytkowników z wybranymi uprawnieniami do modułów. */
    public function store(Request $request)
    {
        UserGroup::create($this->validated($request));

        return redirect()->route('admin.grupy.index')->with('status', 'Grupa została utworzona.');
    }

    /** Wyświetla formularz edycji grupy użytkowników. */
    public function edit(UserGroup $group)
    {
        return view('admin.user-groups.form', compact('group'));
    }

    /** Aktualizuje uprawnienia do modułów i flagę zatwierdzania treści grupy. */
    public function update(Request $request, UserGroup $group)
    {
        $group->update($this->validated($request));

        return redirect()->route('admin.grupy.index')->with('status', 'Grupa została zaktualizowana.');
    }

    /** Usuwa grupę, odłączając jej użytkowników (user_group_id → null). */
    public function destroy(UserGroup $group)
    {
        $group->users()->update(['user_group_id' => null]);
        $group->delete();

        return redirect()->route('admin.grupy.index')->with('status', 'Grupa została usunięta.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['string', Rule::in(array_keys(SiteSetting::MODULES))],
            'can_approve' => ['sometimes', 'boolean'],
        ]);

        $data['modules'] = $data['modules'] ?? [];
        $data['can_approve'] = $request->boolean('can_approve');

        return $data;
    }
}
