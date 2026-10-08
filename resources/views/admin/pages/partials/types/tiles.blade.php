{{-- Sekcja formularza strony: typ "tiles" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- Siatka kafelków --}}
@php $tilesRows = array_values((array) old('tiles', $page->tiles ?? [])); @endphp
<div data-tiles-fields class="space-y-5 border-t border-gray-100 pt-5">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kafelki</p>
    <p class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
        Każdy kafelek to link z ikoną Bootstrap Icons (<code>bi-*</code>). Ustaw szerokość i układ indywidualnie dla każdego kafelka.
        Dla typu <strong>Siatka kafelków</strong> kafelki stanowią główną treść strony; dla innych typów wyświetlają się jako dodatkowa sekcja pod treścią.
    </p>

    <fieldset class="space-y-2" data-tiles-position>
        <legend class="text-sm font-bold">Treść strony względem kafelków <span class="font-normal text-muted">(typ „Siatka kafelków”)</span></legend>
        @php $tilesPos = old('tiles_content_position', $page->tiles_content_position ?: 'above'); @endphp
        <div class="flex flex-wrap gap-3">
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                <input type="radio" name="tiles_content_position" value="above" {{ $tilesPos !== 'below' ? 'checked' : '' }} class="text-brand focus:ring-brand"> Treść nad kafelkami
            </label>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                <input type="radio" name="tiles_content_position" value="below" {{ $tilesPos === 'below' ? 'checked' : '' }} class="text-brand focus:ring-brand"> Treść pod kafelkami
            </label>
        </div>
        <p class="text-xs text-muted">Boczne menu włączasz w sekcji „Nawigacja po podstronach działu” (po prawej stronie treści, albo drzewo działu po lewej).</p>
    </fieldset>

    <div data-repeater>
        <div data-repeater-rows class="space-y-4">
            @foreach ($tilesRows as $i => $tile)
                @include('admin.pages.partials.types._tile-row', ['i' => $i, 'tile' => $tile])
            @endforeach
        </div>
        <button type="button" data-repeater-add class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-5 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kafelek</button>
        <template data-repeater-template>
            @include('admin.pages.partials.types._tile-row', ['i' => '__INDEX__', 'tile' => []])
        </template>
    </div>
</div>
