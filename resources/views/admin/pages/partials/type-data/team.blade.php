{{-- ═══ ZESPÓŁ ═══ --}}
<div data-type-fields="team" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'team' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zespół</p>
    <div>
        <label for="td-team-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-team-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Osoby (imię i nazwisko, rola, opis, e-mail)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('members') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]">
                    <input type="text" name="type_data[members][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Rola" aria-label="Rola" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][email]" value="{{ $row['email'] ?? '' }}" placeholder="E-mail (opcjonalnie)" aria-label="E-mail" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj osobę</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]">
                <input type="text" name="type_data[members][__INDEX__][name]" value="" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][role]" value="" placeholder="Rola" aria-label="Rola" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][text]" value="" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][email]" value="" placeholder="E-mail (opcjonalnie)" aria-label="E-mail" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się nad listą osób.</p>
</div>
