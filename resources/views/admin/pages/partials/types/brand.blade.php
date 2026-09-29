{{-- Sekcja formularza strony: typ "brand" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- ====== MARKA — IDENTYFIKACJA WIZUALNA ====== --}}
@php $brandSectionsValue = array_values((array) old('brand_sections', $page->brand_sections ?? [])); @endphp
<div data-brand-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'brand_assets' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Identyfikacja wizualna</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Strona chroniona indywidualnym loginem i hasłem. Pliki do pobrania wgrywasz w zakładce „Pliki" — tam przypisujesz plik do sekcji zdefiniowanej poniżej.
    </p>

    {{-- Link do brandbooka --}}
    <div>
        <label for="brand_brandbook_url" class="mb-1 block text-sm font-bold">URL brandbooka <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="url" id="brand_brandbook_url" name="brand_brandbook_url"
            value="{{ old('brand_brandbook_url', $page->brand_brandbook_url) }}"
            placeholder="https://…"
            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        <p class="mt-1 text-xs text-muted">Wyświetli się jako przycisk „Pobierz brandbook" na stronie.</p>
        @error('brand_brandbook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Sekcje plików (repeater) --}}
    <div>
        <p class="mb-2 text-sm font-bold">Sekcje plików</p>
        <p class="mb-3 text-xs text-muted">Każda sekcja to osobna grupa plików na stronie (np. „Logotyp kolorowy", „Bannery"). Dodaj sekcje, a potem przypisz do nich pliki w zakładce „Pliki".</p>

        <div data-repeater data-repeater-name="brand_sections">
            <div data-repeater-rows class="space-y-2">
                @foreach ($brandSectionsValue as $bsIndex => $bs)
                    <div data-repeater-row class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 p-2">
                        <div class="flex flex-1 gap-2">
                            <div class="flex-1">
                                <label class="sr-only">Tytuł sekcji</label>
                                <input type="text" name="brand_sections[{{ $bsIndex }}][title]"
                                    value="{{ old('brand_sections.' . $bsIndex . '.title', $bs['title'] ?? '') }}"
                                    placeholder="Tytuł sekcji, np. Logotyp — wersja kolorowa"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div class="w-40">
                                <label class="sr-only">Klucz (ID sekcji)</label>
                                <input type="text" name="brand_sections[{{ $bsIndex }}][key]"
                                    value="{{ old('brand_sections.' . $bsIndex . '.key', $bs['key'] ?? '') }}"
                                    placeholder="klucz-sekcji"
                                    class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                        <button type="button" data-repeater-remove
                            class="flex-none text-muted hover:text-red-600" title="Usuń">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            <span class="sr-only">Usuń sekcję</span>
                        </button>
                    </div>
                @endforeach
            </div>
            <template data-repeater-template>
                <div data-repeater-row class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 p-2 mt-2">
                    <div class="flex flex-1 gap-2">
                        <div class="flex-1">
                            <label class="sr-only">Tytuł sekcji</label>
                            <input type="text" name="brand_sections[__INDEX__][title]"
                                placeholder="Tytuł sekcji, np. Logotyp — wersja kolorowa"
                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"
                                data-autoslug-source data-autoslug-target="brand_sections[__INDEX__][key]">
                        </div>
                        <div class="w-40">
                            <label class="sr-only">Klucz (ID sekcji)</label>
                            <input type="text" name="brand_sections[__INDEX__][key]"
                                placeholder="klucz-sekcji"
                                class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <button type="button" data-repeater-remove
                        class="flex-none text-muted hover:text-red-600" title="Usuń">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span class="sr-only">Usuń sekcję</span>
                    </button>
                </div>
            </template>
            <button type="button" data-repeater-add
                class="mt-3 inline-flex items-center gap-1 rounded border border-dashed border-gray-300 bg-white px-3 py-1 text-sm text-muted hover:bg-gray-50">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj sekcję
            </button>
        </div>
    </div>

    @if ($page->exists && $page->isBrandAssets())
        <div class="rounded border border-gray-200 bg-gray-50 p-3">
            <a href="{{ route('admin.podstrony.dostep.index', $page) }}"
                class="inline-flex items-center gap-2 font-bold text-brand hover:text-brand-dark hover:underline">
                <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                Zarządzaj użytkownikami dostępu ({{ $page->brandAccessUsers()->count() }})
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
            <p class="mt-1 text-xs text-muted">Generuj loginy i hasła, dezaktywuj konta, eksportuj CSV.</p>
        </div>
    @endif
</div>
