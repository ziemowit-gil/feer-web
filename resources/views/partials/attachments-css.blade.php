{{-- Wspólny CSS listy plików do pobrania (ładowany raz na żądanie). --}}
@if (! request()->attributes->get('dl_css'))
    @php request()->attributes->set('dl_css', true); @endphp
    <style>
        /* Pliki do pobrania — płasko: szare karty bez linii i plakietek, rodzaj pliku zwykłym tekstem
           (zwykły CSS, niezależny od zbudowanych klas Tailwinda). Kontrast tekstu ≥ 4,5:1 na #f9fafb. */
        .dl-frame { margin-top: 2.5rem; padding: 0; background: none; }
        .dl-h { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: 1.5rem; font-weight: 700; line-height: 1.3; color: #1a1a1a; }
        .dl-h i { color: var(--color-brand-dark, #1752bf); font-size: 1.1rem; }
        .dl-accent { display: block; width: 3.5rem; height: .25rem; margin: .75rem 0 1.25rem; background: var(--color-brand, #1e6dff); }
        .dl-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
        .dl-card { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem 1rem; padding: .75rem 1rem; border-radius: .375rem; background: #f9fafb; }
        .dl-top { display: flex; flex: 1 1 14rem; min-width: 0; align-items: center; gap: .85rem; }
        .dl-icon { flex: none; width: 1.5rem; text-align: center; font-size: 1.25rem; color: var(--color-brand-dark, #1752bf); }
        .dl-name { margin: 0; font-size: 1rem; font-weight: 700; line-height: 1.3; color: #1a1a1a; overflow-wrap: anywhere; }
        .dl-meta { margin: .15rem 0 0; font-size: .875rem; color: #4b5563; }
        .dl-btn { display: inline-flex; flex: none; align-items: center; gap: .4rem; min-height: 2.75rem; padding: .25rem .25rem; background: none; color: var(--color-brand-dark, #1752bf); font-size: 1rem; font-weight: 700; text-decoration: underline; text-underline-offset: 4px; }
        .dl-btn:hover { text-decoration: none; }
        .dl-btn:focus-visible { outline: none; box-shadow: 0 0 0 2px var(--color-brand-dark, #1752bf); border-radius: .25rem; }
    </style>
@endif
