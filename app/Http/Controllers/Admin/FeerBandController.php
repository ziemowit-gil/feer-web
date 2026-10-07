<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeerBand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel admin: moduł „FEER Paski" — paski z tytułem, tekstem i przyciskami (CRUD, kolejność, miejsce na stronie głównej).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 */
class FeerBandController extends Controller
{
    public function index()
    {
        $bands = FeerBand::forCurrentSite()->orderBy('order')->orderBy('id')->get();

        return view('admin.feer-bands.index', compact('bands'));
    }

    public function create()
    {
        return view('admin.feer-bands.form', ['band' => new FeerBand(['style' => 'brand', 'placement' => 'after_news', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $band = FeerBand::create($this->validated($request));

        return redirect()->route('admin.feer-paski.index')->with('status', 'Pasek został dodany. Skrót do treści: '.$band->shortcode());
    }

    public function edit(FeerBand $band)
    {
        return view('admin.feer-bands.form', compact('band'));
    }

    public function update(Request $request, FeerBand $band)
    {
        $band->update($this->validated($request));

        return redirect()->route('admin.feer-paski.index')->with('status', 'Pasek został zaktualizowany.');
    }

    public function destroy(FeerBand $band)
    {
        $band->delete();

        return redirect()->route('admin.feer-paski.index')->with('status', 'Pasek został usunięty.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'text' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:80', 'required_with:button_url'],
            'button_url' => ['nullable', 'string', 'max:255', 'required_with:button_label'],
            'button2_label' => ['nullable', 'string', 'max:80', 'required_with:button2_url'],
            'button2_url' => ['nullable', 'string', 'max:255', 'required_with:button2_label'],
            'style' => ['required', Rule::in(array_keys(FeerBand::STYLES))],
            'placement' => ['required', Rule::in(array_keys(FeerBand::PLACEMENTS))],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
