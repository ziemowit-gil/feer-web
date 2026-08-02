<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Panel admin: zarządzanie slajdami sekcji hero (CRUD, kolejność) i dedykowany
 * edytor slajdu z misją organizacji.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), updateMissionSlide().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HeroSlideController extends Controller
{
    /** Wyświetla listę slajdów sekcji hero. */
    public function index()
    {
        $heroSlides = HeroSlide::orderBy('order')->get();

        return view('admin.hero.index', compact('heroSlides'));
    }

    /** Wyświetla formularz tworzenia nowego slajdu hero. */
    public function create()
    {
        return view('admin.hero.form', ['heroSlide' => new HeroSlide]);
    }

    /** Zapisuje nowy slajd hero wraz z obrazem. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:50'],
            'button_url' => ['nullable', 'required_with:button_label', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        unset($data['image']);

        $heroSlide = HeroSlide::create($data);
        $heroSlide->addMediaFromRequest('image')->toMediaCollection('image');

        return redirect()->route('admin.hero.index')->with('status', 'Slajd został dodany.');
    }

    /** Wyświetla formularz edycji slajdu hero. */
    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.hero.form', compact('heroSlide'));
    }

    /** Aktualizuje slajd hero, opcjonalnie zastępuje obraz. */
    public function update(Request $request, HeroSlide $heroSlide)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:50'],
            'button_url' => ['nullable', 'required_with:button_label', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
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

    /** Usuwa slajd hero. */
    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();

        return redirect()->route('admin.hero.index')->with('status', 'Slajd został usunięty.');
    }

    /** Zapisuje ustawienia slajdu z misją organizacji (widoczność, tło, obraz). */
    public function updateMissionSlide(Request $request)
    {
        $request->validate([
            'hero_mission_slide' => ['boolean'],
            'hero_mission_bg'    => ['nullable', 'in:brand,image'],
            'hero_mission_order' => ['nullable', 'integer', 'min:1'],
            'hero_mission_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $settings = SiteSetting::current();

        $settings->update([
            'hero_mission_slide' => (bool) $request->input('hero_mission_slide', false),
            'hero_mission_bg'    => $request->input('hero_mission_bg', 'brand'),
            'hero_mission_order' => max(1, (int) $request->input('hero_mission_order', 1)),
        ]);

        if ($request->hasFile('hero_mission_image')) {
            $settings->addMediaFromRequest('hero_mission_image')
                ->toMediaCollection('hero_mission_image');
        }

        return redirect()->route('admin.hero.index')->with('status', 'Ustawienia slajdu z misją zostały zapisane.');
    }
}
