<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserGroupController extends Controller
{
    public function index()
    {
        $groups = UserGroup::withCount('users')->orderBy('name')->get();

        return view('admin.user-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.user-groups.form', ['group' => new UserGroup]);
    }

    public function store(Request $request)
    {
        UserGroup::create($this->validated($request));

        return redirect()->route('admin.grupy.index')->with('status', 'Grupa została utworzona.');
    }

    public function edit(UserGroup $group)
    {
        return view('admin.user-groups.form', compact('group'));
    }

    public function update(Request $request, UserGroup $group)
    {
        $group->update($this->validated($request));

        return redirect()->route('admin.grupy.index')->with('status', 'Grupa została zaktualizowana.');
    }

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
