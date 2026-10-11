{{-- ═══ STUDIUM PRZYPADKU ═══ --}}
<div data-type-fields="case_study" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'case_study' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Studium przypadku</p>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-cs-client" class="{{ $lbl }}">Klient / partner</label><input type="text" id="td-cs-client" name="type_data[client]" value="{{ $td['client'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-cs-sector" class="{{ $lbl }}">Sektor</label><input type="text" id="td-cs-sector" name="type_data[sector]" value="{{ $td['sector'] ?? '' }}" placeholder="np. Administracja publiczna" class="{{ $inp }}"></div>
        <div><label for="td-cs-period" class="{{ $lbl }}">Okres realizacji</label><input type="text" id="td-cs-period" name="type_data[period]" value="{{ $td['period'] ?? '' }}" placeholder="np. 2025–2026" class="{{ $inp }}"></div>
    </div>
    <div>
        <label for="td-cs-lead" class="{{ $lbl }}">Lead</label>
        <textarea id="td-cs-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-cs-challenge" class="{{ $lbl }}">Wyzwanie</label><textarea id="td-cs-challenge" name="type_data[challenge]" rows="5" class="{{ $inp }}">{{ $td['challenge'] ?? '' }}</textarea></div>
        <div><label for="td-cs-solution" class="{{ $lbl }}">Rozwiązanie</label><textarea id="td-cs-solution" name="type_data[solution]" rows="5" class="{{ $inp }}">{{ $td['solution'] ?? '' }}</textarea></div>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Efekty w liczbach</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('results') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input type="text" name="type_data[results][{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="np. 98%" aria-label="Wartość" class="{{ $inp }} font-bold">
                    <input type="text" name="type_data[results][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="np. kryteriów WCAG spełnionych" aria-label="Opis wartości" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń efekt"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj efekt</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                <input type="text" name="type_data[results][__INDEX__][value]" placeholder="np. 98%" aria-label="Wartość" class="{{ $inp }} font-bold">
                <input type="text" name="type_data[results][__INDEX__][label]" placeholder="np. kryteriów WCAG spełnionych" aria-label="Opis wartości" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń efekt"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="grid gap-3 sm:grid-cols-[2fr_1fr]">
        <div><label for="td-cs-quote" class="{{ $lbl }}">Cytat klienta</label><textarea id="td-cs-quote" name="type_data[quote]" rows="2" class="{{ $inp }}">{{ $td['quote'] ?? '' }}</textarea></div>
        <div><label for="td-cs-quote-author" class="{{ $lbl }}">Autor cytatu</label><input type="text" id="td-cs-quote-author" name="type_data[quote_author]" value="{{ $td['quote_author'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-cs-cta-label" class="{{ $lbl }}">Przycisk CTA — etykieta</label><input type="text" id="td-cs-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Porozmawiajmy o Twoim projekcie" class="{{ $inp }}"></div>
        <div><label for="td-cs-cta-url" class="{{ $lbl }}">Przycisk CTA — adres</label><input type="text" id="td-cs-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="/kontakt" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się po sekcji „Rozwiązanie" jako rozwinięcie.</p>
</div>
