<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\SiteSetting;
use App\Support\UnsplashImport;
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
    use \App\Http\Controllers\Concerns\AttachesLibraryMedia;

    /** Wyświetla listę slajdów sekcji hero. */
    public function index()
    {
        // Slidery (wiele zestawów slajdów); przed migracją — dawna lista slajdów.
        if (\Illuminate\Support\Facades\Schema::hasTable('sliders')) {
            return app(SliderController::class)->index();
        }

        $heroSlides = HeroSlide::forCurrentSite()->orderBy('order')->get();

        return view('admin.hero.index', compact('heroSlides'));
    }

    /** Wyświetla formularz tworzenia nowego slajdu hero. */
    public function create()
    {
        return view('admin.hero.form', ['heroSlide' => new HeroSlide(['slider_id' => $this->targetSliderId(request())])]);
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
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['required_without_all:unsplash_full_url,image_library_id', 'nullable', 'image', 'max:4096'],
            'image_library_id' => $this->libraryMediaRule(),
            'slider_id' => ['nullable', 'integer'],
        ] + UnsplashImport::rules(), ['image.required_without_all' => 'Wgraj zdjęcie, wybierz je z biblioteki albo z Unsplash.']);

        $data['order'] = $data['order'] ?? 0;
        // Przed migracją kolumn dat (push bez deploy.sh) zapis nie może padać.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('hero_slides', 'starts_at')) {
            unset($data['starts_at'], $data['ends_at']);
        }
        unset($data['image'], $data['image_library_id'], $data['unsplash_full_url'], $data['unsplash_download_location'], $data['unsplash_author']);

        $data['slider_id'] = $this->targetSliderId($request);
        $heroSlide = HeroSlide::create($data);
        if ($request->hasFile('image')) {
            $heroSlide->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif (! $this->attachFromLibrary($request, $heroSlide, 'image', 'image')) {
            UnsplashImport::attach($heroSlide, $request);
        }

        return $this->backToSlider($heroSlide)->with('status', 'Slajd został dodany.');
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
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_library_id' => $this->libraryMediaRule(),
            'slider_id' => ['nullable', 'integer'],
        ] + UnsplashImport::rules());

        $data['order'] = $data['order'] ?? 0;
        // Przed migracją kolumn dat (push bez deploy.sh) zapis nie może padać.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('hero_slides', 'starts_at')) {
            unset($data['starts_at'], $data['ends_at']);
        }
        unset($data['image'], $data['image_library_id'], $data['unsplash_full_url'], $data['unsplash_download_location'], $data['unsplash_author']);

        $data['slider_id'] = $this->targetSliderId($request) ?? $heroSlide->slider_id;
        $heroSlide->update($data);

        if ($request->hasFile('image')) {
            $heroSlide->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif (! $this->attachFromLibrary($request, $heroSlide, 'image', 'image')) {
            UnsplashImport::attach($heroSlide, $request);
        }

        return $this->backToSlider($heroSlide)->with('status', 'Slajd został zaktualizowany.');
    }

    /** Usuwa slajd hero. */
    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();

        return $this->backToSlider($heroSlide)->with('status', 'Slajd został usunięty.');
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

        return back()->with('status', 'Ustawienia slajdu z misją zostały zapisane.');
    }

    /** Slider, do którego trafia slajd: z formularza/adresu (?slider=ID) albo slider strony głównej. Null przed migracją. */
    private function targetSliderId(Request $request): ?int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sliders')) {
            return null;
        }
        $id = $request->integer('slider_id') ?: $request->integer('slider');
        $slider = $id ? \App\Models\Slider::query()->forCurrentSite()->find($id) : \App\Models\Slider::forPlacement('home');

        return $slider?->id;
    }

    private function backToSlider(HeroSlide $heroSlide): \Illuminate\Http\RedirectResponse
    {
        return $heroSlide->slider_id && \Illuminate\Support\Facades\Route::has('admin.slidery.show')
            ? redirect()->route('admin.slidery.show', $heroSlide->slider_id)
            : redirect()->route('admin.hero.index');
    }
}
