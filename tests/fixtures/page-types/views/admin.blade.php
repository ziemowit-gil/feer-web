{{-- Sekcja pól testowego typu strony (PageTypeRegistryTest). --}}
<div data-type-fields="fixture_type" class="{{ $currentType === 'fixture_type' ? '' : 'hidden' }}">
    <label for="td-fixture-note" class="{{ $lbl }}">Notatka testowa</label>
    <input type="text" id="td-fixture-note" name="type_data[fixture_note]" value="{{ $td['fixture_note'] ?? '' }}" class="{{ $inp }}">
    <template data-td-template>{!! $recRow('fixture_rows', ['text' => ['label' => 'Wiersz testowy']], 'Usuń wiersz', null, [], 'flex gap-2') !!}</template>
</div>
