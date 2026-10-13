<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\Page;
use Illuminate\Http\Request;

/** Panel: moduł „Koordynatorzy” — osoby kontaktowe wybierane w działaniach. */
class CoordinatorController extends Controller
{
    public function index()
    {
        return view('admin.coordinators.index', [
            'coordinators' => Coordinator::query()->forCurrentSite()->withCount('projects')->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.coordinators.form', ['coordinator' => new Coordinator, 'people' => $this->people()]);
    }

    public function store(Request $request)
    {
        $coordinator = Coordinator::create($this->validated($request));

        return redirect()->route('admin.koordynatorzy.index')->with('status', 'Dodano koordynatora „'.$coordinator->name.'”.');
    }

    public function edit(Coordinator $koordynatorzy)
    {
        return view('admin.coordinators.form', ['coordinator' => $koordynatorzy->load('projects'), 'people' => $this->people()]);
    }

    public function update(Request $request, Coordinator $koordynatorzy)
    {
        $koordynatorzy->update($this->validated($request));

        return redirect()->route('admin.koordynatorzy.index')->with('status', 'Zapisano koordynatora „'.$koordynatorzy->name.'”.');
    }

    public function destroy(Coordinator $koordynatorzy)
    {
        $name = $koordynatorzy->name;
        $koordynatorzy->delete(); // działania zostają — bez koordynatora z modułu pokażą dane wpisane w działaniu

        return redirect()->route('admin.koordynatorzy.index')->with('status', 'Usunięto koordynatora „'.$name.'”.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'phone_hours' => ['nullable', 'string', 'max:160'],
            'person_page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }

    /** Osoby z zespołu (strony „osoba”) — źródło zdjęcia koordynatora. */
    private function people()
    {
        return Page::query()->forCurrentSite()->where('type', 'about_person')->orderBy('title')->get(['id', 'title', 'content_image']);
    }
}
