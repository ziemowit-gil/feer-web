{{-- ═══ REGULAMIN / DOKUMENT ═══ --}}
<div data-type-fields="regulation" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'regulation' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Regulamin lub dokument</p>
    <div>
        <label for="td-regulation-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-regulation-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-reg-from" class="{{ $lbl }}">Obowiązuje od</label><input type="text" id="td-reg-from" name="type_data[valid_from]" value="{{ $td['valid_from'] ?? '' }}" placeholder="np. 1 stycznia 2026" class="{{ $inp }}"></div>
        <div><label for="td-reg-ver" class="{{ $lbl }}">Wersja</label><input type="text" id="td-reg-ver" name="type_data[version]" value="{{ $td['version'] ?? '' }}" placeholder="np. 2.1" class="{{ $inp }}"></div>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Poprzednie wersje (nazwa, adres)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('versions') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_3fr_auto]">
                    <input type="text" name="type_data[versions][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="np. Wersja 2.0 (do 31.12.2025)" aria-label="Nazwa wersji" class="{{ $inp }}"> <input type="text" name="type_data[versions][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj poprzednią wersję</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_3fr_auto]">
                <input type="text" name="type_data[versions][__INDEX__][label]" value="" placeholder="np. Wersja 2.0 (do 31.12.2025)" aria-label="Nazwa wersji" class="{{ $inp }}"> <input type="text" name="type_data[versions][__INDEX__][url]" value="" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) to tekst regulaminu — nagłówki H2 i H3 tworzą automatyczny spis treści.</p>
</div>
