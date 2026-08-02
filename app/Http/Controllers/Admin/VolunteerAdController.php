<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VolunteerAdRequest;
use App\Models\VolunteerAd;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: zarządzanie ogłoszeniami wolontariackimi z archiwizacją,
 * klonowaniem i operacjami zbiorczymi.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), clone(),
 *         bulk(), archive(), restore().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class VolunteerAdController extends Controller
{
    /** Wyświetla listę ogłoszeń wolontariackich z przełącznikiem archiwum. */
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        $ads = VolunteerAd::when($showArchived,
            fn ($q) => $q->whereNotNull('archived_at'),
            fn ($q) => $q->whereNull('archived_at'))
            ->orderBy('order')
            ->latest('id')
            ->get();

        $archivedCount = VolunteerAd::whereNotNull('archived_at')->count();

        return view('admin.volunteer-ads.index', compact('ads', 'showArchived', 'archivedCount'));
    }

    /** Schowaj ogłoszenie z domyślnej listy (ręczna archiwizacja). */
    public function archive(VolunteerAd $wolontariat)
    {
        $wolontariat->update(['archived_at' => now()]);

        return redirect()->back()->with('status', 'Ogłoszenie zostało zarchiwizowane.');
    }

    /** Przywróć ogłoszenie z archiwum na aktywną listę. */
    public function restore(VolunteerAd $wolontariat)
    {
        $wolontariat->update(['archived_at' => null]);

        return redirect()->back()->with('status', 'Ogłoszenie zostało przywrócone z archiwum.');
    }

    /** Wyświetla formularz tworzenia nowego ogłoszenia. */
    public function create()
    {
        return view('admin.volunteer-ads.form', ['ad' => new VolunteerAd(['application_cta_label' => 'Zgłoś się', 'q_mode' => 'stacjonarnie'])]);
    }

    /** Zapisuje nowe ogłoszenie wolontariackie z unikalnym slugiem. */
    public function store(VolunteerAdRequest $request)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        VolunteerAd::create($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało dodane.');
    }

    /** Wyświetla formularz edycji ogłoszenia. */
    public function edit(VolunteerAd $wolontariat)
    {
        return view('admin.volunteer-ads.form', ['ad' => $wolontariat]);
    }

    /** Aktualizuje ogłoszenie wolontariackie. */
    public function update(VolunteerAdRequest $request, VolunteerAd $wolontariat)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $wolontariat->id);

        $wolontariat->update($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało zaktualizowane.');
    }

    /** Usuwa ogłoszenie wolontariackie. */
    public function destroy(VolunteerAd $wolontariat)
    {
        $wolontariat->delete();

        return redirect()->route('admin.wolontariat.index')->with('status', 'Ogłoszenie zostało usunięte.');
    }

    /** Klonuje ogłoszenie jako szkic bez terminu zgłoszeń. */
    public function clone(VolunteerAd $wolontariat)
    {
        $clone = $wolontariat->replicate();
        $clone->title = "{$wolontariat->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->closes_at = null;
        $clone->archived_at = null;
        $clone->save();

        return redirect()->route('admin.wolontariat.edit', $clone)
            ->with('status', 'Ogłoszenie zostało sklonowane jako "' . $clone->title . '". Jest zapisane jako szkic — uzupełnij termin zgłoszeń.');
    }

    /** Wykonuje zbiorczą operację (archiwizuj / przywróć / usuń) na zaznaczonych ogłoszeniach. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:archive,restore,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ads = VolunteerAd::whereIn('id', $data['ids'])->get();

        if ($ads->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono ogłoszeń do przetworzenia.');
        }

        $count = $ads->count();

        match ($data['action']) {
            'archive' => VolunteerAd::whereIn('id', $ads->pluck('id'))->update(['archived_at' => now()]),
            'restore' => VolunteerAd::whereIn('id', $ads->pluck('id'))->update(['archived_at' => null]),
            'delete' => $ads->each->delete(),
        };

        $message = match ($data['action']) {
            'archive' => "Zarchiwizowano ogłoszeń: {$count}.",
            'restore' => "Przywrócono z archiwum ogłoszeń: {$count}.",
            'delete' => "Usunięto ogłoszeń: {$count}.",
        };

        return redirect()->back()->with('status', $message);
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
