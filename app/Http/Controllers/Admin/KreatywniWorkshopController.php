<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KreatywniWorkshop;
use App\Models\Page;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mikro moduł „Warsztaty – Kreatywni” (szablon „kreatywni”): terminy warsztatów do strony typu „Kalendarz warsztatów”
 * — dodawanie, edycja, kopia terminu (np. kolejna edycja tego samego warsztatu), widoczność i usuwanie. Grafika
 * ilustracyjna wgrana z dysku albo wybrana z biblioteki Multimediów (AttachesLibraryMedia). Czas w formularzu polski.
 */
class KreatywniWorkshopController extends Controller
{
    use \App\Http\Controllers\Concerns\AttachesLibraryMedia;

    public function index(): View
    {
        $this->site();
        $upcoming = KreatywniWorkshop::forCurrentSite()->upcoming()->orderBy('starts_at')->with('media')->get();
        $past = KreatywniWorkshop::forCurrentSite()->past()->orderByDesc('starts_at')->with('media')->limit(50)->get();
        $calendarPages = Page::forCurrentSite()->where('type', 'kreatywni_workshops')->orderBy('title')->get(['id', 'title', 'slug', 'is_published']);

        return view('admin.kreatywni-workshops.index', compact('upcoming', 'past', 'calendarPages'));
    }

    public function create(Request $request): View
    {
        $this->site();
        $workshop = new KreatywniWorkshop(['is_active' => true]);
        // „Kopiuj termin”: te same dane i grafika, bez daty.
        $source = $request->integer('kopia') ? KreatywniWorkshop::forCurrentSite()->find($request->integer('kopia')) : null;
        if ($source) {
            $workshop->fill($source->only(['title', 'place', 'participants', 'description', 'image_alt']) + ['is_active' => true]);
        }

        return view('admin.kreatywni-workshops.form', ['workshop' => $workshop, 'copyOf' => $source]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->site();
        $workshop = KreatywniWorkshop::create($this->validated($request));
        $this->saveImage($request, $workshop);
        // Kopia terminu bez nowej grafiki bierze grafikę z oryginału.
        if (! $workshop->getFirstMedia('image') && ($from = $request->integer('copy_of'))) {
            KreatywniWorkshop::forCurrentSite()->find($from)?->getFirstMedia('image')?->copy($workshop, 'image');
        }

        return redirect()->route('admin.kreatywni-warsztaty.index')->with('status', 'Dodano warsztat „'.$workshop->title.'” ('.$workshop->whenLabel().').');
    }

    public function edit(KreatywniWorkshop $workshop): View
    {
        $this->guard($workshop);

        return view('admin.kreatywni-workshops.form', ['workshop' => $workshop, 'copyOf' => null]);
    }

    public function update(Request $request, KreatywniWorkshop $workshop): RedirectResponse
    {
        $this->guard($workshop);
        $workshop->update($this->validated($request));
        $this->saveImage($request, $workshop);

        return redirect()->route('admin.kreatywni-warsztaty.index')->with('status', 'Zapisano warsztat „'.$workshop->title.'”.');
    }

    public function destroy(KreatywniWorkshop $workshop): RedirectResponse
    {
        $this->guard($workshop);
        $title = $workshop->title;
        $workshop->delete();

        return redirect()->route('admin.kreatywni-warsztaty.index')->with('status', 'Usunięto warsztat „'.$title.'”.');
    }

    private function site(): SiteSetting
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniWorkshop::supported(), 404);

        return $site;
    }

    private function guard(KreatywniWorkshop $workshop): void
    {
        abort_unless((int) $workshop->site_id === (int) $this->site()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'participants' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:6000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'image_library_id' => $this->libraryMediaRule(),
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'ends_at.after' => 'Koniec warsztatu musi być później niż początek.',
        ], [
            'title' => 'nazwa warsztatu', 'place' => 'miejsce', 'starts_at' => 'początek', 'ends_at' => 'koniec',
            'participants' => 'liczba uczestników', 'description' => 'opis', 'image' => 'grafika', 'image_alt' => 'opis grafiki',
        ]);

        $text = fn (?string $v) => filled($v) ? trim(str_replace("\r\n", "\n", $v)) : null;
        // Formularz podaje czas polski (datetime-local), w bazie UTC.
        $utc = fn (?string $v) => filled($v) ? Carbon::parse($v, KreatywniWorkshop::TZ)->utc() : null;

        return [
            'title' => trim($data['title']),
            'place' => $text($data['place'] ?? null),
            'starts_at' => $utc($data['starts_at']),
            'ends_at' => $utc($data['ends_at'] ?? null),
            'participants' => $text($data['participants'] ?? null),
            'description' => $text($data['description'] ?? null),
            'image_alt' => $text($data['image_alt'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function saveImage(Request $request, KreatywniWorkshop $workshop): void
    {
        if ($request->hasFile('image')) {
            $workshop->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif ($this->attachFromLibrary($request, $workshop, 'image', 'image')) {
            // obraz z biblioteki skopiowany do warsztatu
        } elseif ($request->boolean('remove_image')) {
            $workshop->clearMediaCollection('image');
        }
    }
}
