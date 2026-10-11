{{-- ═══ CENNIK I OPŁATY ═══ --}}
<div data-type-fields="pricing" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'pricing' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Cennik i opłaty</p>
    <div>
        <label for="td-pricing-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-pricing-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Pozycje cennika (grupa, nazwa, cena, opis)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('price_rows') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_1fr_2fr_auto]">
                    <input type="text" name="type_data[price_rows][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa (opcjonalnie)" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" placeholder="Nazwa usługi" aria-label="Nazwa usługi" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" placeholder="np. 85 zł" aria-label="Cena" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pozycję</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_1fr_2fr_auto]">
                <input type="text" name="type_data[price_rows][__INDEX__][group]" value="" placeholder="Grupa (opcjonalnie)" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][item]" value="" placeholder="Nazwa usługi" aria-label="Nazwa usługi" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][price]" value="" placeholder="np. 85 zł" aria-label="Cena" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][note]" value="" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div>
        <label for="td-pricing-pay" class="{{ $lbl }}">Zasady płatności (prostym językiem)</label>
        <textarea id="td-pricing-pay" name="type_data[payment_info]" rows="5" class="{{ $inp }}" placeholder="np. Płać po otrzymaniu faktury. Nie rób przedpłat.">{{ $td['payment_info'] ?? '' }}</textarea>
        <p class="mt-1 text-xs text-muted">Akapity rozdzielaj pustą linią. Nie wpisuj numerów kont — podawaj je w mailu z fakturą.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-pr-cta-label" class="{{ $lbl }}">Przycisk — etykieta</label><input type="text" id="td-pr-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-pr-cta-url" class="{{ $lbl }}">Przycisk — adres</label><input type="text" id="td-pr-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się nad cennikiem.</p>
</div>
