{{-- ═══ SKRÓT (doktyp shortcut w TYPO3) ═══ --}}
@php
    $scMode = $td['shortcut_mode'] ?? 'first_child';
    $scTarget = (int) ($td['target_page_id'] ?? 0);
    // Strony bieżącej witryny w kolejności drzewa (z wcięciem); bez tej strony.
    $scOptions = collect(\App\Support\SubpageList::pickerOptions())->reject(fn ($o) => $page->exists && $o['id'] === $page->id);
@endphp
<div data-type-fields="shortcut" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'shortcut' ? '' : 'hidden' }}" x-data="{ mode: @js($scMode) }">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Skrót</p>
    <p class="text-sm text-muted">Ta strona nie ma treści — jej adres i pozycja w menu prowadzą od razu do strony docelowej.</p>
    <fieldset>
        <legend class="{{ $lbl }}">Dokąd prowadzi</legend>
        <div class="space-y-1.5">
            @foreach (\App\Models\Page::SHORTCUT_MODES as $value => $text)
                <label class="flex min-h-6 items-center gap-2 text-sm">
                    <input type="radio" name="type_data[shortcut_mode]" value="{{ $value }}" x-model="mode" @checked($scMode === $value) class="border-gray-500 text-brand-dark focus:ring-brand-dark">
                    {{ $text }}
                </label>
            @endforeach
        </div>
        <p class="mt-1 text-xs text-muted">„Pierwsza podstrona” to pierwsza opublikowana podstrona w menu — zmieni się sama, gdy zmienisz kolejność.</p>
    </fieldset>
    <div x-show="mode === 'page'" @if ($scMode !== 'page') x-cloak @endif>
        <label for="td-shortcut-target" class="{{ $lbl }}">Strona docelowa</label>
        <select id="td-shortcut-target" name="type_data[target_page_id]" class="{{ $inp }}"
            @error('type_data.target_page_id') aria-invalid="true" aria-describedby="td-shortcut-target-error" @enderror>
            <option value="">— wybierz stronę —</option>
            @foreach ($scOptions as $o)
                <option value="{{ $o['id'] }}" @selected($scTarget === $o['id'])>{{ str_repeat('— ', $o['depth']) }}{{ $o['title'] }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-muted">Lista pokazuje opublikowane strony tej witryny.</p>
        @error('type_data.target_page_id') <p id="td-shortcut-target-error" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
