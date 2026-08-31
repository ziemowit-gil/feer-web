<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * Panel edycji własnej wizytówki — dostępny po indywidualnym zalogowaniu
 * organizacji (patrz OrganizationLoginController). Organizacja może
 * edytować wyłącznie swoje dane, nie widzi listy pozostałych.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MemberOrganizationController extends Controller
{
    public function edit(Request $request)
    {
        if (! $organization = $this->authorizedOrganization($request)) {
            return redirect()->route('organization.login');
        }

        return view('organizations.panel', compact('organization'));
    }

    public function update(Request $request)
    {
        if (! $organization = $this->authorizedOrganization($request)) {
            return redirect()->route('organization.login');
        }

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photos' => ['nullable', 'array', 'max:'.Organization::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $organization->update(collect($data)->except('photos')->all());

        $remainingSlots = Organization::MAX_PHOTOS - $organization->getMedia('photos')->count();
        foreach (array_slice($request->file('photos', []), 0, max(0, $remainingSlots)) as $photo) {
            $organization->addMedia($photo)->toMediaCollection('photos');
        }

        return redirect()->route('organization.panel.edit')
            ->with('status', 'Dane organizacji zostały zaktualizowane.');
    }

    public function destroyPhoto(Request $request, int $media)
    {
        if (! $organization = $this->authorizedOrganization($request)) {
            return redirect()->route('organization.login');
        }

        $organization->getMedia('photos')->where('id', $media)->first()?->delete();

        return redirect()->route('organization.panel.edit')
            ->with('status', 'Zdjęcie zostało usunięte.');
    }

    private function authorizedOrganization(Request $request): ?Organization
    {
        $id = $request->session()->get('organization_id');

        return $id ? Organization::find($id) : null;
    }
}
