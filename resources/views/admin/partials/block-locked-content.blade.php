{{-- Treść edytowana w edytorze blokowym — w formularzu tylko podgląd (bez pola „content”), żeby zwykła edycja nie zastąpiła bloków.
     Parametry: $editUrl (edytor blokowy), $noun (np. „tej strony”), $html (wyrenderowana treść). --}}
<div class="rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="note">
    <p class="font-bold"><i class="fa-solid fa-lock mr-1" aria-hidden="true"></i> Treść {{ $noun }} jest edytowana w edytorze blokowym.</p>
    <p class="mt-1">Zwykła edycja jest zablokowana, żeby nie nadpisać bloków. Pozostałe pola formularza możesz zmieniać i zapisywać.</p>
    <a href="{{ $editUrl }}"
        class="mt-3 inline-flex min-h-10 items-center gap-1.5 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
        <i class="fa-solid fa-cubes" aria-hidden="true"></i> Edytuj treść w edytorze blokowym
    </a>
</div>
@if (filled($html))
    <section aria-label="Podgląd treści (tylko do odczytu)" class="mt-3">
        <h3 class="mb-1 text-sm font-bold text-ink">Podgląd treści</h3>
        <div class="prose prose-sm max-h-96 max-w-none overflow-y-auto rounded-md bg-gray-50 p-4 text-ink" tabindex="0">{!! $html !!}</div>
    </section>
@endif
