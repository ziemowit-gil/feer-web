{{-- Sekcja formularza strony: typ "training" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Instytucja szkoleniowa --}}
<div data-training-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'training_institution' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Instytucja szkoleniowa</p>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="training_manager_name" class="mb-1 block text-sm font-bold">Imię i nazwisko kierownika</label>
            <input type="text" id="training_manager_name" name="training_manager_name"
                value="{{ old('training_manager_name', $page->training_manager_name) }}"
                placeholder="np. Jan Kowalski"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('training_manager_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="training_manager_title" class="mb-1 block text-sm font-bold">Stanowisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="text" id="training_manager_title" name="training_manager_title"
                value="{{ old('training_manager_title', $page->training_manager_title) }}"
                placeholder="np. Kierownik szkolenia"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('training_manager_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="training_ris_number" class="mb-1 block text-sm font-bold">Numer wpisu RIS <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="text" id="training_ris_number" name="training_ris_number"
                value="{{ old('training_ris_number', $page->training_ris_number) }}"
                placeholder="np. 2.18/00001/2024"
                class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
            @error('training_ris_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="training_bur_number" class="mb-1 block text-sm font-bold">Numer wpisu BUR <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="text" id="training_bur_number" name="training_bur_number"
                value="{{ old('training_bur_number', $page->training_bur_number) }}"
                placeholder="np. 2.18/00001/2024"
                class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
            @error('training_bur_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="training_extra_info" class="mb-1 block text-sm font-bold">Dodatkowe informacje <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="training_extra_info" name="training_extra_info" rows="4"
            placeholder="Np. zakres szkoleń, certyfikaty, obszary działalności."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('training_extra_info', $page->training_extra_info) }}</textarea>
        @error('training_extra_info') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="training_bur_note" class="mb-1 block text-sm font-bold">Wyjaśnienie braku wpisu w BUR <span class="font-normal text-muted">(opcjonalnie — wyróżnione na stronie)</span></label>
        <textarea id="training_bur_note" name="training_bur_note" rows="3"
            placeholder="np. Nie figurujemy w BUR, ponieważ…"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('training_bur_note', $page->training_bur_note) }}</textarea>
        <p class="mt-1 text-xs text-muted">Pojawi się jako wyróżniona ramka z informacją. Wypełnij, jeśli nie jesteście wpisani do BUR i chcecie to wyjaśnić.</p>
        @error('training_bur_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
