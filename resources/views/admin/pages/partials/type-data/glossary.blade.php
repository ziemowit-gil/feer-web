{{-- ═══ SŁOWNIK POJĘĆ ═══ --}}
<div data-type-fields="glossary" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'glossary' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Słownik pojęć</p>

    <div>
        <label for="td-glossary-lead" class="{{ $lbl }}">Lead (o czym jest słownik)</label>
        <textarea id="td-glossary-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Hasła (na stronie sortowane alfabetycznie, z indeksem liter i wyszukiwarką)</p>
        <div data-td-rows class="space-y-3">
            @foreach ($tdRows('terms') as $i => $row)
                <div data-td-row class="p-3 rounded-md bg-white">
                    <div class="flex gap-2">
                        <input type="text" name="type_data[terms][{{ $i }}][term]" value="{{ $row['term'] ?? '' }}" placeholder="Hasło, np. WCAG" aria-label="Hasło" class="{{ $inp }} font-bold">
                        <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń hasło"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    <textarea name="type_data[terms][{{ $i }}][definition]" rows="3" placeholder="Definicja" aria-label="Definicja" class="{{ $inp }} mt-2">{{ $row['definition'] ?? '' }}</textarea>
                    <input type="text" name="type_data[terms][{{ $i }}][related]" value="{{ $row['related'] ?? '' }}" placeholder="Hasła powiązane, po przecinku (opcjonalnie)" aria-label="Hasła powiązane" class="{{ $inp }} mt-2">
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj hasło</button>
        <template data-td-template>
            <div data-td-row class="p-3 rounded-md bg-white">
                <div class="flex gap-2">
                    <input type="text" name="type_data[terms][__INDEX__][term]" placeholder="Hasło, np. WCAG" aria-label="Hasło" class="{{ $inp }} font-bold">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń hasło"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
                <textarea name="type_data[terms][__INDEX__][definition]" rows="3" placeholder="Definicja" aria-label="Definicja" class="{{ $inp }} mt-2"></textarea>
                <input type="text" name="type_data[terms][__INDEX__][related]" placeholder="Hasła powiązane, po przecinku (opcjonalnie)" aria-label="Hasła powiązane" class="{{ $inp }} mt-2">
            </div>
        </template>
    </div>
</div>
