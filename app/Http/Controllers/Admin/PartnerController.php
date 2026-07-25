<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::orderBy('order')->get();

        return view('admin.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.partners.form', ['partner' => new Partner]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, isCreate: true);
        unset($data['logo']);

        $partner = Partner::create($data);
        $partner->addMediaFromRequest('logo')->toMediaCollection('logo');

        return redirect()->route('admin.partnerzy.index')->with('status', 'Partner został dodany.');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.form', compact('partner'));
    }

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
