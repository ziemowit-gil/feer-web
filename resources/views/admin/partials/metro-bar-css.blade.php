{{--
    Style górnego paska Metro (admin/partials/topnav-metro): w panelu (styl „Metro”, dołączane przez theme-metro) i na
    stronie publicznej dla zalogowanej redakcji (partials/site-admin-bar, pasek w <div class="admin-metro site-admin-bar">).
    Sam CSS bez znacznika <style> — wywołujący wstawia go do swojego bloku stylów. Tokeny (--m-*) są ustawione na .mt-nav,
    więc pasek wygląda tak samo poza panelem; w panelu mają te same wartości co na <body class="admin-metro">.
--}}
@php
    $mbPalette = $siteSettings->brandPalette();
    $mbBrand = \App\Support\AdminMetro::tones($mbPalette['color'])['brand'];
    $mbSurf = \App\Support\AdminMetro::SURFACES;
    $mbAccentInk = \App\Support\Color::contrast($mbPalette['dark'], $mbSurf['side_hover']) >= 4.5 ? $mbPalette['dark'] : \App\Support\AdminMetro::INK;
@endphp
    .mt-nav {
        --m-bg: {{ $mbSurf['page'] }};
        --m-surface: #fff;
        --m-side: {{ $mbSurf['side'] }};
        --m-side-hover: {{ $mbSurf['side_hover'] }};
        --m-hover: {{ $mbSurf['row_hover'] }};
        --m-head: {{ $mbSurf['head'] }};
        --m-ink: {{ \App\Support\AdminMetro::INK }};
        --m-muted: {{ \App\Support\AdminMetro::MUTED }};
        --m-line: #d9d9d9;
        --m-line-strong: #767676;
        --m-accent: {{ $mbBrand['bg'] }};
        --m-accent-fg: {{ $mbBrand['fg'] }};
        --m-accent-hover: {{ $mbBrand['hover'] }};
        --m-accent-active: {{ $mbBrand['active'] }};
        --m-accent-ink: {{ $mbAccentInk }};
        --m-focus: {{ \App\Support\AdminMetro::INK }};
        --m-font: "Segoe UI", system-ui, -apple-system, Roboto, "Helvetica Neue", Arial, sans-serif;
        --m-font-heading: "Montserrat", "Segoe UI", system-ui, sans-serif;
        font-family: var(--m-font);
    }
    .mt-nav *, .mt-nav *::before, .mt-nav *::after { border-radius: 0; }

    /* ── Górne menu (admin/partials/topnav-metro, body.nav-top): ciemny pasek aplikacji z grupami, pod nim mega menu na całą
       szerokość z kaflami pozycji (jak ekran Start Metro UI 5: tiles-grid, odstęp, ikona u góry, nazwa u dołu, licznik w rogu).
       Na szerokim ekranie pasek boczny znika (zostaje szuflada na telefonie), a nagłówek strony nie przykleja się — przyklejone
       jest tylko menu. Kontrast: biały na #1d1d1a ≈ 17:1, akcent z parą kolorów z AdminMetro, fokus biały na pasku. ── */
    .mt-nav { display: none; }
    @media (min-width: 1024px) {
        body.nav-top .admin-sidebar { display: none !important; }
        body.nav-top .j-topbar { position: static; }
        .admin-metro .mt-nav { position: sticky; top: 0; z-index: 30; display: block; background: var(--m-ink); color: #fff; }
        /* Pasek „Edytujesz: …” nad treścią jest wtedy w górnym menu (ikona za logo); na telefonie zostaje. */
        body.nav-top .admin-site-bar { display: none; }
    }
    .admin-metro .mt-bar { display: flex; flex-wrap: wrap; align-items: stretch; min-height: 3.5rem; padding: 0 .5rem; }
    .admin-metro .mt-brand { display: flex; flex: none; align-items: center; gap: .625rem; padding: 0 1rem 0 .5rem; color: #fff; text-decoration: none; }
    .admin-metro .mt-brand:focus-visible { outline: 3px solid #fff; outline-offset: -3px; }
    .admin-metro .mt-brand-logo { width: 2rem; height: 2rem; flex: none; object-fit: contain; background: #fff; }
    .admin-metro .mt-brand-initial { display: inline-flex; align-items: center; justify-content: center; background: var(--m-accent); color: var(--m-accent-fg); font-weight: 700; }
    .admin-metro .mt-brand-name { font-size: 1.125rem; white-space: nowrap; }
    .admin-metro .mt-groups { display: flex; flex: 1 1 auto; flex-wrap: wrap; margin: 0; padding: 0; list-style: none; }
    .admin-metro .mt-tools { display: flex; flex: none; align-items: stretch; margin-left: auto; }
    .admin-metro .mt-rel { position: relative; }
    .admin-metro :is(.mt-group, .mt-tool) { display: inline-flex; align-items: center; gap: .5rem; min-height: 3.5rem; height: 100%; padding: 0 .875rem; border: 0;
        background: none; color: #fff; font-size: .9375rem; font-weight: 600; line-height: 1.2; white-space: nowrap; cursor: pointer; }
    .admin-metro :is(.mt-group, .mt-tool):hover { background: #34342f; }
    .admin-metro :is(.mt-group, .mt-tool):focus-visible { outline: 3px solid #fff; outline-offset: -3px; }
    .admin-metro .mt-group.is-current { box-shadow: inset 0 -4px 0 #fff; }
    .admin-metro .mt-group.tm-premium { box-shadow: inset 0 0 0 2px #9ca3af; }
    .admin-metro .mt-group.is-current.tm-premium { box-shadow: inset 0 0 0 2px #9ca3af, inset 0 -4px 0 #fff; }
    .admin-metro :is(.mt-group, .mt-tool).is-open { background: var(--m-accent); color: var(--m-accent-fg); }
    .admin-metro :is(.mt-group, .mt-tool).is-open:hover { background: var(--m-accent-hover); }
    .admin-metro .mt-chev { font-size: .625rem; transition: transform .15s; }
    .admin-metro .is-open > .mt-chev { transform: rotate(180deg); }
    .admin-metro .mt-badge { display: inline-flex; min-width: 1.375rem; height: 1.375rem; align-items: center; justify-content: center; padding: 0 .375rem; background: #fff; color: var(--m-ink); font-size: .75rem; font-weight: 700; }
    .admin-metro .is-open > .mt-badge { background: var(--m-accent-fg); color: var(--m-accent); }
    /* Edytowana witryna: ikona zaraz za logo, oddzielona kreską. Podpowiedź pod ikoną — biała z ciemnym tekstem
       (≈ 17:1), bez przerwy między ikoną a podpowiedzią, więc da się na nią najechać. Witryna w sieci: ikona #ea8f00
       na #1d1d1a ≈ 6,9:1; na otwartym przycisku ikona w kolorze tekstu przycisku. */
    .admin-metro .mt-site-slot { display: flex; flex: none; align-items: stretch; border-left: 1px solid rgba(255, 255, 255, .28); }
    .admin-metro .mt-site-icon { font-size: 1.0625rem; }
    .admin-metro .mt-site.is-sub .mt-site-icon { color: #ea8f00; }
    .admin-metro .mt-site.is-open .mt-site-icon { color: inherit; }
    .admin-metro .mt-tip { position: absolute; top: 100%; left: 0; z-index: 32; width: max-content; max-width: min(22rem, calc(100vw - 1rem)); padding: .5rem .75rem;
        background: var(--m-surface); color: var(--m-ink); border: 1px solid var(--m-line-strong); box-shadow: 0 6px 18px rgba(0, 0, 0, .25); font-size: .875rem; line-height: 1.35; }
    .admin-metro .mt-site-now { display: flex; align-items: flex-start; gap: .625rem; margin: 0 0 .5rem; color: var(--m-ink); font-size: .9375rem; line-height: 1.3; }
    .admin-metro .mt-site-now i { margin-top: .2rem; color: var(--m-accent-ink); }
    .admin-metro .mt-site-kind { display: block; color: var(--m-muted); font-size: .8125rem; }
    .admin-metro .mt-site-switch { padding-top: .5rem; border-top: 1px solid var(--m-line); }
    .admin-metro .mt-avatar { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; background: #fff; color: var(--m-ink); font-weight: 700; }

    /* Mega menu grupy: panel pod paskiem na całą szerokość, bloki w kolumnach, w bloku kafle po dwa — wszystkie tej samej
       wysokości (mieszczą nazwę w dwóch wierszach). Pozycja z podpozycjami zajmuje cały rząd bloku: kafel w lewej kolumnie,
       lista podpozycji obok w prawej, więc nie rozciąga sąsiednich kafli i siatka zostaje równa. Kolejność jak w menu. */
    .admin-metro .mt-mega { position: absolute; top: 100%; left: 0; right: 0; max-height: calc(100vh - 3.5rem); overflow-y: auto; background: var(--m-bg); color: var(--m-ink); border-bottom: 1px solid var(--m-line-strong); }
    .admin-metro .mt-mega-inner { padding: 1.25rem 1.5rem 1.75rem; }
    .admin-metro .mt-mega-title { margin: 0 0 1rem; color: var(--m-ink); font-family: var(--m-font-heading); font-size: 1.75rem; font-weight: 300; line-height: 1.2; }
    .admin-metro .mt-blocks { display: grid; grid-template-columns: repeat(auto-fill, minmax(22rem, 1fr)); gap: 1.5rem 2rem; align-items: start; }
    .admin-metro .mt-block-title { margin: 0 0 .5rem; color: var(--m-muted); font-size: .8125rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
    .admin-metro .mt-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .375rem; margin: 0; padding: 0; list-style: none; }
    .admin-metro .mt-cell { display: flex; min-width: 0; flex-direction: column; }
    .admin-metro .mt-cell.has-children { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .375rem; }
    .admin-metro .mt-cell.has-children > .mt-tile { align-self: start; }
    .admin-metro .mt-tile { position: relative; display: flex; flex: 1 1 auto; flex-direction: column; justify-content: space-between; gap: .5rem; min-height: 5.5rem; padding: .625rem .75rem;
        background: var(--m-surface); color: var(--m-ink); text-decoration: none; transition: background-color .15s, transform .1s; }
    .admin-metro .mt-tile:hover { background: var(--m-head); }
    .admin-metro .mt-tile:active { transform: scale(.98); }
    .admin-metro .mt-tile:focus-visible { z-index: 1; outline: 3px solid var(--m-focus); outline-offset: 2px; }
    .admin-metro .mt-tile-icon { font-size: 1.25rem; line-height: 1; color: var(--m-accent-ink); }
    .admin-metro .mt-tile-label { padding-right: 1.75rem; font-size: .875rem; font-weight: 600; line-height: 1.25; overflow-wrap: break-word; }
    .admin-metro .mt-tile-badge { position: absolute; top: .5rem; right: .5rem; display: inline-flex; min-width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; padding: 0 .375rem;
        background: var(--m-accent); color: var(--m-accent-fg); font-size: .75rem; font-weight: 700; }
    .admin-metro .mt-tile-badge.is-muted { background: #e5e7eb; color: var(--m-ink); }
    .admin-metro .mt-tile.tm-premium { box-shadow: inset 0 0 0 2px var(--m-accent); }
    .admin-metro .mt-tile.is-current { background: var(--m-accent); color: var(--m-accent-fg); }
    .admin-metro .mt-tile.is-current:hover { background: var(--m-accent-hover); }
    .admin-metro .mt-tile.is-current .mt-tile-icon { color: inherit; }
    .admin-metro .mt-tile.is-current .mt-tile-badge { background: var(--m-accent-fg); color: var(--m-accent); }
    .admin-metro .mt-children { display: flex; flex-direction: column; justify-content: center; min-height: 5.5rem; margin: 0; padding: .125rem 0; list-style: none; background: var(--m-surface); }
    .admin-metro .mt-children a { display: flex; align-items: center; min-height: 1.75rem; padding: .125rem .75rem; color: var(--m-ink); font-size: .8125rem; text-decoration: none; }
    .admin-metro .mt-children a:hover { background: var(--m-hover); }
    .admin-metro .mt-children a:focus-visible { outline: 3px solid var(--m-focus); outline-offset: -3px; }
    .admin-metro .mt-children a.is-current { font-weight: 700; box-shadow: inset 4px 0 0 var(--m-accent); }

    /* Rozwijane okienka paska (Skróty, witryna, konto): przy prawej krawędzi, białe z wyraźną ramką. */
    .admin-metro .mt-drop { position: absolute; top: 100%; right: 0; z-index: 31; width: min(24rem, calc(100vw - 1rem)); max-height: calc(100vh - 4rem); overflow-y: auto;
        padding: .75rem; background: var(--m-surface); color: var(--m-ink); border: 1px solid var(--m-line-strong); }
    .admin-metro .mt-drop--menu { padding: .25rem 0; }
    .admin-metro .mt-drop--left { right: auto; left: 0; }
    .admin-metro .mt-drop-title { display: block; margin: .5rem 0 .25rem; color: var(--m-muted); font-size: .8125rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
    .admin-metro .mt-drop-title:first-child { margin-top: 0; }
    .admin-metro .mt-kbd { margin-left: .25rem; padding: 0 .375rem; background: var(--m-head); color: var(--m-ink); font-family: ui-monospace, monospace; letter-spacing: 0; text-transform: none; }
    .admin-metro .mt-search { width: 100%; min-height: 2.75rem; margin-bottom: .5rem; padding: .5rem .75rem; border: 2px solid var(--m-line-strong); background: #fff; color: var(--m-ink); }
    .admin-metro .mt-search:focus { border-color: var(--m-accent); outline: none; box-shadow: 0 0 0 2px var(--m-accent); }
    .admin-metro .mt-list { margin: 0; padding: 0; list-style: none; }
    .admin-metro .mt-list a { display: flex; min-height: 2.5rem; flex-wrap: wrap; align-items: center; gap: 0 .5rem; padding: .25rem .5rem; color: var(--m-ink); text-decoration: none; }
    .admin-metro .mt-list a:hover { background: var(--m-hover); }
    .admin-metro .mt-list a:focus-visible, .admin-metro :is(.mt-unpin, .mt-pin):focus-visible { outline: 3px solid var(--m-focus); outline-offset: -3px; }
    .admin-metro .mt-list a > i { color: var(--m-muted); font-size: .75rem; }
    .admin-metro .mt-list-label { font-weight: 600; }
    .admin-metro .mt-list-path { flex-basis: 100%; color: var(--m-muted); font-size: .75rem; }
    .admin-metro .mt-list-row { display: flex; align-items: center; }
    .admin-metro .mt-list-row > a { flex: 1 1 auto; min-width: 0; }
    .admin-metro .mt-unpin { display: inline-flex; width: 2.5rem; height: 2.5rem; flex: none; align-items: center; justify-content: center; border: 0; background: none; color: var(--m-muted); cursor: pointer; }
    .admin-metro .mt-unpin:hover { background: var(--m-hover); color: var(--m-ink); }
    .admin-metro .mt-pin { display: flex; width: 100%; min-height: 2.75rem; align-items: center; gap: .5rem; margin-top: .5rem; padding: .25rem .5rem; border: 0; background: var(--m-head); color: var(--m-ink); font-weight: 600; text-align: left; cursor: pointer; }
    .admin-metro .mt-pin:hover { background: var(--m-side-hover); }
    .admin-metro .mt-empty { padding: .5rem; color: var(--m-muted); font-size: .875rem; }

    /* Przyciski w listach okienek (np. „Edytuj na stronie” w okienku Edytuj na stronie publicznej) wyglądają jak linki listy. */
    .admin-metro .mt-list .mt-list-btn { display: flex; width: 100%; min-height: 2.5rem; align-items: center; gap: 0 .5rem; padding: .25rem .5rem; border: 0; background: none;
        color: var(--m-ink); font: inherit; text-align: left; cursor: pointer; }
    .admin-metro .mt-list .mt-list-btn:hover { background: var(--m-hover); }
    .admin-metro .mt-list .mt-list-btn:focus-visible { outline: 3px solid var(--m-focus); outline-offset: -3px; }
    .admin-metro .mt-list .mt-list-btn > i { color: var(--m-muted); font-size: .75rem; }

    @media (prefers-reduced-motion: reduce) {
        .admin-metro .mt-tile:active { transform: none; }
        .admin-metro .mt-chev { transition: none; }
    }
    html.contrast-bw .admin-metro :is(.mt-nav, .mt-group, .mt-tool, .mt-tile, .mt-mega) { background: #000 !important; color: #ff0 !important; }
    html.contrast-bw .admin-metro :is(.mt-group, .mt-tool, .mt-tile).is-open, html.contrast-bw .admin-metro .mt-tile.is-current { background: #ff0 !important; color: #000 !important; }
    html.contrast-bw .admin-metro .mt-tile { box-shadow: inset 0 0 0 2px #ff0 !important; }
    html.contrast-bw .admin-metro .mt-tile :is(.mt-tile-icon, .mt-tile-badge) { color: inherit !important; }
    html.contrast-bw .admin-metro .mt-site-icon { color: inherit !important; }
    html.contrast-bw .admin-metro .mt-tip { background: #000 !important; color: #ff0 !important; border: 2px solid #ff0; }
    @media (forced-colors: active) {
        .admin-metro .mt-tile { border: 2px solid CanvasText; }
        .admin-metro :is(.mt-group, .mt-tool).is-open, .admin-metro .mt-tile.is-current { outline: 3px solid Highlight; outline-offset: -3px; }
    }
