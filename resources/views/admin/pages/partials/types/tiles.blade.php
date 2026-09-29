{{-- Sekcja formularza strony: typ "tiles" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Siatka kafelków --}}
@php $tilesRows = array_values((array) old('tiles', $page->tiles ?? [])); @endphp
<div data-tiles-fields class="space-y-5 border-t border-gray-100 pt-5">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kafelki</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Każdy kafelek to link z ikoną Bootstrap Icons (<code>bi-*</code>). Ustaw szerokość i układ indywidualnie dla każdego kafelka.
        Dla typu <strong>Siatka kafelków</strong> kafelki stanowią główną treść strony; dla innych typów wyświetlają się jako dodatkowa sekcja pod treścią.
    </p>

    <div data-repeater>
        <div data-repeater-rows class="space-y-3">
            @foreach ($tilesRows as $i => $tile)
                <div data-repeater-row class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid gap-2 sm:grid-cols-[2fr_3fr]">
                        <input type="text" name="tiles[{{ $i }}][label]" value="{{ $tile['label'] ?? '' }}" placeholder="Etykieta kafelka" aria-label="Etykieta kafelka {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <input type="text" name="tiles[{{ $i }}][url]" value="{{ $tile['url'] ?? '' }}" placeholder="Adres (URL lub /sciezka)" aria-label="Adres linku {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[2fr_1fr]">
                        <input type="text" name="tiles[{{ $i }}][icon]" value="{{ $tile['icon'] ?? '' }}" placeholder="Ikona Bootstrap, np. bi-rocket-takeoff" aria-label="Ikona {{ $i + 1 }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                        <input type="text" name="tiles[{{ $i }}][color]" value="{{ $tile['color'] ?? '' }}" placeholder="Kolor, np. #2563eb" aria-label="Kolor {{ $i + 1 }}" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <fieldset>
                            <legend class="mb-1 text-xs font-bold text-muted">Szerokość</legend>
                            <div class="flex gap-1.5" role="radiogroup">
                                @foreach ([1 => '1', 2 => '2', 3 => '3'] as $cv => $cl)
                                    <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300">
                                        <input type="radio" name="tiles[{{ $i }}][cols]" value="{{ $cv }}" {{ (int)($tile['cols'] ?? 1) === $cv ? 'checked' : '' }} class="sr-only"> {{ $cl }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="mb-1 text-xs font-bold text-muted">Układ</legend>
                            <div class="flex gap-1.5" role="radiogroup">
                                <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300">
                                    <input type="radio" name="tiles[{{ $i }}][strip]" value="0" {{ ! ($tile['strip'] ?? false) ? 'checked' : '' }} class="sr-only"> Karta
                                </label>
                                <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300">
                                    <input type="radio" name="tiles[{{ $i }}][strip]" value="1" {{ ($tile['strip'] ?? false) ? 'checked' : '' }} class="sr-only"> Pasek
                                </label>
                            </div>
                        </fieldset>
                        <label class="flex cursor-pointer items-center gap-1.5">
                            <input type="hidden" name="tiles[{{ $i }}][is_negative]" value="0">
                            <input type="checkbox" name="tiles[{{ $i }}][is_negative]" value="1" {{ ($tile['is_negative'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-xs font-bold">Negatyw</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń kafelek"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek</button>
        <template data-repeater-template>
            <div data-repeater-row class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="grid gap-2 sm:grid-cols-[2fr_3fr]">
                    <input type="text" name="tiles[__INDEX__][label]" placeholder="Etykieta kafelka" aria-label="Etykieta kafelka" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    <input type="text" name="tiles[__INDEX__][url]" placeholder="Adres (URL lub /sciezka)" aria-label="Adres linku" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                </div>
                <div class="grid gap-2 sm:grid-cols-[2fr_1fr]">
                    <input type="text" name="tiles[__INDEX__][icon]" placeholder="Ikona Bootstrap, np. bi-rocket-takeoff" aria-label="Ikona" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                    <input type="text" name="tiles[__INDEX__][color]" placeholder="Kolor, np. #2563eb" aria-label="Kolor" class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">
                </div>
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                    <fieldset>
                        <legend class="mb-1 text-xs font-bold text-muted">Szerokość</legend>
                        <div class="flex gap-1.5" role="radiogroup">
                            <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300"><input type="radio" name="tiles[__INDEX__][cols]" value="1" checked class="sr-only"> 1</label>
                            <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300"><input type="radio" name="tiles[__INDEX__][cols]" value="2" class="sr-only"> 2</label>
                            <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300"><input type="radio" name="tiles[__INDEX__][cols]" value="3" class="sr-only"> 3</label>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="mb-1 text-xs font-bold text-muted">Układ</legend>
                        <div class="flex gap-1.5" role="radiogroup">
                            <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300"><input type="radio" name="tiles[__INDEX__][strip]" value="0" checked class="sr-only"> Karta</label>
                            <label class="flex cursor-pointer items-center gap-1 rounded border px-2 py-1 text-xs transition has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand border-gray-300"><input type="radio" name="tiles[__INDEX__][strip]" value="1" class="sr-only"> Pasek</label>
                        </div>
                    </fieldset>
                    <label class="flex cursor-pointer items-center gap-1.5">
                        <input type="hidden" name="tiles[__INDEX__][is_negative]" value="0">
                        <input type="checkbox" name="tiles[__INDEX__][is_negative]" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-xs font-bold">Negatyw</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-1">
                    <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                    <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                    <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń kafelek"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                </div>
            </div>
        </template>
    </div>
</div>
