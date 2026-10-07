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
