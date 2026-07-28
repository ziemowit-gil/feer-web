<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPageRequest;
use App\Models\LandingPage;

class LandingPageController extends Controller
{
    public function index()
    {
        $pages = LandingPage::withCount('registrations')->orderByDesc('updated_at')->get();

        return view('admin.landing-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.landing-pages.form', ['page' => new LandingPage]);
    }

    public function store(LandingPageRequest $request)
    {
        LandingPage::create($this->prepared($request));

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został utworzony.');
    }

    public function edit(LandingPage $landing)
    {
        return view('admin.landing-pages.form', ['page' => $landing]);
    }

    public function update(LandingPageRequest $request, LandingPage $landing)
    {
        $landing->update($this->prepared($request));

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został zapisany.');
    }

    public function destroy(LandingPage $landing)
    {
        $landing->delete();

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został usunięty.');
    }

    /** Normalizuje dane: czyści puste wiersze repeaterów i booleany. */
    private function prepared(LandingPageRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');

        foreach (['speakers', 'benefits', 'agenda'] as $section) {
            $data[$section] = collect($data[$section] ?? [])
                ->map(fn ($row) => array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row))
                ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
                ->values()
                ->all();
        }

        $data['section_order'] = array_values($data['section_order'] ?? []);

        return $data;
    }
}
