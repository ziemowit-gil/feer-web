{{--
    Style szablonu „test3” wspólne dla nagłówka, stopki i podstron (prefiks z3-). Inline, bo wdrożenie nie buduje CSS Tailwinda.
    @once, bo dołączają je i nagłówek, i stopka (stopka bywa bez nagłówka, np. na stronach z uproszczonym nagłówkiem).
    Kontrast: tekst #1d1d1a na bieli i #f2f2f2; przyciski i linki w kolorze witryny (brand-dark) z białym tekstem ≥ 4,5:1.
--}}
@once
<style>
    .z3, .z3 input, .z3 button { font-family: Roboto, Arial, sans-serif; }
    .z3-wrap { max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    .z3 a:focus-visible, .z3 button:focus-visible, .z3 input:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }

    {{-- Pasek górny: szybkie linki, ułatwienia dostępu, social, BIP. --}}
    .z3-util { background: #f2f2f2; border-bottom: 1px solid #e5e7eb; font-size: .75rem; color: #1d1d1a; }
    .z3-util-in { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 2.5rem; }
    .z3-util ul { display: flex; align-items: center; margin: 0; padding: 0; list-style: none; }
    .z3-util-links { gap: 1.25rem; text-transform: uppercase; font-weight: 500; letter-spacing: .02em; }
    .z3-util a { color: #1d1d1a; text-decoration: none; }
    .z3-util-links a { display: inline-flex; align-items: center; min-height: 2.5rem; }
    .z3-util-links a:hover { text-decoration: underline; }
    .z3-util-tools { display: flex; align-items: center; gap: .1rem; flex-wrap: wrap; justify-content: flex-end; }
    .z3-tool { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-width: 2.5rem; min-height: 2.5rem; padding: 0 .5rem; border: 0; background: transparent; color: #1d1d1a; font-size: .8rem; font-weight: 700; text-decoration: none; cursor: pointer; }
    .z3-tool:hover { color: var(--color-brand-dark); }
    .z3-tool i { font-size: 1.05rem; }
    .z3-tool[aria-pressed="true"], .z3-tool[aria-expanded="true"] { background: #1d1d1a; color: #fff; }
    .z3-util-sep { width: 1px; height: 1.25rem; margin: 0 .35rem; background: #9ca3af; }

    {{-- Nagłówek: logo, szybkie linki, wyszukiwarka. --}}
    .z3-head { background: #fff; }
    .z3-head-in { display: flex; align-items: center; gap: 1.5rem 2rem; flex-wrap: wrap; padding: 1.25rem 1rem; }
    .z3-brand { display: flex; align-items: center; gap: .9rem; min-width: 0; color: #1d1d1a; text-decoration: none; }
    .z3-brand img { height: 4rem; width: auto; max-width: 5rem; object-fit: contain; }
    .z3-brand-name { padding-left: .9rem; border-left: 1px solid #1d1d1a; font-size: clamp(1.15rem, 2.4vw, 1.5rem); font-weight: 500; line-height: 1.15; letter-spacing: .08em; text-transform: uppercase; }
    .z3-links { display: flex; flex-wrap: wrap; margin: 0 0 0 auto; padding: 0; list-style: none; }
    .z3-links a { position: relative; display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 1.1rem; color: #1d1d1a; font-size: .8rem; font-weight: 700; text-decoration: none; }
    .z3-links li + li a::before { content: ""; position: absolute; left: 0; top: 30%; height: 40%; width: 1px; background: #6b7280; }
    .z3-links a:hover, .z3-links a[aria-current="page"] { color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
    .z3-search { display: flex; align-items: center; width: 20rem; max-width: 100%; border: 1px solid #6b7280; background: #fff; }
    .z3-search:focus-within { outline: 3px solid #1d1d1a; outline-offset: 2px; }
    .z3-search input { flex: 1; min-width: 0; min-height: 2.75rem; padding: 0 .9rem; border: 0; background: transparent; color: #1d1d1a; font-size: .875rem; }
    .z3-search input:focus-visible { outline: none; }
    .z3-search input::placeholder { color: #4b5563; opacity: 1; }
    .z3-search button { display: flex; align-items: center; justify-content: center; min-width: 2.75rem; min-height: 2.75rem; border: 0; background: transparent; color: var(--color-brand-dark); font-size: 1rem; cursor: pointer; }

    {{-- Kategorie: trzy (lub więcej) kolorowe belki z rozwijanym podmenu. --}}
    .z3-cats { background: #fff; }
    .z3-cats-in { display: grid; grid-template-columns: repeat(var(--z3-n, 3), minmax(0, 1fr)); gap: 0 2.5rem; margin: 0; padding: 0 1rem; list-style: none; max-width: 1160px; margin-inline: auto; }
    .z3-cat { position: relative; padding-bottom: 1.25rem; }
    .z3-cat-row { display: flex; align-items: center; gap: .9rem; padding: .5rem .25rem; border-bottom: 3px solid var(--z3-c); }
    .z3-bars { color: #4b5563; font-size: .95rem; }
    .z3-cat-label { flex: 1; min-width: 0; color: #1d1d1a; font-size: .85rem; font-weight: 500; letter-spacing: .02em; text-transform: uppercase; text-decoration: none; }
    a.z3-cat-label:hover { text-decoration: underline; text-underline-offset: 4px; }
    .z3-cat-btn { display: flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border: 0; background: transparent; color: var(--z3-ink); font-size: 1.35rem; cursor: pointer; }
    .z3-cat-btn i { transition: transform .2s; }
    .z3-cat-btn[aria-expanded="true"] i { transform: rotate(180deg); }
    .z3-panel { position: absolute; left: 0; right: 0; top: calc(100% - 1.25rem); z-index: 60; margin: 0; padding: .5rem 0; list-style: none; background: #fff; box-shadow: 0 10px 18px rgba(0, 0, 0, .14); }
    .z3-panel a { display: flex; align-items: center; gap: .75rem; min-height: 2.5rem; padding: .35rem 1rem .35rem 1.6rem; color: #1d1d1a; font-size: .8rem; text-decoration: none; }
    .z3-panel a::before { content: "\2192"; color: var(--z3-ink); font-weight: 700; }
    .z3-panel a:hover, .z3-panel a:focus-visible, .z3-panel a[aria-current="page"] { background: #f2f2f2; }
    .z3-panel a[aria-current="page"] { font-weight: 700; }
    @media (max-width: 900px) {
        .z3-cats-in { grid-template-columns: 1fr; }
        .z3-cat { padding-bottom: .5rem; }
        .z3-panel { position: static; box-shadow: none; background: #f7f7f7; }
        .z3-links { margin-left: 0; }
        .z3-util-links { display: none; }
        .z3-util-in { justify-content: flex-end; }
    }

    {{-- Stopka. --}}
    .z3-foot { background: #f2f2f2; color: #1d1d1a; margin-top: 0; }
    .z3-foot-grid { display: grid; gap: 2rem; grid-template-columns: 1.3fr 1fr 1fr; padding-top: 3rem; padding-bottom: 2.5rem; align-items: start; }
    @media (max-width: 800px) { .z3-foot-grid { grid-template-columns: 1fr; padding-top: 2rem; } }
    .z3-foot h2 { margin: 0 0 .75rem; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .z3-foot-brand { display: flex; align-items: center; gap: .9rem; margin-bottom: 1rem; }
    .z3-foot-brand img { height: 3.5rem; width: auto; max-width: 4.5rem; object-fit: contain; }
    .z3-foot-brand span { padding-left: .9rem; border-left: 1px solid #1d1d1a; font-size: 1.1rem; font-weight: 500; line-height: 1.15; letter-spacing: .06em; text-transform: uppercase; }
    .z3-foot address { font-style: normal; font-size: .9rem; line-height: 1.6; }
    .z3-foot ul { display: grid; gap: .5rem; margin: 0; padding: 0; list-style: none; }
    .z3-foot a { color: var(--color-brand-dark); font-size: .9rem; text-decoration: underline; text-underline-offset: 3px; }
    .z3-foot a:hover { text-decoration: none; }
    .z3-foot p { margin: 0 0 .5rem; font-size: .9rem; line-height: 1.55; }
    .z3-foot-bar { background: #1d1d1a; color: #fff; font-size: .75rem; }
    .z3-foot-bar-in { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem 1rem; padding-top: .75rem; padding-bottom: .75rem; }
    .z3-foot-bar button { padding: 0; border: 0; background: none; color: #fff; font-size: .75rem; text-decoration: underline; cursor: pointer; }
    .z3-top { position: fixed; right: 1rem; bottom: 1rem; z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; width: 3.5rem; height: 3.5rem; border: 0; background: #1d1d1a; color: #fff; font-size: .6rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; cursor: pointer; }
    .z3-top[hidden] { display: none; }
    .z3-top:hover { background: var(--color-brand-dark); }
    .z3-top i { font-size: 1rem; }

    {{-- Podstrony: okruszki bez szarego paska, tytuł z akcentem, tabele. --}}
    .template-test3 div:has(> nav[aria-label="Ścieżka nawigacyjna"]) { background: transparent; border: 0; }
    .template-test3 nav[aria-label="Ścieżka nawigacyjna"] ol { padding-top: 1.25rem; padding-bottom: .25rem; font-size: .8rem; font-weight: 500; color: #1d1d1a; }
    .template-test3 nav[aria-label="Ścieżka nawigacyjna"] a { color: #1d1d1a; }
    .template-test3 nav[aria-label="Ścieżka nawigacyjna"] a:hover { color: var(--color-brand-dark); text-decoration: underline; }
    .template-test3 nav[aria-label="Ścieżka nawigacyjna"] .fa-house { display: none; }
    .template-test3 main h1:not(.sr-only) { position: relative; padding-bottom: .9rem; font-weight: 500; letter-spacing: -.01em; }
    .template-test3 main h1:not(.sr-only)::after { content: ""; position: absolute; left: 0; bottom: 0; width: 4rem; height: .3rem; background: var(--color-brand); }
    .template-test3 .prose a { color: var(--color-brand-dark); }
    .template-test3 main table th { background: #f2f2f2; color: #1d1d1a; font-weight: 700; border-bottom: 3px solid var(--color-brand); }
</style>
@endonce
