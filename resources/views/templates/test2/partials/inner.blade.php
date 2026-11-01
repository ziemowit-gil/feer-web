{{--
    Podstrony wewnętrzne szablonu „test2”: style nakładane na istniejące widoki pod klasą body.template-test2
    (okruszki z kreską, tytuł strony jako niebieski pasek, akordeony jako kolorowe belki, tabele z niebieskim
    nagłówkiem). Inline, bo wdrożenie nie buduje CSS. Kontrast tekstu na każdej belce ≥ 4,5:1.
--}}
<style>
    .template-test2 { --z2-blue: #0b4f9e; --z2-blue-dark: #083a75; }

    /* Okruszki: bez szarego paska, z kreską przed ścieżką. */
    .template-test2 div:has(> nav[aria-label="Ścieżka nawigacyjna"]) { background: transparent; border: 0; }
    .template-test2 nav[aria-label="Ścieżka nawigacyjna"] ol { font-size: .8rem; font-weight: 700; color: #1a1a1a; padding-top: 1.25rem; padding-bottom: .25rem; }
    .template-test2 nav[aria-label="Ścieżka nawigacyjna"] ol::before { content: "—"; margin-right: .35rem; }
    .template-test2 nav[aria-label="Ścieżka nawigacyjna"] a { color: #1a1a1a; }
    .template-test2 nav[aria-label="Ścieżka nawigacyjna"] a:hover { color: var(--z2-blue); text-decoration: underline; }
    .template-test2 nav[aria-label="Ścieżka nawigacyjna"] .fa-house { display: none; }

    /* Tytuł strony: niebieski pasek na całą szerokość treści (poza ukrytym h1 strony głównej). */
    .template-test2 main h1:not(.sr-only) { background: var(--z2-blue); color: #fff; text-align: center; font-weight: 800; padding: .8rem 1.25rem; margin-bottom: 1.5rem; }
    .template-test2 main h1:not(.sr-only) a { color: inherit; }

    /* Akordeony (blok, FAQ, podstrony działu): kolorowe belki z białym tekstem. */
    .template-test2 main details { border: 0; border-radius: 0; background: transparent; margin-bottom: .9rem; }
    .template-test2 main details > summary { background: var(--z2-blue); color: #fff !important; text-align: center; justify-content: center; position: relative; padding: .85rem 3rem !important; min-height: 3.25rem; border-radius: 0 !important; font-weight: 800; }
    .template-test2 main details > summary:hover { filter: brightness(.9); background: var(--z2-blue); }
    .template-test2 main details > summary > i, .template-test2 main details > summary > svg { position: absolute; right: 1.1rem; top: 50%; margin-top: -.5rem; color: #fff; }
    .template-test2 main details:nth-of-type(5n+1) > summary { background: #9a5500; }
    .template-test2 main details:nth-of-type(5n+2) > summary { background: #1a479a; }
    .template-test2 main details:nth-of-type(5n+3) > summary { background: #1f6b21; }
    .template-test2 main details:nth-of-type(5n+4) > summary { background: #4f1aa8; }
    .template-test2 main details:nth-of-type(5n+5) > summary { background: #7a0c10; }
    .template-test2 main div.divide-y:has(> details) { border: 0; }
    .template-test2 main details > summary:focus-visible { outline: 3px solid #1a1a1a; outline-offset: 2px; }

    /* Tabele: niebieski nagłówek, naprzemienne szare wiersze. */
    .template-test2 main table { border-collapse: collapse; width: 100%; }
    .template-test2 main table thead th, .template-test2 main table th { background: var(--z2-blue); color: #fff; font-weight: 800; padding: 1rem 1.1rem; text-align: left; }
    .template-test2 main table td { padding: .75rem 1.1rem; border: 0; }
    .template-test2 main table tbody tr:nth-child(odd) { background: #efefef; }
    .template-test2 main table tbody tr:nth-child(even) { background: #fff; }
    .template-test2 main table tbody tr:hover { background: #e2e8f0; }

    /* Linki w treści i przyciski typowych akcji. */
    .template-test2 .prose a { color: var(--z2-blue); }
    .template-test2 .prose a:hover { color: var(--z2-blue-dark); }
</style>
