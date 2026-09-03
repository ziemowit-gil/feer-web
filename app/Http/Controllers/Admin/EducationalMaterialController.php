<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel admin: CRUD materiałów edukacyjnych (PDF, wideo, scenariusze) z obsługą MediaLibrary.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EducationalMaterialController extends Controller
{
    /** Wyświetla listę materiałów edukacyjnych. */
    public function index()
    {
        $materials = EducationalMaterial::orderBy('order')->orderBy('title')->get();

        return view('admin.educational-materials.index', compact('materials'));
    }

    /** Wyświetla formularz dodawania nowego materiału edukacyjnego. */
    public function create()
    {
        return view('admin.educational-materials.form', ['material' => new EducationalMaterial]);
    }

    /** Zapisuje nowy materiał edukacyjny wraz z plikiem PDF (jeśli dotyczy). */
    public function store(Request $request)
    {
        $data = $this->validated($request, isCreate: true);
        unset($data['file']);

        $material = EducationalMaterial::create($data);

        if ($request->hasFile('file')) {
            $material->addMediaFromRequest('file')->toMediaCollection('file');
        }

        return redirect()->route('admin.materialy-edukacyjne.index')->with('status', 'Materiał został dodany.');
    }

    /** Wyświetla formularz edycji materiału edukacyjnego. */
    public function edit(EducationalMaterial $material)
    {
        return view('admin.educational-materials.form', compact('material'));
    }

    /** Aktualizuje materiał edukacyjny, opcjonalnie zastępuje plik PDF. */
    public function update(Request $request, EducationalMaterial $material)
    {
        $data = $this->validated($request, isCreate: false);
        unset($data['file']);

        $material->update($data);

        if ($request->hasFile('file')) {
            $material->addMediaFromRequest('file')->toMediaCollection('file');
        }

        return redirect()->route('admin.materialy-edukacyjne.index')->with('status', 'Materiał został zaktualizowany.');
    }

    /** Usuwa materiał edukacyjny. */
    public function destroy(EducationalMaterial $material)
    {
        $material->delete();

        return redirect()->route('admin.materialy-edukacyjne.index')->with('status', 'Materiał został usunięty.');
    }

    /** Akcje zbiorcze: publish, unpublish, delete. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
        ]);

        $materials = EducationalMaterial::whereIn('id', $data['ids'])->get();

        if ($materials->isEmpty()) {
            return back()->with('error', 'Nie znaleziono materiałów.');
        }

        $count = $materials->count();

        match ($data['action']) {
            'publish'   => EducationalMaterial::whereIn('id', $materials->pluck('id'))->update(['is_published' => true]),
            'unpublish' => EducationalMaterial::whereIn('id', $materials->pluck('id'))->update(['is_published' => false]),
            'delete'    => $materials->each->delete(),
        };

        $message = match ($data['action']) {
            'publish'   => "Opublikowano materiałów: {$count}.",
            'unpublish' => "Cofnięto publikację materiałów: {$count}.",
            'delete'    => "Usunięto materiałów: {$count}.",
        };

        activity('cms')
            ->causedBy(auth()->user())
            ->withProperty('ids', $materials->pluck('id'))
            ->event('bulk_' . ($data['action'] === 'delete' ? 'deleted' : $data['action'] . 'd'))
            ->log("EducationalMaterial bulk_{$data['action']} ({$count})");

        return back()->with('status', $message);
    }

    private function validated(Request $request, bool $isCreate): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'target_group' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(EducationalMaterial::TYPES))],
            'video_url' => ['required_if:type,video', 'nullable', 'url', 'max:255'],
            'file' => [
                $isCreate ? 'required_if:type,pdf,scenariusz' : 'nullable',
                'nullable', 'file', 'mimes:pdf', 'max:10240',
            ],
            'order' => ['nullable', 'integer', 'min:0'],
            'price_pln' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_archival'] = $request->boolean('is_archival');
        $data['is_premium'] = $request->boolean('is_premium');
        $data['price_grosze'] = $data['price_pln'] !== null ? (int) round($data['price_pln'] * 100) : null;
        unset($data['price_pln']);

        if ($data['type'] !== 'video') {
            $data['video_url'] = null;
        }

        return $data;
    }
}
