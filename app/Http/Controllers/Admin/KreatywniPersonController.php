<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KreatywniPerson;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Mikro moduł „Osoby – Kreatywni” (szablon „kreatywni”): osoby z sekcji „Kto prowadzi warsztaty” — dodawanie, edycja,
 * zdjęcie, widoczność, kolejność i jednorazowy import z tekstów szablonu. Tylko dla witryny w szablonie „kreatywni”.
 */
class KreatywniPersonController extends Controller
{
    public function index(): View
    {
        $texts = KreatywniTexts::for($this->site());
        $people = KreatywniPerson::forCurrentSite()->orderBy('order')->orderBy('id')->get();
        $importable = $people->isEmpty() ? count($this->textRows($texts)) : 0;

        return view('admin.kreatywni-people.index', compact('people', 'importable'));
    }

    public function create(): View
    {
        $this->site();

        return view('admin.kreatywni-people.form', ['person' => new KreatywniPerson(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->site();
        $data = $this->validated($request);
        $person = KreatywniPerson::create($data + ['order' => (int) KreatywniPerson::forCurrentSite()->max('order') + 1]);
        $this->savePhoto($request, $person);

        return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Dodano osobę „'.$person->name.'”.');
    }

    public function edit(KreatywniPerson $person): View
    {
        $this->guard($person);

        return view('admin.kreatywni-people.form', compact('person'));
    }

    public function update(Request $request, KreatywniPerson $person): RedirectResponse
    {
        $this->guard($person);
        $person->update($this->validated($request));
        $this->savePhoto($request, $person);

        return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Zapisano osobę „'.$person->name.'”.');
    }

    public function destroy(KreatywniPerson $person): RedirectResponse
    {
        $this->guard($person);
        $name = $person->name;
        $person->delete();

        return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Usunięto osobę „'.$name.'”.');
    }

    /** Wyżej / Niżej: zamiana miejscami z sąsiednią osobą (kolejność najpierw porządkowana 1, 2, 3…). */
    public function move(Request $request, KreatywniPerson $person): RedirectResponse
    {
        $this->guard($person);
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($person, $direction) {
            $ids = KreatywniPerson::forCurrentSite()->orderBy('order')->orderBy('id')->pluck('id')->all();
            $at = array_search($person->getKey(), $ids, true);
            $to = $direction === 'up' ? $at - 1 : $at + 1;
            if ($at === false || ! isset($ids[$to])) {
                return;
            }
            [$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
            foreach ($ids as $i => $id) {
                KreatywniPerson::whereKey($id)->update(['order' => $i + 1]);
            }
        });

        return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Przesunięto osobę „'.$person->name.'” '.($direction === 'up' ? 'wyżej' : 'niżej').'.');
    }

    /** Przenosi osoby z tekstów szablonu (strona „O nas”, a gdy pusto — sekcja Zespół) do modułu; tylko przy pustym module. */
    public function import(): RedirectResponse
    {
        $texts = KreatywniTexts::for($this->site());
        if (KreatywniPerson::forCurrentSite()->exists()) {
            return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Moduł ma już osoby — import pominięty.');
        }

        $rows = $this->textRows($texts);
        foreach ($rows as $i => [$name, $role, $bio]) {
            KreatywniPerson::create(['name' => $name, 'role' => $role ?: null, 'bio' => $bio ?: null, 'order' => $i + 1, 'is_active' => true]);
        }

        return redirect()->route('admin.kreatywni-osoby.index')->with('status', 'Przeniesiono z tekstów szablonu: '.count($rows).' '.trans_choice('osobę|osoby|osób', count($rows)).'. Teraz możesz dodać zdjęcia.');
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    private function textRows(KreatywniTexts $texts): array
    {
        return $texts->rows('about_team', 3) ?: $texts->rows('team_items', 3);
    }

    private function site(): SiteSetting
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniPerson::supported(), 404);

        return $site;
    }

    private function guard(KreatywniPerson $person): void
    {
        abort_unless((int) $person->site_id === (int) $this->site()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo_alt' => ['nullable', 'string', 'max:255'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'imię i nazwisko', 'role' => 'rola', 'bio' => 'opis', 'photo' => 'zdjęcie', 'photo_alt' => 'opis zdjęcia',
        ]);

        return [
            'name' => trim($data['name']),
            'role' => filled($data['role'] ?? null) ? trim($data['role']) : null,
            'bio' => filled($data['bio'] ?? null) ? trim(str_replace("\r\n", "\n", $data['bio'])) : null,
            'photo_alt' => filled($data['photo_alt'] ?? null) ? trim($data['photo_alt']) : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function savePhoto(Request $request, KreatywniPerson $person): void
    {
        if ($request->hasFile('photo')) {
            $person->addMediaFromRequest('photo')->toMediaCollection('photo');
        } elseif ($request->boolean('remove_photo')) {
            $person->clearMediaCollection('photo');
        }
    }
}
