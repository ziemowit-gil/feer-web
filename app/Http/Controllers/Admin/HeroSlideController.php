<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class HeroSlideController extends Controller
{
    public function index()
    {
        $heroSlides = HeroSlide::orderBy('order')->get();

        return view('admin.hero.index', compact('heroSlides'));
    }

    public function create()
    {
        return view('admin.hero.form', ['heroSlide' => new HeroSlide]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:50'],
            'button_url' => ['nullable', 'required_with:button_label', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        unset($data['image']);

        $heroSlide = HeroSlide::create($data);
        $heroSlide->addMediaFromRequest('image')->toMediaCollection('image');

        return redirect()->route('admin.hero.index')->with('status', 'Slajd został dodany.');
    }

    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.hero.form', compact('heroSlide'));
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:50'],
            'button_url' => ['nullable', 'required_with:button_label', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        unset($data['image']);

        $heroSlide->update($data);

        if ($request->hasFile('image')) {
            $heroSlide->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.hero.index')->with('status', 'Slajd został zaktualizowany.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();

        return redirect()->route('admin.hero.index')->with('status', 'Slajd został usunięty.');
    }

    public function updateMissionSlide(Request $request)
    {
        $request->validate(['hero_mission_slide' => ['boolean']]);

        SiteSetting::current()->update([
            'hero_mission_slide' => (bool) $request->input('hero_mission_slide', false),
        ]);

        return redirect()->route('admin.hero.index')->with('status', 'Ustawienie slajdu z misją zostało zapisane.');
    }
}
