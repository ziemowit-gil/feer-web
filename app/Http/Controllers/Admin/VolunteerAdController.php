<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VolunteerAdRequest;
use App\Models\VolunteerAd;
use App\Services\SzoClient;
use App\Services\SzoFormMirror;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: zarządzanie ofertami wolontariatu z archiwizacją,
 * klonowaniem i operacjami zbiorczymi.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), clone(),
 *         bulk(), archive(), restore().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class VolunteerAdController extends Controller
{
    /** Wyświetla listę ofert wolontariatu z przełącznikiem archiwum. */
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

    /** Schowaj ofertę z domyślnej listy (ręczna archiwizacja). */
    public function archive(VolunteerAd $wolontariat)
    {
        $wolontariat->update(['archived_at' => now()]);

        return redirect()->back()->with('status', 'Oferta została zarchiwizowana.');
    }

    /** Przywróć ofertę z archiwum na aktywną listę. */
    public function restore(VolunteerAd $wolontariat)
    {
        $wolontariat->update(['archived_at' => null]);

        return redirect()->back()->with('status', 'Oferta została przywrócona z archiwum.');
    }

    /** Wyświetla formularz tworzenia nowej oferty. */
    public function create()
    {
        return view('admin.volunteer-ads.form', [
            'ad' => new VolunteerAd(['application_cta_label' => 'Zgłoś się', 'q_mode' => 'stacjonarnie']),
            'szoForms' => app(SzoClient::class)->forms(),
        ]);
    }

    /** Zapisuje nową ofertę wolontariatu z unikalnym slugiem. */
    public function store(VolunteerAdRequest $request)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        VolunteerAd::create($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Oferta została dodana.');
    }

    /** Wyświetla formularz edycji oferty. */
    public function edit(VolunteerAd $wolontariat)
    {
        return view('admin.volunteer-ads.form', ['ad' => $wolontariat, 'szoForms' => app(SzoClient::class)->forms()]);
    }

    /** Aktualizuje ofertę wolontariatu. */
    public function update(VolunteerAdRequest $request, VolunteerAd $wolontariat)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $wolontariat->id);

        $wolontariat->update($data);

        return redirect()->route('admin.wolontariat.index')->with('status', 'Oferta została zaktualizowana.');
    }

    /** Usuwa ofertę wolontariatu. */
    public function destroy(VolunteerAd $wolontariat)
    {
        $wolontariat->delete();

        return redirect()->route('admin.wolontariat.index')->with('status', 'Oferta została usunięta.');
    }

    /** Klonuje ofertę jako szkic bez terminu zgłoszeń. */
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
            ->with('status', 'Oferta została sklonowana jako "' . $clone->title . '". Jest zapisana jako szkic — uzupełnij termin zgłoszeń.');
    }

    /** Wykonuje zbiorczą operację (archiwizuj / przywróć / usuń) na zaznaczonych ofertach. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:archive,restore,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ads = VolunteerAd::whereIn('id', $data['ids'])->get();

        if ($ads->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono ofert do przetworzenia.');
        }

        $count = $ads->count();

        match ($data['action']) {
            'archive' => VolunteerAd::whereIn('id', $ads->pluck('id'))->update(['archived_at' => now()]),
            'restore' => VolunteerAd::whereIn('id', $ads->pluck('id'))->update(['archived_at' => null]),
            'delete' => $ads->each->delete(),
        };

        $message = match ($data['action']) {
            'archive' => "Zarchiwizowano ofert: {$count}.",
            'restore' => "Przywrócono z archiwum ofert: {$count}.",
            'delete' => "Usunięto ofert: {$count}.",
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

        // Formularz z SZO: odświeżamy jego lustro w CMS-ie przy każdym zapisie oferty (pola mogły się zmienić w SZO).
        $data['szo_form_slug'] = trim((string) ($data['szo_form_slug'] ?? '')) ?: null;
        $data['form_definition_id'] = $data['szo_form_slug']
            ? app(SzoFormMirror::class)->sync($data['szo_form_slug'], $data['contact_email'] ?? null)?->id
            : null;

        return $data;
    }

    /** Unikalny slug oferty (pomijając bieżące przy edycji). */
    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'oferta';
        $candidate = $slug;
        $i = 2;

        while (VolunteerAd::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
