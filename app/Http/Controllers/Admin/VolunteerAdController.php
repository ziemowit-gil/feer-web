<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VolunteerAdRequest;
use App\Models\VolunteerAd;
use Illuminate\Support\Str;

class VolunteerAdController extends Controller
{
    public function index()
    {
        $ads = VolunteerAd::orderBy('order')->latest('id')->get();

        return view('admin.volunteer-ads.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.volunteer-ads.form', ['ad' => new VolunteerAd(['application_cta_label' => 'Zgłoś się', 'q_mode' => 'stacjonarnie'])]);
    }

    public function store(VolunteerAdRequest $request)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        VolunteerAd::create($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało dodane.');
    }

    public function edit(VolunteerAd $wolontariat)
    {
        return view('admin.volunteer-ads.form', ['ad' => $wolontariat]);
    }

    public function update(VolunteerAdRequest $request, VolunteerAd $wolontariat)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $wolontariat->id);

        $wolontariat->update($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało zaktualizowane.');
    }

    public function destroy(VolunteerAd $wolontariat)
    {
        $wolontariat->delete();

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało usunięte.');
    }

    /** Znormalizowane dane z żądania (checkbox publikacji, domyślne wartości). */
    private function prepared(VolunteerAdRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $data['order'] = $data['order'] ?? 0;
        $data['application_cta_label'] = trim((string) ($data['application_cta_label'] ?? '')) ?: 'Zgłoś się';

        return $data;
    }

    /** Unikalny slug ogłoszenia (pomijając bieżące przy edycji). */
    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'ogloszenie';
        $candidate = $slug;
        $i = 2;

        while (VolunteerAd::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
