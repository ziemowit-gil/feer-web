<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel admin: CRUD organizacji członkowskich federacji (katalog + wizytówki).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class OrganizationController extends Controller
{
    public function __construct()
    {
        abort_unless(\App\Models\SiteSetting::current()->site_template === 'federation', 404);
    }

    public function index()
    {
        $organizations = Organization::orderBy('order')->orderBy('name')->get();

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.form', ['organization' => new Organization]);
    }

    public function store(Request $request)
    {
        Organization::create($this->validated($request));

        return redirect()->route('admin.organizacje.index')->with('status', 'Organizacja została dodana.');
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.form', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $organization->update($this->validated($request, $organization));

        return redirect()->route('admin.organizacje.index')->with('status', 'Organizacja została zaktualizowana.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return redirect()->route('admin.organizacje.index')->with('status', 'Organizacja została usunięta.');
    }

    private function validated(Request $request, ?Organization $organization = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization?->id)],
            'town' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Organization::TYPES)],
            'spheres' => ['nullable', 'array'],
            'spheres.*' => [Rule::in(array_keys(Organization::SPHERE_ICONS))],
            'description' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'login' => ['required', 'string', 'max:255', Rule::unique('organizations', 'login')->ignore($organization?->id)],
            'password' => [$organization?->exists ? 'nullable' : 'required', 'string', 'min:8'],
            'is_test' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['is_test'] = $request->boolean('is_test');
        $data['order'] = $data['order'] ?? 0;
        $data['spheres'] = array_values(array_unique($data['spheres'] ?? []));

        // Hasło ustawiane tylko, gdy podane — puste pole przy edycji zachowuje obecne.
        if (blank($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
