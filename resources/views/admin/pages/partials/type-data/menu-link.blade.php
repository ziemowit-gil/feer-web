{{-- ═══ WŁASNY LINK W MENU ═══ --}}
<div data-type-fields="menu_link" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'menu_link' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Własny link w menu</p>
    <p class="text-sm text-muted">Ta podstrona nie ma treści — w menu jej tytuł prowadzi od razu pod adres poniżej.</p>
    <div>
        <label for="td-menu-link-url" class="{{ $lbl }}">Adres</label>
        <input type="text" id="td-menu-link-url" name="type_data[target_url]" value="{{ $td['target_url'] ?? '' }}" placeholder="/kontakt lub https://…"
            @error('type_data.target_url') aria-invalid="true" aria-describedby="td-menu-link-url-error" @enderror class="{{ $inp }}">
        <p class="mt-1 text-xs text-muted">Ścieżka na tej stronie (zaczyna się od /) albo pełny adres http(s).</p>
        @error('type_data.target_url') <p id="td-menu-link-url-error" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <label class="flex items-start gap-2 text-sm">
        <input type="hidden" name="type_data[new_tab]" value="0">
        <input type="checkbox" name="type_data[new_tab]" value="1" @checked(! empty($td['new_tab'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
        <span><span class="font-bold">Otwieraj w nowej karcie</span> — przy linku pojawi się informacja dla czytników ekranu.</span>
    </label>
</div>
