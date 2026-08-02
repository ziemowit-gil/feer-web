<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

/**
 * Panel admin: CRUD partnerów organizacji (logotypy, linki, kolejność).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PartnerController extends Controller
{
    /** Wyświetla listę partnerów posortowaną wg kolejności. */
    public function index()
    {
        $partners = Partner::orderBy('order')->get();

        return view('admin.partners.index', compact('partners'));
    }

    /** Wyświetla formularz tworzenia nowego partnera. */
    public function create()
    {
        return view('admin.partners.form', ['partner' => new Partner]);
    }

    /** Zapisuje nowego partnera wraz z plikiem logotypu. */
    public function store(Request $request)
    {
        $data = $this->validated($request, isCreate: true);
        unset($data['logo']);

        $partner = Partner::create($data);
        $partner->addMediaFromRequest('logo')->toMediaCollection('logo');

        return redirect()->route('admin.partnerzy.index')->with('status', 'Partner został dodany.');
    }

    /** Wyświetla formularz edycji partnera. */
    public function edit(Partner $partner)
    {
        return view('admin.partners.form', compact('partner'));
    }

    /** Aktualizuje dane partnera, opcjonalnie zastępuje logotyp. */
    public function update(Request $request, Partner $partner)
    {
        $data = $this->validated($request, isCreate: false);
        unset($data['logo']);

        $partner->update($data);

        if ($request->hasFile('logo')) {
            $partner->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()->route('admin.partnerzy.index')->with('status', 'Partner został zaktualizowany.');
    }

    /** Usuwa partnera. */
    public function destroy(Partner $partner)
    {
        $partner->delete();

        return redirect()->route('admin.partnerzy.index')->with('status', 'Partner został usunięty.');
    }

    private function validated(Request $request, bool $isCreate): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'logo' => [$isCreate ? 'required' : 'nullable', 'image', 'max:2048'],
        ]);

        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
