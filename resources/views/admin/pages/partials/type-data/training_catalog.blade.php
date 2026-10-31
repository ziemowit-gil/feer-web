{{-- ═══ KATALOG SZKOLEŃ ═══ --}}
<div data-type-fields="training_catalog" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'training_catalog' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Katalog szkoleń</p>
    <div>
        <label for="td-tc-lead" class="{{ $lbl }}">Lead</label>
        <textarea id="td-tc-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <label class="flex items-start gap-3 text-sm text-ink">
        <input type="hidden" name="type_data[show_slider]" value="0">
        <input type="checkbox" name="type_data[show_slider]" value="1" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(($td['show_slider'] ?? '1') == '1')>
        <span><span class="font-bold">Slider wyróżnionych szkoleń nad listą</span> <span class="block text-ink">Pokazuje szkolenia oznaczone jako wyróżnione (w stylu slidera ze strony głównej).</span></span>
    </label>
    <p class="text-xs text-muted">Lista szkoleń powstaje sama z podstron tej strony o typie „Szkolenie”. Dodaj je jako podstrony katalogu.</p>
</div>
