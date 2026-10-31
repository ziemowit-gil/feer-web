{{-- ═══ AKREDYTACJE I JAKOŚĆ ═══ --}}
<div data-type-fields="training_accreditations" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'training_accreditations' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Akredytacje i jakość kształcenia</p>
    <div>
        <label for="td-ta-lead" class="{{ $lbl }}">Lead</label>
        <textarea id="td-ta-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Wpisy, akredytacje i certyfikaty</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('accreditations') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1.4fr_1.4fr_8rem_9rem_1fr_auto]">
                    <input type="text" name="type_data[accreditations][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Nazwa" aria-label="Nazwa" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][{{ $i }}][issuer]" value="{{ $row['issuer'] ?? '' }}" placeholder="Wydający (np. Wojewódzki Urząd Pracy)" aria-label="Wydający" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][{{ $i }}][number]" value="{{ $row['number'] ?? '' }}" placeholder="Numer" aria-label="Numer" class="{{ $inp }}">
                    <input type="date" name="type_data[accreditations][{{ $i }}][valid_until]" value="{{ $row['valid_until'] ?? '' }}" aria-label="Ważne do" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Adres dokumentu" aria-label="Adres dokumentu" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1.4fr_1.4fr_8rem_9rem_1fr_auto]">
                    <input type="text" name="type_data[accreditations][__INDEX__][name]" value="" placeholder="Nazwa" aria-label="Nazwa" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][__INDEX__][issuer]" value="" placeholder="Wydający (np. Wojewódzki Urząd Pracy)" aria-label="Wydający" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][__INDEX__][number]" value="" placeholder="Numer" aria-label="Numer" class="{{ $inp }}">
                    <input type="date" name="type_data[accreditations][__INDEX__][valid_until]" value="" aria-label="Ważne do" class="{{ $inp }}">
                    <input type="text" name="type_data[accreditations][__INDEX__][url]" value="" placeholder="Adres dokumentu" aria-label="Adres dokumentu" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Jakość w liczbach (np. wyniki ankiet)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('quality_stats') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input type="text" name="type_data[quality_stats][{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="np. 4,8/5" aria-label="Wartość" class="{{ $inp }}">
                    <input type="text" name="type_data[quality_stats][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="np. średnia ocena szkoleń w ankietach uczestników" aria-label="Opis wartości" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wskaźnik</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input type="text" name="type_data[quality_stats][__INDEX__][value]" value="" placeholder="np. 4,8/5" aria-label="Wartość" class="{{ $inp }}">
                    <input type="text" name="type_data[quality_stats][__INDEX__][label]" value="" placeholder="np. średnia ocena szkoleń w ankietach uczestników" aria-label="Opis wartości" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) to polityka jakości lub opis procedur. Dane rejestrowe instytucji (RIS, BUR) zostają w typie „Instytucja szkoleniowa”.</p>
</div>
