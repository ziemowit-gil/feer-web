{{-- Wiersz-nagłówek sekcji kafelków w edytorze strony. Zmienne: $i (indeks albo „__INDEX__"), $heading. --}}
<div data-repeater-row class="rounded-xl border-2 border-brand bg-brand-light p-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1">
            <label class="mb-1 block text-xs font-bold text-ink" for="tile-section-{{ $i }}">Sekcja — nagłówek</label>
            <input type="text" id="tile-section-{{ $i }}" name="tiles[{{ $i }}][heading]" value="{{ $heading ?? '' }}" maxlength="160" placeholder="np. Dla uczestników"
                class="w-full rounded-lg border-gray-300 text-sm font-bold focus:border-brand focus:ring-brand">
        </div>
        <div class="flex items-center gap-1">
            <button type="button" data-repeater-move="up" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-700 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń sekcję wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
            <button type="button" data-repeater-move="down" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-700 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń sekcję niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
            <button type="button" data-repeater-remove class="flex h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
        </div>
    </div>
    <p class="mt-1 text-xs text-ink">Kolejne kafelki należą do tej sekcji — aż do następnego nagłówka.</p>
</div>
