<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Panel admin: moduł „Książka telefoniczna” — osoby (dział, rola, numer komórkowy, w jakiej sprawie, kiedy dzwonić)
 * i ustawienia strony /ksiazka-telefoniczna (tytuł, wstęp, układ, sortowanie, ukrywanie numerów).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), settings().
 */
class PhoneBookController extends Controller
{
    public function index()
    {
        $entries = PhoneBookEntry::forCurrentSite()->orderBy('order')->orderBy('id')->get();

        return view('admin.phone-book.index', [
            'entries' => $entries,
            'settings' => SiteSetting::current(),
        ]);
    }

    public function create()
    {
        return view('admin.phone-book.form', ['entry' => new PhoneBookEntry(['is_active' => true]), 'groups' => $this->groups()]);
    }

    public function store(Request $request)
    {
        PhoneBookEntry::create($this->validated($request));

        return redirect()->route('admin.ksiazka-telefoniczna.index')->with('status', 'Osoba została dodana do książki telefonicznej.');
    }

    public function edit(PhoneBookEntry $entry)
    {
        return view('admin.phone-book.form', ['entry' => $entry, 'groups' => $this->groups()]);
    }

    public function update(Request $request, PhoneBookEntry $entry)
    {
        $entry->update($this->validated($request));

        return redirect()->route('admin.ksiazka-telefoniczna.index')->with('status', 'Wpis został zaktualizowany.');
    }

    public function destroy(PhoneBookEntry $entry)
    {
        $entry->delete();

        return redirect()->route('admin.ksiazka-telefoniczna.index')->with('status', 'Wpis został usunięty.');
    }

    /** Ustawienia strony publicznej książki (tytuł, wstęp, układ, kolejność, ukrywanie numerów). */
    public function settings(Request $request)
    {
        $data = $request->validate([
            'phonebook_title' => ['nullable', 'string', 'max:160'],
            'phonebook_lead' => ['nullable', 'string', 'max:1000'],
            'phonebook_layout' => ['required', 'in:table,cards'],
            'phonebook_sort' => ['required', 'in:manual,alpha'],
        ]);
        $data['phonebook_hide_numbers'] = $request->boolean('phonebook_hide_numbers');

        SiteSetting::current()->update($data);

        return redirect()->route('admin.ksiazka-telefoniczna.index')->with('status', 'Zapisano ustawienia książki telefonicznej.');
    }

    /** Dotychczasowe działy do podpowiedzi w formularzu. */
    private function groups(): array
    {
        return PhoneBookEntry::forCurrentSite()->whereNotNull('group')->where('group', '!=', '')->distinct()->orderBy('group')->pluck('group')->all();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'group' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-.]*$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'topic' => ['nullable', 'string', 'max:500'],
            'hours' => ['nullable', 'string', 'max:160'],
            'order' => ['nullable', 'integer', 'min:0'],
        ], ['phone.regex' => 'Numer może zawierać tylko cyfry, spacje oraz znaki + ( ) - .']);

        foreach (['group', 'role', 'phone', 'email', 'topic', 'hours'] as $key) {
            $data[$key] = trim((string) ($data[$key] ?? '')) ?: null;
        }
        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
