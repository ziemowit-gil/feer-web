<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpPoint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel admin: CRUD punktów pomocy (moduł "Mapa pomocy", szablon federation).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HelpPointController extends Controller
{
    public function index()
    {
        $points = HelpPoint::orderBy('order')->orderBy('name')->get();

        return view('admin.help-points.index', compact('points'));
    }

    public function create()
    {
        return view('admin.help-points.form', ['point' => new HelpPoint]);
    }

    public function store(Request $request)
    {
        HelpPoint::create($this->validated($request));

        return redirect()->route('admin.mapa-pomocy.index')->with('status', 'Punkt pomocy został dodany.');
    }

    public function edit(HelpPoint $helpPoint)
    {
        return view('admin.help-points.form', ['point' => $helpPoint]);
    }

    public function update(Request $request, HelpPoint $helpPoint)
    {
        $helpPoint->update($this->validated($request));

        return redirect()->route('admin.mapa-pomocy.index')->with('status', 'Punkt pomocy został zaktualizowany.');
    }

    public function destroy(HelpPoint $helpPoint)
    {
        $helpPoint->delete();

        return redirect()->route('admin.mapa-pomocy.index')->with('status', 'Punkt pomocy został usunięty.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(HelpPoint::CATEGORIES))],
            'address' => ['nullable', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
