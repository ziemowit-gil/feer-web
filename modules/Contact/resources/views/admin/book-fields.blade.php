{{-- Pola typu „Kontakt — książka”: wygląd książki (osoby są w module „Książka telefoniczna”). Zmienne z type-data-fields: $td, $inp, $lbl, $box. --}}
@php
    $bookCount = \App\Models\PhoneBookEntry::forCurrentSite()->count();
    $bookGroups = \App\Models\PhoneBookEntry::forCurrentSite()->whereNotNull('group')->where('group', '!=', '')->distinct()->orderBy('group')->pluck('group');
    $bookModuleOn = $siteSettings->isModuleEnabled('phone_book');
    $hasBookAdmin = $bookModuleOn && \Illuminate\Support\Facades\Route::has('admin.ksiazka-telefoniczna.index');
@endphp
<div data-type-fields="contact_book" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'contact_book' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kontakt — książka</p>

    <div class="{{ $box }}">
        <p class="text-sm text-ink">
            Osoby w książce: <strong>{{ $bookCount }}</strong>.
            @if ($hasBookAdmin)
                Dodajesz je i zmieniasz w <a href="{{ route('admin.ksiazka-telefoniczna.index') }}" class="font-bold text-brand-dark underline underline-offset-4">Komunikacja → Książka telefoniczna</a>; ta strona pokazuje tę samą listę.
            @else
                Włącz moduł „Książka telefoniczna” (Ustawienia → Moduły), żeby dodawać i zmieniać osoby w panelu.
            @endif
        </p>
    </div>

    <div>
        <label for="td-contactbook-lead" class="{{ $lbl }}">Lead (np. kiedy dzwonić, a kiedy pisać)</label>
        <textarea id="td-contactbook-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}">
        <p class="text-xs font-bold uppercase text-muted">Wygląd książki</p>
        <div>
            <label for="td-contactbook-layout" class="{{ $lbl }}">Układ</label>
            <select id="td-contactbook-layout" name="type_data[layout]" class="{{ $inp }}">
                <option value="table" @selected(($td['layout'] ?? 'table') !== 'cards')>Tabele (każdy dział to tabela: osoba, numer, sprawa, kiedy dzwonić)</option>
                <option value="cards" @selected(($td['layout'] ?? '') === 'cards')>Karty (osoby jako kafelki)</option>
            </select>
        </div>
        <div>
            <label for="td-contactbook-sort" class="{{ $lbl }}">Kolejność osób</label>
            <select id="td-contactbook-sort" name="type_data[sort]" class="{{ $inp }}">
                <option value="manual" @selected(($td['sort'] ?? 'manual') !== 'alpha')>Jak w panelu (ręcznie)</option>
                <option value="alpha" @selected(($td['sort'] ?? '') === 'alpha')>Alfabetycznie (działy i osoby)</option>
            </select>
        </div>
        <div>
            <label for="td-contactbook-group" class="{{ $lbl }}">Tylko dział (opcjonalnie)</label>
            <input type="text" id="td-contactbook-group" name="type_data[group]" value="{{ $td['group'] ?? '' }}" maxlength="120" list="td-contactbook-groups" class="{{ $inp }}" placeholder="Puste = wszystkie działy, np. Zarząd">
            <datalist id="td-contactbook-groups">
                @foreach ($bookGroups as $bookGroup)<option value="{{ $bookGroup }}"></option>@endforeach
            </datalist>
            <p class="mt-1 text-xs text-muted">Pozwala zrobić kilka podstron książki, np. osobną dla Zarządu i koordynatorów (z ich godzinami „Kiedy dzwonić”).</p>
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="type_data[hide_numbers]" value="1" @checked(! empty($td['hide_numbers'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            <span><span class="font-bold">Ukryj numery za przyciskiem</span>
                <span class="block text-xs text-muted">Odwiedzający odsłania numer najechaniem, klawiaturą albo kliknięciem. To tylko ukrycie wizualne (utrudnia przypadkowe kopiowanie): numer nadal jest w kodzie strony.</span></span>
        </label>
    </div>
</div>
