{{-- ═══ PORADNIK KROK PO KROKU ═══ --}}
<div data-type-fields="guide" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'guide' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Poradnik krok po kroku</p>

    <div>
        <label for="td-guide-lead" class="{{ $lbl }}">Lead (czego dotyczy poradnik)</label>
        <textarea id="td-guide-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-guide-time" class="{{ $lbl }}">Szacowany czas</label><input type="text" id="td-guide-time" name="type_data[time_estimate]" value="{{ $td['time_estimate'] ?? '' }}" placeholder="np. 15 minut" class="{{ $inp }}"></div>
        <div>
            <label for="td-guide-level" class="{{ $lbl }}">Poziom</label>
            <select id="td-guide-level" name="type_data[level]" class="{{ $inp }}">
                @foreach (\App\Models\Page::GUIDE_LEVELS as $lv => $ll)
                    <option value="{{ $lv }}" @selected(($td['level'] ?? 'basic') === $lv)>{{ $ll }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Zanim zaczniesz — wymagania</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('requirements') as $i => $row)
                <div data-td-row class="flex gap-2">
                    <input type="text" name="type_data[requirements][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Konto w systemie" aria-label="Wymaganie" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wymaganie"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wymaganie</button>
        <template data-td-template>
            <div data-td-row class="flex gap-2">
                <input type="text" name="type_data[requirements][__INDEX__][text]" placeholder="np. Konto w systemie" aria-label="Wymaganie" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wymaganie"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Kroki (kolejność = kolejność na stronie)</p>
        <div data-td-rows class="space-y-3">
            @foreach ($tdRows('steps') as $i => $row)
                <div data-td-row class="p-3 rounded-md bg-white">
                    <div class="flex gap-2">
                        <input type="text" name="type_data[steps][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł kroku" aria-label="Tytuł kroku" class="{{ $inp }} font-bold">
                        <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń krok"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    <textarea name="type_data[steps][{{ $i }}][text]" rows="3" placeholder="Opis kroku (można używać akapitów)" aria-label="Opis kroku" class="{{ $inp }} mt-2">{{ $row['text'] ?? '' }}</textarea>
                    <input type="text" name="type_data[steps][{{ $i }}][tip]" value="{{ $row['tip'] ?? '' }}" placeholder="Wskazówka (opcjonalnie)" aria-label="Wskazówka" class="{{ $inp }} mt-2">
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj krok</button>
        <template data-td-template>
            <div data-td-row class="p-3 rounded-md bg-white">
                <div class="flex gap-2">
                    <input type="text" name="type_data[steps][__INDEX__][title]" placeholder="Tytuł kroku" aria-label="Tytuł kroku" class="{{ $inp }} font-bold">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń krok"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
                <textarea name="type_data[steps][__INDEX__][text]" rows="3" placeholder="Opis kroku (można używać akapitów)" aria-label="Opis kroku" class="{{ $inp }} mt-2"></textarea>
                <input type="text" name="type_data[steps][__INDEX__][tip]" placeholder="Wskazówka (opcjonalnie)" aria-label="Wskazówka" class="{{ $inp }} mt-2">
            </div>
        </template>
    </div>

    <div>
        <label for="td-guide-summary" class="{{ $lbl }}">Podsumowanie / co dalej</label>
        <textarea id="td-guide-summary" name="type_data[summary]" rows="3" class="{{ $inp }}">{{ $td['summary'] ?? '' }}</textarea>
    </div>
</div>
