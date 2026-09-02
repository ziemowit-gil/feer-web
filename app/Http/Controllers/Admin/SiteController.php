<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Zakładanie i podstawowa administracja sub-witrynami sieci (np. „Ośrodek"
 * prowadzony przez federację) — każda sub-witryna to kolejny wiersz
 * `SiteSetting` (patrz SiteSetting::current(), BelongsToSite). Pełna edycja
 * ustawień/treści danej witryny odbywa się przez istniejące ekrany admina po
 * przełączeniu się na nią (Admin\ActiveSiteController::switch) — ten
 * kontroler odpowiada tylko za pola założycielskie: nazwę, slug, domenę
 * i rodzica.
 */
class SiteController extends Controller
{
    public function index()
    {
        $mainSite = SiteSetting::whereNull('parent_site_id')->orderBy('id')->first();
        $sites = SiteSetting::with('subsites')->orderBy('id')->get();

        return view('admin.sites.index', compact('mainSite', 'sites'));
    }

    public function create()
    {
        return view('admin.sites.form', ['site' => new SiteSetting]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['parent_site_id'] = SiteSetting::current()->id;
        $data['site_template'] = SiteSetting::current()->site_template;

        $site = SiteSetting::create($data);

        if ($request->hasFile('logo')) {
            $site->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()->route('admin.witryny.index')->with('status', 'Witryna „'.$site->site_name.'" została założona.');
    }

    public function edit(SiteSetting $site)
    {
        return view('admin.sites.form', compact('site'));
    }

    public function update(Request $request, SiteSetting $site)
    {
        $data = $this->validated($request, $site);
        $site->update($data);

        if ($request->hasFile('logo')) {
            $site->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()->route('admin.witryny.index')->with('status', 'Zapisano zmiany witryny „'.$site->site_name.'".');
    }

    public function destroy(SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');

        $tables = ['news', 'pages', 'gallery_images', 'events', 'partners', 'quick_actions', 'polls', 'hero_slides'];
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\DB::table($table)->where('site_id', $site->id)->exists()) {
                return back()->with('error', 'Nie można usunąć witryny „'.$site->site_name.'" — ma jeszcze przypisaną treść. Przenieś lub usuń ją najpierw.');
            }
        }

        $site->delete();

        return redirect()->route('admin.witryny.index')->with('status', 'Witryna została usunięta.');
    }

    private function validated(Request $request, ?SiteSetting $site = null): array
    {
        $reserved = array_merge(Page::RESERVED_SLUGS, ['site']);

        return $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::notIn($reserved),
                Rule::unique('site_settings', 'slug')->ignore($site?->id),
                Rule::unique('pages', 'slug'),
            ],
            'domain' => [
                'nullable', 'string', 'max:255',
                Rule::unique('site_settings', 'domain')->ignore($site?->id),
            ],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
