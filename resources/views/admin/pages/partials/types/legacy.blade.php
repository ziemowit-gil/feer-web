{{-- Sekcja formularza strony: typ "legacy" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- „Prezentacja tego, co było" — poprzednik kontynuowany przez FEER --}}
<div data-legacy-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'legacy' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Prezentacja tego, co było</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Ten typ prezentuje historię i działalność podmiotu poprzedzającego FEER, którego działalność kontynuujemy. Historię i dokonania opisz w polu „Treść" (edytor); możesz też dołączyć galerię zdjęć.
    </p>
    <div>
        <label for="legacy_name" class="mb-1 block text-sm font-bold">Nazwa poprzednika</label>
        <input type="text" id="legacy_name" name="legacy_name" value="{{ old('legacy_name', $page->legacy_name) }}" placeholder="np. Stowarzyszenie „Dawna Nazwa""
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        @error('legacy_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="legacy_intro" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="legacy_intro" name="legacy_intro" rows="3" placeholder="Krótko: czym był ten podmiot i jak FEER kontynuuje jego działalność."
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('legacy_intro', $page->legacy_intro) }}</textarea>
        @error('legacy_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
