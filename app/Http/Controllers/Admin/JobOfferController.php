<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobOfferRequest;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: zarządzanie ogłoszeniami o pracę z archiwizacją,
 * klonowaniem i operacjami zbiorczymi.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(),
 *         clone(), bulk(), archive(), restore().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class JobOfferController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        $offers = JobOffer::when($showArchived,
            fn ($q) => $q->whereNotNull('archived_at'),
            fn ($q) => $q->whereNull('archived_at'))
            ->orderBy('order')
            ->latest('id')
            ->get();

        $archivedCount = JobOffer::whereNotNull('archived_at')->count();

        return view('admin.job-offers.index', compact('offers', 'showArchived', 'archivedCount'));
    }

    public function create()
    {
        return view('admin.job-offers.form', [
            'offer' => new JobOffer(['application_cta_label' => 'Aplikuj', 'mode' => 'stacjonarnie', 'job_type' => 'pelny_etat']),
        ]);
    }

    public function store(JobOfferRequest $request)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        JobOffer::create($data);

        return redirect()->route('admin.praca.index')->with('status', 'Ogłoszenie zostało dodane.');
    }

    public function edit(JobOffer $praca)
    {
        return view('admin.job-offers.form', ['offer' => $praca]);
    }

    public function update(JobOfferRequest $request, JobOffer $praca)
    {
        $data = $this->prepared($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $praca->id);

        $praca->update($data);

        return redirect()->route('admin.praca.index')->with('status', 'Ogłoszenie zostało zaktualizowane.');
    }

    public function destroy(JobOffer $praca)
    {
        $praca->delete();

        return redirect()->route('admin.praca.index')->with('status', 'Ogłoszenie zostało usunięte.');
    }

    public function archive(JobOffer $praca)
    {
        $praca->update(['archived_at' => now()]);

        return redirect()->back()->with('status', 'Ogłoszenie zostało zarchiwizowane.');
    }

    public function restore(JobOffer $praca)
    {
        $praca->update(['archived_at' => null]);

        return redirect()->back()->with('status', 'Ogłoszenie zostało przywrócone z archiwum.');
    }

    public function clone(JobOffer $praca)
    {
        $clone = $praca->replicate();
        $clone->title = "{$praca->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->closes_at = null;
        $clone->archived_at = null;
        $clone->save();

        return redirect()->route('admin.praca.edit', $clone)
            ->with('status', 'Ogłoszenie zostało sklonowane jako "' . $clone->title . '".');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:archive,restore,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
        ]);

        $offers = JobOffer::whereIn('id', $data['ids'])->get();

        if ($offers->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono ogłoszeń do przetworzenia.');
        }

        $count = $offers->count();

        match ($data['action']) {
            'archive' => JobOffer::whereIn('id', $offers->pluck('id'))->update(['archived_at' => now()]),
            'restore' => JobOffer::whereIn('id', $offers->pluck('id'))->update(['archived_at' => null]),
            'delete'  => $offers->each->delete(),
        };

        $message = match ($data['action']) {
            'archive' => "Zarchiwizowano ogłoszeń: {$count}.",
            'restore' => "Przywrócono z archiwum ogłoszeń: {$count}.",
            'delete'  => "Usunięto ogłoszeń: {$count}.",
        };

        return redirect()->back()->with('status', $message);
    }

    private function prepared(JobOfferRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $data['order'] = $data['order'] ?? 0;
        $data['application_cta_label'] = trim((string) ($data['application_cta_label'] ?? '')) ?: 'Aplikuj';

        return $data;
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'ogloszenie-pracy';
        $candidate = $slug;
        $i = 2;

        while (JobOffer::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
