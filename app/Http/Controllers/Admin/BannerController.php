<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::with('zones')->latest()->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $zones = BannerZone::orderBy('label')->get();

        return view('admin.banners.form', ['banner' => new Banner, 'zones' => $zones]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->prepare($request);
        $banner = Banner::create($data);

        if ($request->hasFile('image_file')) {
            $banner->image_path = $request->file('image_file')->store('banners', 'public');
            $banner->saveQuietly();
        }

        $this->syncZones($request, $banner);
        $this->flushZoneCache();

        return redirect()->route('admin.banery.index')
            ->with('status', 'Baner „' . $banner->name . '" został dodany.');
    }

    public function edit(Banner $banner): View
    {
        $banner->load('zones');
        $zones = BannerZone::orderBy('label')->get();

        return view('admin.banners.form', compact('banner', 'zones'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->prepare($request);
        $banner->fill($data);

        if ($request->hasFile('image_file')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $banner->image_path = $request->file('image_file')->store('banners', 'public');
        }

        $banner->save();
        $this->syncZones($request, $banner);
        $this->flushZoneCache();

        return redirect()->route('admin.banery.index')
            ->with('status', 'Baner „' . $banner->name . '" został zaktualizowany.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();
        $this->flushZoneCache();

        return redirect()->route('admin.banery.index')
            ->with('status', 'Baner został usunięty.');
    }

    public function toggle(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);
        $this->flushZoneCache();

        $msg = $banner->is_active ? 'aktywowany' : 'wyłączony';

        return back()->with('status', 'Baner "' . $banner->name . '" został ' . $msg . '.');
    }

    private function prepare(Request $request): array
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:image,html',
            'image_file'  => 'nullable|image|mimes:jpeg,png,gif,webp|max:4096',
            'image_alt'   => 'nullable|string|max:255',
            'link_url'    => 'nullable|url|max:1024',
            'link_target' => 'in:_self,_blank',
            'html_content' => 'nullable|string',
            'is_active'   => 'boolean',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
            'zones'       => 'nullable|array',
            'zones.*'     => 'exists:banner_zones,id',
            'priority'    => 'nullable|array',
            'priority.*'  => 'integer|min:0|max:100',
        ]);

        return [
            'name'         => $request->name,
            'type'         => $request->type,
            'image_alt'    => $request->image_alt,
            'link_url'     => $request->link_url,
            'link_target'  => $request->input('link_target', '_blank'),
            'html_content' => $request->html_content,
            'is_active'    => $request->boolean('is_active'),
            'starts_at'    => $request->starts_at ?: null,
            'ends_at'      => $request->ends_at ?: null,
        ];
    }

    private function syncZones(Request $request, Banner $banner): void
    {
        $zoneIds = $request->input('zones', []);
        $sync = [];
        foreach ($zoneIds as $id) {
            $sync[(int) $id] = ['priority' => (int) $request->input("priority.{$id}", 0)];
        }
        $banner->zones()->sync($sync);
    }

    /** Wyczyść zbuforowane listy bannerów wszystkich stref (front cachuje je 10 min). */
    private function flushZoneCache(): void
    {
        BannerZone::pluck('slug')->each(
            fn (string $slug) => Cache::forget("banner_zone_{$slug}")
        );
    }
}
