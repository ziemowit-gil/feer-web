{{-- ═══ PARTNERZY I SPONSORZY ═══ --}}
<div data-type-fields="partners" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'partners' ? '' : 'hidden' }}">
    @php
        $partnerOptions = \App\Models\Partner::forCurrentSite()->orderBy('order')->orderBy('name')->get(['id', 'name']);
        $partnerSelect = function (string $name, $selected = null) use ($partnerOptions, $inp) {
            $h = '<select name="'.$name.'" aria-label="Partner z modułu" class="'.$inp.'"><option value="">— własny wpis (nazwa niżej) —</option>';
            foreach ($partnerOptions as $po) { $h .= '<option value="'.$po->id.'"'.((int) $selected === $po->id ? ' selected' : '').'>'.e($po->name).'</option>'; }
            return $h.'</select>';
        };
    @endphp
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Partnerzy i sponsorzy</p>
    <div>
        <label for="td-partners-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-partners-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="td-partners-layout" class="{{ $lbl }}">Układ</label>
            <select id="td-partners-layout" name="type_data[partners_layout]" class="{{ $inp }}">
                <option value="logos" @selected(($td['partners_layout'] ?? 'logos') === 'logos')>Ściana logotypów (same loga)</option>
                <option value="cards" @selected(($td['partners_layout'] ?? '') === 'cards')>Karty (logo, nazwa, opis roli)</option>
            </select>
        </div>
        <label class="flex items-start gap-2 pt-5 text-sm"><input type="hidden" name="type_data[partners_all]" value="0"><input type="checkbox" name="type_data[partners_all]" value="1" @checked(! empty($td['partners_all'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> <span><span class="font-bold">Pokaż też wszystkich partnerów z modułu</span><span class="block text-xs text-muted">Ci, których nie przypiszesz do grupy, trafią do grupy „Pozostali partnerzy”.</span></span></label>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Grupy (np. Partnerzy strategiczni, Sponsorzy, Patroni medialni) — nazwa i krótki opis</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('partner_groups') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="type_data[partner_groups][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Nazwa grupy" aria-label="Nazwa grupy" class="{{ $inp }}"> <input type="text" name="type_data[partner_groups][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis grupy (opcjonalnie)" aria-label="Opis grupy" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                <input type="text" name="type_data[partner_groups][__INDEX__][title]" value="" placeholder="Nazwa grupy" aria-label="Nazwa grupy" class="{{ $inp }}"> <input type="text" name="type_data[partner_groups][__INDEX__][text]" value="" placeholder="Opis grupy (opcjonalnie)" aria-label="Opis grupy" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Wpisy (grupa, partner z modułu albo własna nazwa, adres, logo, opis roli)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('partners') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1.4fr_1.2fr_1.2fr_1.2fr_1.4fr_auto]">
                    <input type="text" name="type_data[partners][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa" aria-label="Grupa" class="{{ $inp }}"> {!! $partnerSelect("type_data[partners][{$i}][partner_id]", $row['partner_id'] ?? null) !!} <input type="text" name="type_data[partners][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Własna nazwa" aria-label="Własna nazwa" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://…" aria-label="Adres strony" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][logo]" value="{{ $row['logo'] ?? '' }}" placeholder="Adres logo (własny wpis)" aria-label="Adres logo" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Opis roli (np. finansuje szkolenia)" aria-label="Opis roli" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1.4fr_1.2fr_1.2fr_1.2fr_1.4fr_auto]">
                <input type="text" name="type_data[partners][__INDEX__][group]" value="" placeholder="Grupa" aria-label="Grupa" class="{{ $inp }}"> {!! $partnerSelect('type_data[partners][__INDEX__][partner_id]') !!} <input type="text" name="type_data[partners][__INDEX__][name]" value="" placeholder="Własna nazwa" aria-label="Własna nazwa" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][url]" value="" placeholder="https://…" aria-label="Adres strony" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][logo]" value="" placeholder="Adres logo (własny wpis)" aria-label="Adres logo" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][note]" value="" placeholder="Opis roli" aria-label="Opis roli" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
        <p class="text-xs text-muted">Logo partnera z modułu bierze się automatycznie. Adres logo własnego wpisu możesz skopiować z Biblioteki multimediów.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-partners-cta-label" class="{{ $lbl }}">Przycisk „Zostań partnerem” — napis</label><input type="text" id="td-partners-cta-label" name="type_data[partners_cta_label]" value="{{ $td['partners_cta_label'] ?? '' }}" placeholder="Zostań partnerem" class="{{ $inp }}"></div>
        <div><label for="td-partners-cta-url" class="{{ $lbl }}">Przycisk — adres</label><input type="text" id="td-partners-cta-url" name="type_data[partners_cta_url]" value="{{ $td['partners_cta_url'] ?? '' }}" placeholder="/kontakt lub mailto:…" class="{{ $inp }}"></div>
    </div>
    <div>
        <label for="td-partners-thanks" class="{{ $lbl }}">Podziękowanie (na dole strony, opcjonalnie)</label>
        <textarea id="td-partners-thanks" name="type_data[partners_thanks]" rows="2" placeholder="np. Dziękujemy wszystkim, którzy wspierają nasze działania." class="{{ $inp }}">{{ $td['partners_thanks'] ?? '' }}</textarea>
    </div>
</div>
