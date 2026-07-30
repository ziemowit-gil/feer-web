<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerZoneController extends Controller
{
    public function index(): View
    {
        $zones = BannerZone::withCount('banners')->orderBy('label')->get();

        return view('admin.banner-zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('admin.banner-zones.form', ['zone' => new BannerZone]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'slug'            => ['required', 'string', 'max:64', 'unique:banner_zones,slug', 'regex:/^[a-z0-9_]+$/'],
            'label'           => 'required|string|max:128',
            'description'     => 'nullable|string|max:512',
            'max_concurrent'  => 'required|integer|min:1|max:10',
        ]);

        BannerZone::create($request->only('slug', 'label', 'description', 'max_concurrent'));

        return redirect()->route('admin.strefy-bannerow.index')
            ->with('status', 'Strefa „' . $request->label . '" została dodana.');
    }

    public function edit(BannerZone $strefaBanneru): View
    {
        return view('admin.banner-zones.form', ['zone' => $strefaBanneru]);
    }

    public function update(Request $request, BannerZone $strefaBanneru): RedirectResponse
    {
        $request->validate([
            'slug'           => ['required', 'string', 'max:64', 'unique:banner_zones,slug,' . $strefaBanneru->id, 'regex:/^[a-z0-9_]+$/'],
            'label'          => 'required|string|max:128',
            'description'    => 'nullable|string|max:512',
            'max_concurrent' => 'required|integer|min:1|max:10',
        ]);

        $strefaBanneru->update($request->only('slug', 'label', 'description', 'max_concurrent'));

        return redirect()->route('admin.strefy-bannerow.index')
            ->with('status', 'Strefa „' . $strefaBanneru->label . '" została zaktualizowana.');
    }

    public function destroy(BannerZone $strefaBanneru): RedirectResponse
    {
        $strefaBanneru->delete();

        return redirect()->route('admin.strefy-bannerow.index')
            ->with('status', 'Strefa została usunięta.');
    }
}
