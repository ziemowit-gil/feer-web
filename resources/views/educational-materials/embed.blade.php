{{--
    Lista materiałów osadzona w treści ([materialy…], blok „Materiały edukacyjne”): te same karty co na /materialy,
    w siatce 1–3 kolumn, z linkiem do wszystkich materiałów. Style i okno podglądu PDF dołączane raz na stronę (@once).
--}}
<div class="mat-embed not-prose">
    <ul class="mat-grid" role="list">
        @foreach ($materials as $material)
            <li>@include('partials.material-card-feer', ['material' => $material])</li>
        @endforeach
    </ul>
    <p class="mat-embed-more"><a href="{{ $moreUrl }}">Wszystkie materiały edukacyjne <span aria-hidden="true">→</span></a></p>
</div>
@once
    @include('educational-materials.partials.styles')
    <style>
        .mat-embed { margin: 1.5rem 0; }
        .mat-embed .mat-grid { margin: 0; padding: 0; list-style: none; }
        .mat-embed .mat-grid > li { margin: 0; padding: 0; }
        .mat-embed .mat-grid > li::before { content: none; }
        .mat-embed .mat-title { font-family: var(--font-heading); }
        .mat-embed-more { margin: 1rem 0 0; }
        .mat-embed-more a { display: inline-flex; min-height: 2.75rem; align-items: center; font-weight: 700; color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 4px; }
        .mat-embed-more a:hover { text-decoration: none; }
        .mat-embed-more a:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; border-radius: .25rem; }
    </style>
    @include('partials.material-preview-dialog')
@endonce
