<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacilitatorRequest;
use App\Models\Facilitator;

class FacilitatorController extends Controller
{
    public function index()
    {
        $facilitators = Facilitator::orderBy('name')->get();

        return view('admin.facilitators.index', compact('facilitators'));
    }

    public function create()
    {
        return view('admin.facilitators.form', ['facilitator' => new Facilitator]);
    }

    public function store(FacilitatorRequest $request)
    {
        $facilitator = Facilitator::create($request->safe()->except(['photo', 'remove_photo']));
        $this->syncPhoto($request, $facilitator);

        return redirect()->route('admin.prowadzacy.index')->with('status', 'Prowadzący/a dodany/a do katalogu.');
    }

    public function edit(Facilitator $prowadzacy)
    {
        return view('admin.facilitators.form', ['facilitator' => $prowadzacy]);
    }

    public function update(FacilitatorRequest $request, Facilitator $prowadzacy)
    {
        $prowadzacy->update($request->safe()->except(['photo', 'remove_photo']));
        $this->syncPhoto($request, $prowadzacy);

        return redirect()->route('admin.prowadzacy.index')->with('status', 'Dane prowadzącego/ej zaktualizowane.');
    }

    public function destroy(Facilitator $prowadzacy)
    {
        $prowadzacy->delete();

        return redirect()->route('admin.prowadzacy.index')->with('status', 'Prowadzący/a usunięty/a z katalogu.');
    }

    private function syncPhoto(FacilitatorRequest $request, Facilitator $facilitator): void
    {
        if ($request->hasFile('photo')) {
            $facilitator->addMediaFromRequest('photo')->toMediaCollection('photo');
        } elseif ($request->boolean('remove_photo')) {
            $facilitator->clearMediaCollection('photo');
        }
    }
}
