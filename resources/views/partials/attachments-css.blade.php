{{-- Wspólny CSS kart plików do pobrania (ładowany raz na żądanie). --}}
@if (! request()->attributes->get('dl_css'))
    @php request()->attributes->set('dl_css', true); @endphp
    <style>
        /* Pliki do pobrania: ramka z kartami plików (zwykły CSS, niezależny od zbudowanych klas Tailwinda). */
        .dl-frame { margin-top: 2.5rem; padding: 0; border: 0; background: none; }
        .dl-h { display: flex; align-items: center; gap: .5rem; margin: 0 0 .5rem; padding-bottom: .5rem; border-bottom: 3px solid #1d1d1a; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
        .dl-h i { color: var(--color-brand); font-size: 1rem; }
        .dl-grid { list-style: none; margin: 0; padding: 0; display: block; }
        .dl-card { display: flex; align-items: center; gap: 1rem; padding: .75rem 0; border-bottom: 1px solid #d1d5db; background: none; }
        .dl-top { display: flex; flex: 1 1 auto; min-width: 0; align-items: center; gap: .85rem; }
        .dl-type { display: flex; flex: none; align-items: center; justify-content: center; min-width: 3.25rem; height: 2rem; border-radius: .25rem; background: #1d1d1a; color: #fff; font-size: .7rem; font-weight: 800; letter-spacing: .04em; }
        .dl-type i { display: none; }
        .dl-type.is-pdf { background: #b91c1c; } .dl-type.is-doc { background: #1d4ed8; } .dl-type.is-xls { background: #166534; } .dl-type.is-ppt { background: #c2410c; } .dl-type.is-zip { background: #4b5563; } .dl-type.is-img { background: #7e22ce; } .dl-type.is-av { background: #0f766e; }
        .dl-name { margin: 0; font-size: 1rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; overflow-wrap: anywhere; }
        .dl-meta { margin: .15rem 0 0; font-size: .85rem; color: #374151; }
        .dl-btn { display: inline-flex; flex: none; align-items: center; gap: .4rem; min-height: 2.5rem; padding: .25rem .5rem; border: 0; background: none; color: var(--color-brand); font-size: .95rem; font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }
        .dl-btn:hover { color: #1d1d1a; }
        .dl-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    </style>
@endif
