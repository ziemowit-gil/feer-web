<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\Slider;
use Illuminate\Http\Request;

/**
 * Panel admin: slidery — zestawy slajdów dla strony głównej, bloga i wybranych stron.
 * Slajdy edytuje HeroSlideController (z ?slider=ID); tu lista, ustawienia, kolejność i usuwanie.
 *
 * Metody: index(), store(), show(), update(), copy(), destroy(), move().
 */
class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::query()->forCurrentSite()->with(['slides' => fn ($q) => $q->with('media')])->orderByDesc('is_home')->orderBy('name')->get();

        return view('admin.sliders.index', compact('sliders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $slider = Slider::create($data + ['is_home' => ! Slider::query()->forCurrentSite()->exists()]);

        return redirect()->route('admin.slidery.show', $slider)->with('status', 'Slider „'.$slider->name.'” utworzony — dodaj slajdy.');
    }

    public function show(Slider $slider)
    {
        return view('admin.sliders.show', [
            'slider' => $slider->load(['slides' => fn ($q) => $q->with('media')]),
            'pages' => $slider->pages(),
        ]);
    }

    public function update(Request $request, Slider $slider)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration' => ['nullable', 'integer', 'min:3', 'max:60'],
        ]);
        $data['is_home'] = $request->boolean('is_home');
        $data['show_on_blog'] = $request->boolean('show_on_blog');

        // Strona główna i blog mają po jednym sliderze — zaznaczenie tutaj zdejmuje je z innych.
        foreach (['is_home', 'show_on_blog'] as $flag) {
            if ($data[$flag]) {
                Slider::query()->forCurrentSite()->whereKeyNot($slider->id)->update([$flag => false]);
            }
        }
        $slider->update($data);

        return back()->with('status', 'Ustawienia slidera zapisane.');
    }

    /**
     * Kopiuje slider razem ze slajdami (tytuł, tekst, przycisk, kolejność) i własnymi kopiami zdjęć.
     * Kopia nie ma przypisanego miejsca (strona główna, blog, strony), więc nie pojawia się od razu na froncie.
     */
    public function copy(Slider $slider)
    {
        $copy = \Illuminate\Support\Facades\DB::transaction(function () use ($slider) {
            $copy = $slider->replicate(['is_home', 'show_on_blog']);
            $copy->fill(['name' => \Illuminate\Support\Str::limit($slider->name, 110, '').' (kopia)', 'is_home' => false, 'show_on_blog' => false])->save();

            foreach ($slider->slides()->with('media')->get() as $slide) {
                $newSlide = $slide->replicate();
                $newSlide->slider_id = $copy->id;
                $newSlide->save();
                $slide->getMedia('image')->each(fn ($media) => $media->copy($newSlide, 'image'));
            }

            return $copy;
        });

        return redirect()->route('admin.slidery.show', $copy)->with('status', 'Slider „'.$slider->name.'” skopiowany jako „'.$copy->name.'”. Kopia nie jest nigdzie wyświetlana — przypisz ją w ustawieniach.');
    }

    public function destroy(Slider $slider)
    {
        $name = $slider->name;
        $slider->slides()->get()->each->delete(); // przez model — razem ze zdjęciami
        $slider->delete();

        return redirect()->route('admin.hero.index')->with('status', "Slider „{$name}” usunięty.");
    }

    /** Przesuwa slajd w górę / w dół (przepisuje kolejność 0..n). */
    public function move(Request $request, Slider $slider, HeroSlide $heroSlide)
    {
        abort_unless($heroSlide->slider_id === $slider->id, 404);
        $dir = $request->input('kierunek') === 'gora' ? -1 : 1;

        $slides = $slider->slides()->get()->values();
        $i = $slides->search(fn ($s) => $s->id === $heroSlide->id);
        $j = $i + $dir;
        if ($i !== false && $j >= 0 && $j < $slides->count()) {
            $tmp = $slides[$i];
            $slides[$i] = $slides[$j];
            $slides[$j] = $tmp;
        }
        $slides->each(fn ($s, $n) => $s->order !== $n ? $s->update(['order' => $n]) : null);

        return back()->with('status', 'Zmieniono kolejność slajdów.');
    }
}
