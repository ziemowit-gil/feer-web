{{--
    Style szablonu „bu2” (prefiks bu2-), w zwykłym CSS, bo wdrożenie nie buduje Tailwinda. @once — dołączają je nagłówek,
    stopka i widoki szablonu. Paleta ze screenów: niebieski #00488e, jaśniejszy niebieski ramki #004fbf, pomarańczowa kreska
    #ffbc3c (tylko ozdoba, nigdy jedyny znak stanu), szary napis #5a6466, tło okruszków #f9f9f9, linia pod tytułem #b2c8dd.
    Kontrast: #00488e na bieli 9,6:1, na #f9f9f9 9,2:1, na #f2f2f2 8,5:1; biały na #00488e 9,6:1; #5a6466 na bieli 6,1:1;
    #1d1d1a na #f7f7f7 15:1. Fokus: obrys 3 px #00488e (na niebieskim tle biały), cel kliknięcia min. 44 px w menu.
    Wielkości w rem, więc działają z powiększeniem tekstu z panelu dostępności.
--}}
@once
<style>
    .bu2 { --bu2-blue: #00488e; --bu2-blue-2: #004fbf; --bu2-orange: #ffbc3c; --bu2-grey: #5a6466; --bu2-ink: #1d1d1a;
        --bu2-line: #e5e5e5; --bu2-soft: #f9f9f9; --bu2-soft-2: #f2f2f2; --bu2-panel: #f7f7f7; --bu2-under: #b2c8dd; }
    .bu2, .bu2 input, .bu2 button, .bu2 select, .bu2 textarea { font-family: Montserrat, Arial, sans-serif; }
    .bu2-wrap { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    #a11y-panel { --a11y-bar-w: 1200px; }
    .bu2 a:focus-visible, .bu2 button:focus-visible, .bu2 summary:focus-visible, .bu2 input:focus-visible { outline: 3px solid var(--bu2-blue); outline-offset: 2px; }
    .bu2 .bu2-on-blue:focus-visible, .bu2-on-blue a:focus-visible, .bu2-on-blue button:focus-visible { outline-color: #fff; }
    .bu2 [x-cloak] { display: none !important; }
    .bu2-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .bu2-chev { display: inline-block; width: .5rem; height: .5rem; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: translateY(-.15rem) rotate(45deg); transition: transform .15s; flex: none; }
    [aria-expanded="true"] > .bu2-chev, .bu2-chev.is-up { transform: translateY(.1rem) rotate(-135deg); }

    /* ── Nagłówek ─────────────────────────────────────────────── */
    .bu2-header { position: relative; z-index: 40; background: #fff; }
    .bu2-top { display: flex; align-items: center; gap: 1rem 2rem; min-height: 7.5rem; padding-top: .75rem; padding-bottom: .75rem; }
    .bu2-brand { display: flex; align-items: center; gap: 1rem 4.5rem; min-width: 0; color: var(--bu2-grey); text-decoration: none; }
    .bu2-brand img { display: block; height: 5.75rem; width: auto; max-width: 9rem; object-fit: contain; }
    .bu2-brand-name { font-size: 1.85rem; font-weight: 500; line-height: 1.2; color: var(--bu2-grey); }
    .bu2-brand:hover .bu2-brand-name { color: var(--bu2-blue); }
    .bu2-tools { display: flex; align-items: center; gap: .25rem 1.25rem; margin-left: auto; }
    .bu2-tool { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.75rem; padding: 0 .4rem; border: 0; background: transparent; color: var(--bu2-blue); font-size: 1rem; font-weight: 500; text-decoration: none; cursor: pointer; }
    .bu2-tool:hover { text-decoration: underline; text-underline-offset: 4px; }
    .bu2-tool-a11y { color: var(--bu2-ink); font-size: 1.35rem; font-weight: 500; }
    .bu2-tool-a11y sup { font-size: .6em; font-weight: 700; }
    .bu2-tool-a11y .bu2-chev { color: var(--bu2-blue); margin-left: .15rem; }
    .bu2-burger { display: none; }
    .bu2-search { border-top: 1px solid var(--bu2-line); background: #fff; }
    .bu2-search form { display: flex; gap: .5rem; padding-top: .75rem; padding-bottom: .75rem; }
    .bu2-search input { flex: 1; min-width: 0; min-height: 2.75rem; padding: .5rem .85rem; border: 1px solid #767676; border-radius: 3px; font-size: 1rem; color: var(--bu2-ink); }
    .bu2-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 2.75rem; padding: .5rem 1.25rem; border: 1px solid var(--bu2-blue); border-radius: 3px; background: var(--bu2-blue); color: #fff; font-size: .95rem; font-weight: 600; text-decoration: none; cursor: pointer; }
    .bu2-btn:hover { background: #003466; border-color: #003466; }
    .bu2-btn-line { background: #fff; color: var(--bu2-blue); }
    .bu2-btn-line:hover { background: var(--bu2-blue); color: #fff; }

    .bu2-nav { position: relative; }
    .bu2-nav-in { position: relative; border-top: 1px solid var(--bu2-line); }
    .bu2-nav-list { display: flex; justify-content: space-between; gap: .25rem; margin: 0; padding: .55rem 0; list-style: none; }
    .bu2-nav-item { position: static; }
    .bu2-nav-link { position: relative; display: inline-flex; align-items: center; gap: .55rem; min-height: 3.25rem; padding: 0 1.9rem; border: 1px solid transparent; border-radius: 3px; background: transparent;
        color: var(--bu2-blue); font-size: 1.05rem; font-weight: 600; letter-spacing: .01em; text-transform: uppercase; text-decoration: none; cursor: pointer; }
    .bu2-nav-link::after { content: ""; position: absolute; left: 1.9rem; right: 1.9rem; bottom: .3rem; height: 4px; background: var(--bu2-orange); opacity: 0; }
    .bu2-nav-link:hover, .bu2-nav-link[aria-expanded="true"] { border-color: var(--bu2-blue-2); }
    .bu2-nav-link:hover::after, .bu2-nav-link[aria-expanded="true"]::after, .bu2-nav-link.is-here::after { opacity: 1; }
    .bu2-nav-link .bu2-chev { width: .4rem; height: .4rem; }
    .bu2-mega { position: absolute; left: 0; right: 0; top: 100%; z-index: 45; padding: 1.75rem 2.25rem 2rem; background: #fff; border-left: 1px solid var(--bu2-blue); border-right: 1px solid var(--bu2-blue); border-bottom: 1px solid var(--bu2-blue); box-shadow: 0 10px 18px rgba(0, 0, 0, .08); }
    .bu2-mega ul { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .25rem 2rem; margin: 0; padding: 0; list-style: none; }
    .bu2-mega a { display: flex; align-items: center; min-height: 2.75rem; color: var(--bu2-blue); font-size: 1.05rem; font-weight: 600; text-transform: uppercase; text-decoration: none; }
    .bu2-mega a:hover, .bu2-mega a[aria-current="page"] { text-decoration: underline; text-decoration-color: var(--bu2-orange); text-decoration-thickness: 3px; text-underline-offset: 6px; }
    .bu2-mega-all { margin: 1.25rem 0 0; padding-top: 1rem; border-top: 1px solid var(--bu2-line); }
    .bu2-mega .bu2-mega-all a { display: inline-flex; min-height: 2.75rem; text-transform: none; font-weight: 600; text-decoration: underline; text-underline-offset: 4px; }

    .bu2-mnav { display: none; }

    /* ── Okruszki ─────────────────────────────────────────────── */
    .bu2-crumbs { background: var(--bu2-soft); }
    .bu2-crumbs ol { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .55rem; margin: 0; padding: 1.35rem 0; list-style: none; font-size: .95rem; }
    .bu2-crumbs li { display: inline-flex; align-items: center; gap: .55rem; }
    .bu2-crumbs li + li::before { content: "/"; color: var(--bu2-ink); }
    .bu2-crumbs a, .bu2-crumbs [aria-current] { display: inline-flex; align-items: center; min-height: 1.75rem; color: var(--bu2-blue); text-decoration: none; }
    .bu2-crumbs a:hover { text-decoration: underline; text-underline-offset: 3px; }

    /* ── Podstrona: menu boczne + treść ───────────────────────── */
    .bu2-page { display: grid; grid-template-columns: 17.5rem minmax(0, 1fr); gap: 1.75rem; padding-top: 3rem; padding-bottom: 4rem; }
    .bu2-page--wide { grid-template-columns: minmax(0, 1fr); }
    .bu2-side { align-self: start; border: 1px solid var(--bu2-line); background: #fff; }
    .bu2-side ul { margin: 0; padding: 0; list-style: none; }
    .bu2-side-row { display: flex; align-items: stretch; }
    .bu2-side-row > a, .bu2-side-row > span { flex: 1; display: flex; align-items: center; min-height: 2.9rem; padding: .55rem 1rem .55rem 1.2rem; color: var(--bu2-blue); font-size: .95rem; line-height: 1.35; text-decoration: none; }
    .bu2-side-row > a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .bu2-side-row > button { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 2.9rem; border: 0; background: transparent; color: var(--bu2-blue); cursor: pointer; }
    .bu2-side > ul > li { border-bottom: 1px solid var(--bu2-line); }
    .bu2-side > ul > li:last-child { border-bottom: 0; }
    .bu2-side > ul > li.is-open > .bu2-side-row { background: var(--bu2-blue); }
    .bu2-side > ul > li.is-open > .bu2-side-row > a, .bu2-side > ul > li.is-open > .bu2-side-row > span, .bu2-side > ul > li.is-open > .bu2-side-row > button { color: #fff; font-weight: 700; }
    .bu2-side > ul > li.is-open > .bu2-side-row a:focus-visible, .bu2-side > ul > li.is-open > .bu2-side-row button:focus-visible { outline-color: #fff; outline-offset: -4px; }
    .bu2-side-sub { padding: .35rem 0 .6rem; }
    .bu2-side-sub .bu2-side-row > a, .bu2-side-sub .bu2-side-row > span { min-height: 2.5rem; padding: .4rem .75rem .4rem 0; color: var(--bu2-ink); font-size: .9rem; border-left: 3px solid transparent; }
    .bu2-side-sub .bu2-side-row > button { width: 2.5rem; color: var(--bu2-blue); }
    .bu2-side-sub .bu2-side-row > a[aria-current="page"] { background: var(--bu2-soft-2); border-left-color: var(--bu2-blue); color: var(--bu2-blue); font-weight: 700; }
    .bu2-side-sub li.is-open > .bu2-side-row > a:not([aria-current]) { color: var(--bu2-blue); }
    .bu2-side-sub .bu2-side-sub { padding: .1rem 0 .3rem; }

    .bu2-main { min-width: 0; }
    .bu2-title { margin: 0 0 1.75rem; padding-bottom: .85rem; border-bottom: 2px solid var(--bu2-under); color: var(--bu2-blue); font-size: 2rem; font-weight: 500; line-height: 1.3; }
    .bu2-body { color: var(--bu2-ink); font-size: 1rem; line-height: 1.7; }
    .bu2-body.prose { max-width: none; }
    .bu2-body > :first-child { margin-top: 0; }
    .bu2-body p { margin: 0 0 1rem; }
    .bu2-body h2 { margin: 2rem 0 1rem; color: var(--bu2-blue); font-size: 1.55rem; font-weight: 500; line-height: 1.3; }
    .bu2-body h3 { margin: 1.75rem 0 .85rem; color: var(--bu2-ink); font-size: 1.3rem; font-weight: 600; line-height: 1.35; }
    .bu2-body h4, .bu2-body h5, .bu2-body h6 { margin: 1.5rem 0 .75rem; color: var(--bu2-ink); font-size: 1.1rem; font-weight: 600; }
    .bu2-body a { color: var(--bu2-blue); text-decoration: underline; text-underline-offset: 3px; }
    .bu2-body a:hover { text-decoration-thickness: 2px; }
    .bu2-body hr { margin: 1.5rem 0; border: 0; border-top: 1px solid #d6d6d6; }
    .bu2-body img { max-width: 100%; height: auto; }
    .bu2-body ul, .bu2-body ol { margin: 0 0 1rem; padding-left: 1.4rem; }

    /* Zakładki (blok „zwijana sekcja” i każde <details> w treści) */
    .bu2-body details { margin: 0 0 .55rem; border-radius: 4px; background: var(--bu2-panel); }
    .bu2-body details > summary { position: relative; display: block; padding: .9rem 3.25rem .9rem 1.15rem; border: 1px solid transparent; border-radius: 4px; color: var(--bu2-ink); font-size: 1.15rem; font-weight: 600; line-height: 1.35; list-style: none; cursor: pointer; }
    .bu2-body details > summary::-webkit-details-marker { display: none; }
    .bu2-body details > summary::after { content: ""; position: absolute; right: 1.25rem; top: 50%; width: .55rem; height: .55rem; border-right: 2px solid var(--bu2-blue); border-bottom: 2px solid var(--bu2-blue); transform: translateY(-70%) rotate(45deg); transition: transform .15s; }
    .bu2-body details[open] > summary::after { transform: translateY(-25%) rotate(-135deg); }
    .bu2-body details > summary:hover, .bu2-body details > summary:focus-visible { border-color: var(--bu2-blue-2); }
    .bu2-body details[open] { padding-bottom: .75rem; }
    .bu2-body details > :not(summary) { margin-left: .75rem; margin-right: .75rem; }
    .bu2-body details > .content-details-body, .bu2-body details > div:not(.content-details-body):only-of-type { padding: 1.25rem 1.25rem .5rem; background: #fff; }

    /* Pliki (blok „plik”) i lista „Pliki do pobrania” */
    .bu2-body .content-file { margin: 0 0 .5rem; }
    .bu2-body .content-file > a { display: flex; align-items: center; gap: .75rem; min-height: 3rem; padding: .7rem 1rem; background: var(--bu2-panel); color: var(--bu2-blue); text-decoration: none; }
    .bu2-body .content-file > a::before { content: ""; flex: none; width: 1.1rem; height: 1.1rem; background: no-repeat center / contain url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2300488e' d='M11 3h2v9.17l3.59-3.58L18 10l-6 6-6-6 1.41-1.41L11 12.17zM5 18h14v2H5z'/%3E%3C/svg%3E"); }
    .bu2-body .content-file > a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .bu2-body .content-file-meta { margin-left: auto; padding-left: 1rem; color: var(--bu2-ink); font-size: .85rem; font-weight: 700; white-space: nowrap; }
    .bu2-body .content-file-desc { display: block; padding: .35rem 1rem 0 2.85rem; color: var(--bu2-grey); font-size: .9rem; }

    /* Tabele w treści (np. numery telefonów na stronie Kontakt) */
    .bu2-body table { width: 100%; margin: 0 0 1.5rem; border-collapse: collapse; font-size: .95rem; }
    .bu2-body th, .bu2-body td { padding: .45rem .6rem; border: 1px solid var(--bu2-ink); background: var(--bu2-soft); vertical-align: top; text-align: left; }
    .bu2-body th { font-weight: 700; }
    .bu2-body td a, .bu2-body th a { font-weight: 700; text-decoration: none; }
    .bu2-body td a:hover { text-decoration: underline; }
    .bu2-body .content-table-wrap, .bu2-table-scroll { overflow-x: auto; }

    /* Kafle podstron */
    .bu2-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.4rem 2rem; margin: 0; padding: 0; list-style: none; }
    .bu2-tiles a { display: flex; align-items: center; gap: 1.1rem; color: var(--bu2-blue); font-size: 1rem; line-height: 1.45; text-decoration: none; }
    .bu2-tiles a:hover .bu2-tile-label { text-decoration: underline; text-underline-offset: 3px; }
    .bu2-tile-icon { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 4.25rem; height: 4.25rem; background: var(--bu2-blue); color: #fff; font-size: 2.1rem; }
    .bu2-tile-icon .material-symbols-outlined { font-size: 2.4rem; }
    .bu2-body + .bu2-tiles, .bu2-tiles-wrap { margin-top: 2rem; }

    /* Lista projektów (moduł Działania) */
    .bu2-intro { margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid #d6d6d6; }
    .bu2-group-title { margin: 1.5rem 0 1rem; color: var(--bu2-blue); font-size: 1.55rem; font-weight: 500; }
    .bu2-group + .bu2-group { margin-top: 1rem; padding-top: .5rem; border-top: 1px solid #d6d6d6; }
    .bu2-boxes { margin: 0; padding: 0; list-style: none; }
    .bu2-boxes li { margin-bottom: .55rem; }
    .bu2-boxes a { display: block; padding: .85rem 1.1rem; border: 1px solid transparent; border-radius: 4px; background: var(--bu2-panel); box-shadow: 0 2px 2px rgba(0, 0, 0, .06); color: var(--bu2-ink); font-size: 1.15rem; font-weight: 500; line-height: 1.35; text-decoration: none; }
    .bu2-boxes a:hover { border-color: var(--bu2-blue-2); color: var(--bu2-blue); }

    /* Dane teleadresowe (Kontakt) */
    .bu2-contact h2 { margin: 0 0 .5rem; color: var(--bu2-ink); font-size: 1.3rem; font-weight: 500; }
    .bu2-contact address { margin-bottom: 1.25rem; font-style: normal; line-height: 1.6; }

    /* Narzędzia pod treścią */
    .bu2-tools-row { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #d6d6d6; }
    .bu2-print { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.25rem; padding: .3rem .85rem; border: 0; border-radius: 3px; background: var(--bu2-soft-2); color: var(--bu2-ink); font-size: .85rem; font-weight: 500; cursor: pointer; }
    .bu2-print:hover { background: #e4e4e4; }
    .bu2-print svg { width: .95rem; height: .95rem; fill: var(--bu2-blue); }
    .bu2-date { color: var(--bu2-grey); font-size: .9rem; }

    /* Lista plików „Pliki do pobrania” (załączniki strony) w wyglądzie BU2 */
    .bu2-main .dl-frame { margin-top: 2rem; padding: 0; border: 0; background: transparent; box-shadow: none; }
    .bu2-main .dl-h { color: var(--bu2-ink); font-size: 1.3rem; font-weight: 600; }
    .bu2-main .dl-accent { display: none; }
    .bu2-main .dl-card { border: 0; border-radius: 0; background: var(--bu2-panel); box-shadow: none; }

    /* ── Strona główna ────────────────────────────────────────── */
    .bu2-slider { position: relative; max-width: 1440px; margin: 0 auto; }
    .bu2-slide { display: grid; grid-template-columns: 2fr 3fr; min-height: 22.5rem; }
    .bu2-slide-panel { display: flex; flex-direction: column; justify-content: center; gap: 1rem; padding: 2.5rem 3.25rem 4.5rem; background: var(--bu2-blue); color: #fff; }
    .bu2-slide-panel h2 { margin: 0; font-size: 1.6rem; font-weight: 600; line-height: 1.3; }
    .bu2-slide-panel p { margin: 0; font-size: 1rem; line-height: 1.6; }
    .bu2-slide-panel .bu2-btn-ghost { align-self: flex-start; margin-top: 1.25rem; display: inline-flex; align-items: center; min-height: 2.75rem; padding: .45rem 1.2rem; border: 1px solid #fff; color: #fff; font-size: .95rem; font-weight: 600; text-decoration: none; }
    .bu2-slide-panel .bu2-btn-ghost:hover { background: #fff; color: var(--bu2-blue); }
    .bu2-slide-img { position: relative; background: #d9dee3 center / cover no-repeat; }
    .bu2-slide-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .bu2-slider-ctrl { position: absolute; right: 0; bottom: 1.75rem; z-index: 2; display: flex; gap: .25rem; }
    .bu2-slider-ctrl button { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border: 0; background: var(--bu2-blue); color: #fff; cursor: pointer; }
    .bu2-slider-ctrl button:hover { background: #003466; }
    .bu2-slider-ctrl button:focus-visible { outline: 3px solid var(--bu2-orange); outline-offset: 0; box-shadow: 0 0 0 5px var(--bu2-blue); }
    .bu2-slider-ctrl svg { width: 1rem; height: 1rem; fill: currentColor; }

    .bu2-sec { padding: 3.5rem 0 1rem; }
    .bu2-sec-title { margin: 0 0 1.75rem; color: var(--bu2-blue); font-size: 1.7rem; font-weight: 500; text-align: center; }
    .bu2-news { position: relative; }
    .bu2-news-track { display: grid; grid-auto-flow: column; grid-auto-columns: calc((100% - 2 * 1rem) / 3); gap: 1rem; margin: 0; padding: 0 0 .25rem; list-style: none; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; scrollbar-width: none; }
    .bu2-news-track::-webkit-scrollbar { display: none; }
    .bu2-news-track > li { scroll-snap-align: start; }
    .bu2-news-card { display: block; color: var(--bu2-blue); text-decoration: none; }
    .bu2-news-img { position: relative; display: block; aspect-ratio: 3 / 2; background: #e6e9ec; overflow: hidden; }
    .bu2-news-img img { width: 100%; height: 100%; object-fit: cover; }
    .bu2-news-date { position: absolute; top: 0; right: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 4.1rem; padding: .55rem .4rem; background: var(--bu2-blue); color: #fff; line-height: 1; }
    .bu2-news-date b { font-size: 1.6rem; font-weight: 500; }
    .bu2-news-date span { margin-top: .4rem; font-size: .75rem; font-weight: 600; text-transform: uppercase; }
    .bu2-news-card h3 { margin: .9rem 0 0; font-size: 1.15rem; font-weight: 400; line-height: 1.5; }
    .bu2-news-card:hover h3 { text-decoration: underline; text-underline-offset: 3px; }
    .bu2-news-arrow { position: absolute; top: 25%; display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border: 0; background: transparent; color: var(--bu2-ink); cursor: pointer; }
    .bu2-news-arrow[disabled] { visibility: hidden; }
    .bu2-news-arrow svg { width: 1rem; height: 1rem; fill: currentColor; }
    .bu2-news-arrow--prev { left: -3.25rem; }
    .bu2-news-arrow--next { right: -3.25rem; }
    .bu2-more { display: flex; justify-content: flex-end; margin-top: 1.75rem; }
    .bu2-more a { display: inline-flex; align-items: center; min-height: 2.5rem; padding: .35rem 2.4rem; border: 1px solid var(--bu2-blue); color: var(--bu2-blue); font-size: .9rem; text-decoration: none; }
    .bu2-more a:hover { background: var(--bu2-blue); color: #fff; }

    .bu2-home-contact { display: grid; grid-template-columns: 1fr 1.2fr 1fr; align-items: center; gap: 2rem; padding: 1rem 0 3rem; text-align: center; }
    .bu2-home-contact img { max-width: 9rem; max-height: 5rem; margin: 0 auto; object-fit: contain; }
    .bu2-home-contact address { font-style: normal; font-size: .95rem; line-height: 1.55; color: var(--bu2-ink); }
    .bu2-home-contact address strong { display: block; margin-bottom: .45rem; font-size: 1.2rem; font-weight: 600; }
    .bu2-home-contact ul { margin: .25rem 0 0; padding: 0; list-style: none; }
    .bu2-home-contact li { display: flex; align-items: center; justify-content: center; gap: .45rem; }
    .bu2-home-contact li svg { width: .9rem; height: .9rem; fill: var(--bu2-ink); }
    .bu2-home-contact a { color: inherit; text-decoration: none; }
    .bu2-home-contact a:hover { text-decoration: underline; }
    .bu2-home-contact .bu2-mail a { color: var(--bu2-blue); }
    .bu2-map { display: inline-flex; flex-direction: column; align-items: center; gap: .6rem; color: var(--bu2-blue) !important; font-size: .8rem; font-weight: 700; text-transform: uppercase; }
    .bu2-map svg { width: 2.6rem; height: 3.4rem; fill: var(--bu2-blue); }

    /* ── Stopka ───────────────────────────────────────────────── */
    .bu2-footer { margin-top: auto; padding-bottom: 2rem; background: #fff; }
    .bu2-foot-links { margin: 0; padding: .85rem 0; border-top: 1px solid var(--bu2-line); border-bottom: 1px solid var(--bu2-line); }
    .bu2-foot-links ul { display: flex; flex-wrap: wrap; justify-content: center; gap: .25rem 2rem; margin: 0; padding: 0; list-style: none; }
    .bu2-foot-links a, .bu2-foot-links button { display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0; border: 0; background: none; color: var(--bu2-blue); font-size: .85rem; text-decoration: none; cursor: pointer; }
    .bu2-foot-links a:hover, .bu2-foot-links button:hover { text-decoration: underline; text-underline-offset: 3px; }
    .bu2-funding { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 1.25rem 3rem; margin: 0; padding: 1.75rem 0 1rem; list-style: none; }
    .bu2-funding img { display: block; max-height: 3.5rem; width: auto; max-width: 13rem; object-fit: contain; }
    .bu2-copy { margin: .75rem 0 0; color: var(--bu2-grey); font-size: .85rem; text-align: center; }
    .bu2-copy a { color: var(--bu2-blue); }

    /* ── Wąskie ekrany ────────────────────────────────────────── */
    @media (max-width: 1199px) {
        .bu2-nav-link { padding: 0 1rem; font-size: .95rem; }
        .bu2-nav-link::after { left: 1rem; right: 1rem; }
        .bu2-brand { gap: 1rem 2rem; }
        .bu2-news-arrow--prev { left: -.5rem; }
        .bu2-news-arrow--next { right: -.5rem; }
    }
    @media (max-width: 1023px) {
        .bu2-top { min-height: 5rem; }
        .bu2-brand img { height: 3.75rem; }
        .bu2-brand-name { font-size: 1.3rem; }
        .bu2-tool-label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
        .bu2-burger { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.75rem; padding: 0 .85rem; border: 1px solid var(--bu2-blue); border-radius: 3px; background: #fff; color: var(--bu2-blue); font-size: .95rem; font-weight: 600; cursor: pointer; }
        .bu2-nav { display: none; }
        .bu2-mnav { display: block; border-top: 1px solid var(--bu2-line); padding-bottom: 1rem; }
        .bu2-mnav .bu2-side { border: 0; }
        .bu2-page { grid-template-columns: minmax(0, 1fr); padding-top: 2rem; }
        .bu2-page .bu2-side { order: 2; }
        .bu2-slide { grid-template-columns: 1fr; }
        .bu2-slide-img { min-height: 14rem; order: -1; }
        .bu2-slide-panel { padding: 1.75rem 1.25rem 4.25rem; }
        .bu2-slider-ctrl { bottom: 1rem; right: 1rem; }
        .bu2-news-track { grid-auto-columns: calc((100% - 1rem) / 2); }
        .bu2-home-contact { grid-template-columns: 1fr; }
    }
    @media (max-width: 639px) {
        .bu2-brand { gap: .75rem; }
        .bu2-brand img { height: 3rem; }
        .bu2-brand-name { font-size: 1.05rem; }
        .bu2-tools { gap: 0; }
        .bu2-title { font-size: 1.6rem; }
        .bu2-tiles { grid-template-columns: 1fr; }
        .bu2-news-track { grid-auto-columns: 85%; }
        .bu2-news-arrow { display: none; }
        .bu2-body .content-file > a { flex-wrap: wrap; }
        .bu2-body .content-file-meta { margin-left: 1.85rem; padding-left: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .bu2-news-track { scroll-behavior: auto; }
        .bu2-chev, .bu2-body details > summary::after { transition: none; }
    }
    @media print {
        .bu2-side, .bu2-crumbs, .bu2-tools-row, .bu2-mnav { display: none !important; }
        .bu2-page { display: block; padding: 0; }
        .bu2-body details > summary::after { display: none; }
    }
</style>
@endonce
