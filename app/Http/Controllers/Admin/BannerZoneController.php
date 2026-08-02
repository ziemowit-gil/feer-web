<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Panel admin: zarządzanie strefami banerów — miejscami wyświetlania banerów w serwisie.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BannerZoneController extends Controller
{
    /** Wyświetla listę stref banerów z liczbą przypisanych banerów. */
    public function index(): View
    {
        $zones = BannerZone::withCount('banners')->orderBy('label')->get();

        return view('admin.banner-zones.index', compact('zones'));
    }

    /** Wyświetla formularz tworzenia nowej strefy banerów. */
    public function create(): View
    {
        return view('admin.banner-zones.form', ['zone' => new BannerZone]);
    }

    /** Zapisuje nową strefę banerów. */
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

    /** Wyświetla formularz edycji strefy banerów. */
    public function edit(BannerZone $strefaBanneru): View
    {
        return view('admin.banner-zones.form', ['zone' => $strefaBanneru]);
    }

    /** Aktualizuje strefę banerów i czyści jej cache. */
    public function update(Request $request, BannerZone $strefaBanneru): RedirectResponse
    {
        $request->validate([
            'slug'           => ['required', 'string', 'max:64', 'unique:banner_zones,slug,' . $strefaBanneru->id, 'regex:/^[a-z0-9_]+$/'],
            'label'          => 'required|string|max:128',
            'description'    => 'nullable|string|max:512',
            'max_concurrent' => 'required|integer|min:1|max:10',
        ]);

        $strefaBanneru->update($request->only('slug', 'label', 'description', 'max_concurrent'));
        $this->flushZoneCache($strefaBanneru->slug);

        return redirect()->route('admin.strefy-bannerow.index')
            ->with('status', 'Strefa „' . $strefaBanneru->label . '" została zaktualizowana.');
    }

    /** Usuwa strefę banerów i czyści jej cache. */
    public function destroy(BannerZone $strefaBanneru): RedirectResponse
    {
        $slug = $strefaBanneru->slug;
        $strefaBanneru->delete();
        $this->flushZoneCache($slug);

        return redirect()->route('admin.strefy-bannerow.index')
            ->with('status', 'Strefa została usunięta.');
    }

    /** Wyczyść zbuforowane dane strefy (lista bannerów + max_concurrent). */
    private function flushZoneCache(string $slug): void
    {
        Cache::forget("banner_zone_{$slug}");
        Cache::forget("banner_zone_max_{$slug}");
    }
}
