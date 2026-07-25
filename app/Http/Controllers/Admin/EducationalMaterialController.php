<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationalMaterialController extends Controller
{
    public function index()
    {
        $materials = EducationalMaterial::orderBy('order')->orderBy('title')->get();

        return view('admin.educational-materials.index', compact('materials'));
    }

    public function create()
    {
        return view('admin.educational-materials.form', ['material' => new EducationalMaterial]);
    }

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

    public function edit(EducationalMaterial $material)
    {
        return view('admin.educational-materials.form', compact('material'));
    }

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

    public function destroy(EducationalMaterial $material)
    {
        $material->delete();

        return redirect()->route('admin.materialy-edukacyjne.index')->with('status', 'Materiał został usunięty.');
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
        ]);

        $data['order'] = $data['order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_archival'] = $request->boolean('is_archival');

        if ($data['type'] !== 'video') {
            $data['video_url'] = null;
        }

        return $data;
    }
}
