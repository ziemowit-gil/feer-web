{{-- ═══ PRZEKIEROWANIE ═══ --}}
<div data-type-fields="redirect" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'redirect' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Przekierowanie</p>
    <div>
        <label for="td-redirect-url" class="{{ $lbl }}">Adres docelowy</label>
        <input type="text" id="td-redirect-url" name="type_data[target_url]" value="{{ $td['target_url'] ?? '' }}" placeholder="/nowy-adres lub https://…" class="{{ $inp }}">
        <p class="mt-1 text-xs text-muted">Ścieżka na tej stronie (zaczyna się od /) albo pełny adres http(s).</p>
    </div>
    <fieldset>
        <legend class="{{ $lbl }}">Co ma się stać po wejściu na tę stronę</legend>
        @php $rmode = $td['redirect_mode'] ?? 'notice'; @endphp
        <label class="mb-1 flex items-start gap-2 text-sm"><input type="radio" name="type_data[redirect_mode]" value="notice" @checked($rmode !== 'auto') class="mt-0.5 border-gray-300 text-brand focus:ring-brand"> <span><span class="font-bold">Pokaż stronę z informacją</span> i przycisk do nowego adresu (przyjazne dla osób korzystających z czytników ekranu).</span></label>
        <label class="flex items-start gap-2 text-sm"><input type="radio" name="type_data[redirect_mode]" value="auto" @checked($rmode === 'auto') class="mt-0.5 border-gray-300 text-brand focus:ring-brand"> <span><span class="font-bold">Przekieruj od razu</span> (301). W podglądzie z panelu zobaczysz stronę z informacją.</span></label>
    </fieldset>
    <div>
        <label for="td-redirect-msg" class="{{ $lbl }}">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="td-redirect-msg" name="type_data[redirect_message]" rows="2" placeholder="np. Ta treść znajduje się teraz w innym miejscu." class="{{ $inp }}">{{ $td['redirect_message'] ?? '' }}</textarea>
    </div>
    <div>
        <label for="td-redirect-label" class="{{ $lbl }}">Napis na przycisku <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="text" id="td-redirect-label" name="type_data[redirect_label]" value="{{ $td['redirect_label'] ?? '' }}" placeholder="Przejdź do nowej strony" class="{{ $inp }}">
    </div>
</div>
