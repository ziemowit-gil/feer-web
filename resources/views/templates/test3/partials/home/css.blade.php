{{-- Style sekcji strony głównej szablonu „test3” (prefiks z3-), jednorazowo. Inline, bo wdrożenie nie buduje CSS Tailwinda. --}}
@once
<style>
    .z3-sec { padding: 3.5rem 0; color: #1d1d1a; }
    .z3-sec h2 { margin: 0 0 1.5rem; font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 500; line-height: 1.2; text-align: center; }
    .z3-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: 0 1.75rem; border: 0; background: var(--color-brand-dark); color: #fff; font-size: .8rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; text-decoration: none; cursor: pointer; }
    .z3-btn:hover { background: #1d1d1a; color: #fff; }
    .z3-center { margin: 2.25rem 0 0; text-align: center; }

    {{-- Slider i kafle skrótów. --}}
    .z3-hero { background: #f2f2f2; padding: 1.5rem 0 1.75rem; }
    .z3-hero-in { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 900px) { .z3-hero-in.has-tiles { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    .z3-slider { position: relative; min-height: 20rem; overflow: hidden; background: #1d1d1a; color: #fff; }
    .z3-slide { position: absolute; inset: 0; opacity: 0; visibility: hidden; transition: opacity .5s; }
    .z3-slide.is-on { opacity: 1; visibility: visible; z-index: 1; }
    .z3-slide > img { width: 100%; height: 100%; object-fit: cover; }
    .z3-slide-cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 4rem 3.75rem 1.75rem 1.5rem; background: linear-gradient(to top, rgba(0, 0, 0, .88), rgba(0, 0, 0, 0)); }
    .z3-slide-cap h3 { margin: 0; font-size: clamp(1.2rem, 2.4vw, 1.8rem); font-weight: 700; line-height: 1.25; }
    .z3-slide-cap a { color: #fff; text-decoration: none; }
    .z3-slide-cap a:hover { text-decoration: underline; text-underline-offset: 4px; }
    .z3-slide-mission { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; height: 100%; padding: 2rem 4rem; text-align: center; background: var(--color-brand-dark); }
    .z3-slide-mission img { height: 4.5rem; width: auto; object-fit: contain; }
    .z3-slide-mission p { margin: 0; font-size: clamp(1.1rem, 2.2vw, 1.6rem); font-weight: 500; line-height: 1.4; }
    .z3-arrow { position: absolute; top: 50%; z-index: 3; display: flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; margin-top: -1.375rem; border: 0; background: rgba(255, 255, 255, .92); color: #1d1d1a; font-size: 1rem; cursor: pointer; }
    .z3-arrow:hover { background: #fff; }
    .z3-arrow--prev { left: 0; }
    .z3-arrow--next { right: 0; }
    .z3-dots { position: absolute; right: .75rem; bottom: .75rem; z-index: 3; display: flex; align-items: center; gap: .15rem; padding: .1rem .35rem; background: rgba(0, 0, 0, .55); }
    .z3-dot, .z3-pause { display: flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; padding: 0; border: 0; background: transparent; cursor: pointer; }
    .z3-dot::before { content: ""; width: .7rem; height: .7rem; background: rgba(255, 255, 255, .85); }
    .z3-dot[aria-current="true"]::before { background: var(--color-brand); outline: 2px solid #fff; }
    .z3-pause { color: #fff; font-size: .8rem; }
    .z3-tiles { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); align-content: stretch; margin: 0; padding: 0; list-style: none; }
    .z3-tiles li { display: flex; }
    @media (min-width: 900px) { .z3-tiles--wide { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .z3-tile { display: flex; flex: 1; flex-direction: column; align-items: center; justify-content: center; gap: .6rem; min-height: 6.5rem; padding: .75rem; background: #fff; color: #1d1d1a; font-size: .7rem; font-weight: 700; line-height: 1.3; letter-spacing: .02em; text-align: center; text-transform: uppercase; text-decoration: none; }
    .z3-tile:hover { background: var(--color-brand-light); }
    .z3-tile i, .z3-tile .mi-glyph { color: var(--color-brand-dark); }
    .z3-tile--c:hover { filter: brightness(.9); background: inherit; }
    .z3-tile--c i, .z3-tile--c .mi-glyph { color: inherit; }

    {{-- Aktualności z filtrem kategorii. --}}
    .z3-news { background: #fff; }
    .z3-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; margin: 0 0 2rem; padding: 0; list-style: none; }
    .z3-pills a { display: inline-flex; align-items: center; min-height: 2.5rem; padding: 0 1rem; background: #f2f2f2; color: #1d1d1a; font-size: .7rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; text-decoration: none; }
    .z3-pills a:hover { background: #e5e7eb; }
    .z3-pills a.is-on { background: var(--color-brand-dark); color: #fff; }
    .z3-cards { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr)); margin: 0; padding: 0; list-style: none; }
    .z3-card { position: relative; display: flex; flex-direction: column; background: #fff; box-shadow: 0 2px 12px rgba(0, 0, 0, .1); }
    .z3-card img { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
    .z3-card-body { display: flex; flex: 1; flex-direction: column; gap: .5rem; padding: 1.25rem 1.25rem 1rem; }
    .z3-card time { color: #4b5563; font-size: .8rem; }
    .z3-card h3 { margin: 0; font-size: 1.1rem; font-weight: 500; line-height: 1.35; }
    .z3-card h3 a { color: #1d1d1a; text-decoration: none; }
    .z3-card h3 a::after { content: ""; position: absolute; inset: 0; }
    .z3-card:hover h3 a { color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
    .z3-card .z3-excerpt { margin: 0; color: #374151; font-size: .85rem; line-height: 1.55; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
    .z3-card .z3-more { margin-top: auto; padding-top: .75rem; color: var(--color-brand-dark); font-size: 1.4rem; line-height: 1; }

    {{-- Pas liczb na zdjęciu. --}}
    .z3-stats { position: relative; padding: 4.5rem 0; background: #1d1d1a center / cover no-repeat; color: #fff; text-align: center; }
    .z3-stats::before { content: ""; position: absolute; inset: 0; background: rgba(0, 0, 0, .62); }
    .z3-stats-in { position: relative; max-width: 52rem; margin: 0 auto; padding: 0 1rem; }
    .z3-stats p { margin: 0; font-size: 1rem; line-height: 1.65; }
    .z3-stats-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.75rem 3.5rem; margin: 2rem 0; }
    .z3-stat { display: flex; flex-direction: column-reverse; align-items: center; }
    .z3-stat dd { margin: 0; padding: 0 .5rem .45rem; border-bottom: 1px solid rgba(255, 255, 255, .85); font-size: 2.6rem; font-weight: 500; line-height: 1.1; }
    .z3-stat dt { margin-top: .6rem; font-size: .7rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }

    {{-- Galeria. --}}
    .z3-gal { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; padding: 0; list-style: none; }
    @media (min-width: 900px) { .z3-gal { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .z3-gal li { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #1d1d1a; }
    .z3-gal img { display: block; width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; }
    .z3-gal figcaption { position: absolute; left: 0; right: 0; bottom: 0; padding: 1.75rem .75rem .6rem; background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, 0)); color: #fff; font-size: .9rem; font-weight: 700; line-height: 1.3; pointer-events: none; }

    {{-- Partnerzy. --}}
    .z3-partners { display: flex; align-items: center; gap: .5rem; }
    .z3-partners ul { display: flex; flex: 1; gap: 2rem; margin: 0; padding: 0; list-style: none; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; align-items: center; }
    .z3-partners ul::-webkit-scrollbar { display: none; }
    .z3-partners li { flex: 0 0 11rem; display: flex; align-items: center; justify-content: center; scroll-snap-align: start; }
    .z3-partners img { display: block; max-height: 4rem; max-width: 100%; width: auto; object-fit: contain; }
    .z3-partners-btn { flex: none; width: 2.75rem; height: 2.75rem; border: 0; background: transparent; color: #1d1d1a; font-size: 1.2rem; cursor: pointer; }
    .z3-partners-btn:hover { background: #f2f2f2; }
</style>
@endonce
