{{-- ═══ DOKUMENTY ═══ --}}
<div data-type-fields="documents" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'documents' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Dokumenty</p>
    <div>
        <label for="td-documents-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-documents-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Dokumenty (grupa, tytuł, adres pliku lub strony, opis)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('docs') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_2fr_2fr_auto]">
                    <input type="text" name="type_data[docs][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa, np. Statut" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł dokumentu" aria-label="Tytuł" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Opis (opcjonalnie)" aria-label="Opis" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj dokument</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_2fr_2fr_auto]">
                <input type="text" name="type_data[docs][__INDEX__][group]" value="" placeholder="Grupa, np. Statut" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][title]" value="" placeholder="Tytuł dokumentu" aria-label="Tytuł" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][url]" value="" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][note]" value="" placeholder="Opis (opcjonalnie)" aria-label="Opis" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Pliki wgrane w zakładce „Pliki” tej strony pokazują się dodatkowo jako karty „Pliki do pobrania”.</p>
</div>
