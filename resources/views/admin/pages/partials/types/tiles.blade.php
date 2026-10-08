{{-- Sekcja formularza strony: typ "tiles" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Siatka kafelków --}}
@php $tilesRows = array_values((array) old('tiles', $page->tiles ?? [])); @endphp
<div data-tiles-fields class="space-y-5 border-t border-gray-100 pt-5"
    x-data="{ on: @js((bool) old('tiles_enabled', $page->tiles_enabled ?? false) || ($currentType ?? 'standard') === 'tiles_grid'), isGrid: @js(($currentType ?? 'standard') === 'tiles_grid') }"
    @change.window="if ($event.target && $event.target.id === 'type') isGrid = $event.target.value === 'tiles_grid'">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kafelki</p>
    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3" x-show="! isGrid">
        <input type="hidden" name="tiles_enabled" value="0">
        <input type="checkbox" name="tiles_enabled" value="1" x-model="on" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
        <span>
            <span class="block text-sm font-bold">Kafelki na stronie</span>
            <span class="block text-xs text-muted">Włącz, aby dodać kafelki do tej strony (kolejność względem treści, kafelki i sekcje). Wyłączone: nic nie jest wyświetlane, a zapisane kafelki zostają w panelu.</span>
        </span>
    </label>
    <div class="space-y-5" x-show="on || isGrid" x-cloak>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Każdy kafelek to link z ikoną Bootstrap Icons (<code>bi-*</code>). Ustaw szerokość i układ indywidualnie dla każdego kafelka.
        Dla typu <strong>Siatka kafelków</strong> kafelki stanowią główną treść strony; dla innych typów wyświetlają się jako dodatkowa sekcja pod treścią. Przyciskiem „Dodaj sekcję” wstawisz nagłówek — kolejne kafelki należą do niego aż do następnego nagłówka.
    </p>

    <fieldset class="space-y-2" data-tiles-position>
        <legend class="text-sm font-bold">Kolejność treści i kafelków <span class="font-normal text-muted">(strony standardowe i „Siatka kafelków”)</span></legend>
        @php $tilesPos = old('tiles_content_position', $page->tiles_content_position ?: 'above'); @endphp
        <div class="flex flex-wrap gap-3">
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                <input type="radio" name="tiles_content_position" value="above" {{ $tilesPos !== 'below' ? 'checked' : '' }} class="text-brand focus:ring-brand"> Treść nad kafelkami
            </label>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                <input type="radio" name="tiles_content_position" value="below" {{ $tilesPos === 'below' ? 'checked' : '' }} class="text-brand focus:ring-brand"> Treść pod kafelkami
            </label>
        </div>
        <p class="text-xs text-muted">Boczne menu ustawiasz wyżej, w sekcji „Nawigacja po podstronach”. Inne typy stron pokazują kafelki zawsze pod swoją treścią.</p>
    </fieldset>

    <div data-repeater>
        <div data-repeater-rows class="space-y-4">
            @foreach ($tilesRows as $i => $tile)
                @if (isset($tile['heading']))
                    @include('admin.pages.partials.types._tile-section-row', ['i' => $i, 'heading' => $tile['heading']])
                @else
                    @include('admin.pages.partials.types._tile-row', ['i' => $i, 'tile' => $tile])
                @endif
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-5 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek</button>
        <button type="button" data-repeater-add-section class="mt-4 ml-2 inline-flex min-h-11 items-center gap-2 rounded-lg border-2 border-dashed border-gray-400 px-5 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-heading" aria-hidden="true"></i> Dodaj sekcję (nagłówek)</button>
        <template data-repeater-template>
            @include('admin.pages.partials.types._tile-row', ['i' => '__INDEX__', 'tile' => []])
        </template>
        <template data-repeater-section-template>
            @include('admin.pages.partials.types._tile-section-row', ['i' => '__INDEX__', 'heading' => ''])
        </template>
    </div>
    </div>
</div>
