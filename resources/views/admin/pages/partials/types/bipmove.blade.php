{{-- Sekcja formularza strony: typ "bipmove" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Bip-Move — komunikat o przeniesieniu do BIP --}}
<div data-bipmove-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'bip_move' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Przeniesiono do BIP</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Ten typ wyświetla gotowy komunikat, że treść została przeniesiona do Biuletynu Informacji Publicznej, wraz z oficjalnym logo BIP i wyjaśnieniem oddzielenia warstwy reprezentacyjnej od formalnej. Poniżej możesz doprecyzować link i dodatkową informację.
    </p>

    <div>
        <label for="bip_move_url" class="mb-1 block text-sm font-bold">Bezpośredni link do treści w BIP <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="url" id="bip_move_url" name="bip_move_url" value="{{ old('bip_move_url', $page->bip_move_url) }}"
            placeholder="https://bip… — puste = ogólny adres BIP z Ustawień"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        <p class="mt-1 text-xs text-muted">Puste = przycisk poprowadzi do ogólnego adresu BIP z „Ustawienia → Media i BIP". Dodatkowy opis wpiszesz w polu „Dodatkowa informacja" poniżej.</p>
        @error('bip_move_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="bip_move_note" class="mb-1 block text-sm font-bold">Dodatkowa informacja <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="bip_move_note" name="bip_move_note" rows="3" placeholder="np. W BIP znajdziesz sprawozdania, statut i dokumenty formalne fundacji."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bip_move_note', $page->bip_move_note) }}</textarea>
        @error('bip_move_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
