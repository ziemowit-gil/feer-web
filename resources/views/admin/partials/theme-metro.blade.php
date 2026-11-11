{{--
    Styl panelu „Metro” — alternatywa dla schematów kolorów (Mój profil → Wygląd panelu, User::ADMIN_SCHEMES['metro']).
    Wzorowany na Metro UI 5 (metroui.org.ua): jasne tło strony i pasek boczny z akcentem przy aktywnej pozycji, białe pola,
    kafle 150 px z odstępem 10 px (etykieta u dołu, ikona pośrodku, licznik w rogu) w kolorach marki, płaskie
    przyciski i pola. Ładowany po theme-joomla tylko dla tego schematu; działa na <body class="admin-joomla admin-metro">,
    więc ten sam markup obsługuje oba style. Style wbudowane (push nie buduje CSS). Zaokrąglenie ustawia jedna zmienna:
    --m-radius; jasność teł: App\Support\AdminMetro::SURFACES (strona, menu) i ::tones() (kafle).
    Kontrast (WCAG): tekst ≥ 4,5:1 w stanach normalny / najechany / aktywny / otwarty, bez opacity na treści; pary kolorów
    kafli wylicza App\Support\AdminMetro (kolor marki przez Color::button), obrysy pól #767676 (≥ 3:1 na bieli i szarościach).
--}}
@php
    $mtTones = \App\Support\AdminMetro::tones($brandPalette['color']);
    $mtBrand = $mtTones['brand'];
    $mtSurf = \App\Support\AdminMetro::SURFACES;
    // Akcent jako kolor TEKSTU (np. licznik na białym kafelku): ciemny odcień marki, jeśli czyta się na najciemniejszej powierzchni, inaczej ink.
    $mtAccentInk = \App\Support\Color::contrast($brandPalette['dark'], $mtSurf['side_hover']) >= 4.5 ? $brandPalette['dark'] : \App\Support\AdminMetro::INK;
@endphp
<style>
    /* ── Tokeny ──────────────────────────────────────────────────────────────────────────────── */
    body.admin-metro {
        --m-radius: 0;
        --m-bg: {{ $mtSurf['page'] }};
        --m-surface: #fff;
        --m-side: {{ $mtSurf['side'] }};
        --m-side-hover: {{ $mtSurf['side_hover'] }};
        --m-hover: {{ $mtSurf['row_hover'] }};
        --m-head: {{ $mtSurf['head'] }};
        --m-ink: {{ \App\Support\AdminMetro::INK }};
        --m-muted: {{ \App\Support\AdminMetro::MUTED }};
        --m-line: #d9d9d9;
        --m-line-strong: #767676;
        --m-accent: {{ $mtBrand['bg'] }};
        --m-accent-fg: {{ $mtBrand['fg'] }};
        --m-accent-hover: {{ $mtBrand['hover'] }};
        --m-accent-active: {{ $mtBrand['active'] }};
        --m-accent-ink: {{ $mtAccentInk }};
        --m-danger: {{ \App\Support\AdminMetro::DANGER }};
        --m-focus: {{ \App\Support\AdminMetro::INK }};
        --m-font: "Segoe UI", system-ui, -apple-system, Roboto, "Helvetica Neue", Arial, sans-serif;
        --m-font-heading: "Montserrat", "Segoe UI", system-ui, sans-serif;
        /* Zmienne motywu Joomla (nagłówki tabel, linie) przejmują barwy Metro. */
        --j-bg: var(--m-bg); --j-cream: var(--m-surface); --j-cream-hover: var(--m-hover); --j-line: var(--m-line); --j-head: var(--m-head);
        background: var(--m-bg);
        font-family: var(--m-font);
    }
    @foreach ($mtTones as $mtName => $mtTone)
    .metro-tile[data-tone="{{ $mtName }}"] { --t-bg: {{ $mtTone['bg'] }}; --t-fg: {{ $mtTone['fg'] }}; --t-hover: {{ $mtTone['hover'] }}; --t-active: {{ $mtTone['active'] }}; }
    @endforeach

    /* ── Płasko: bez zaokrągleń i cieni ──────────────────────────────────────────────────────── */
    .admin-metro *:not(input[type="radio"]):not(.animate-spin), .admin-metro *::before, .admin-metro *::after { border-radius: var(--m-radius); }
    .admin-metro [style*="border-radius"] { border-radius: var(--m-radius) !important; }
    .admin-metro :is(.shadow, .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl) { box-shadow: none; }
    /* Okna i listy rozwijane bez cienia odcina cienka, wyraźna ramka (≥ 3:1), nie ozdoba. */
    .admin-metro :is(.shadow-lg, .shadow-xl, .shadow-2xl), .admin-metro .picker-popover { border: 1px solid var(--m-line-strong); box-shadow: none; }
    .admin-metro #main :is(.rounded, .rounded-md, .rounded-lg, .rounded-xl).bg-white:not([class*="border"]):not([class*="shadow"]) { box-shadow: none; }
    .admin-metro h1, .admin-metro h2, .admin-metro h3 { font-family: var(--m-font-heading); }

    /* ── Nagłówek: duży, lekki tytuł strony i płaskie przyciski o wysokości 44 px ───────────── */
    .admin-metro .j-topbar { border-bottom: 0; background: var(--m-surface); }
    .admin-metro .j-title { font-family: var(--m-font-heading); font-size: 1.75rem; font-weight: 300; letter-spacing: -.02em; }
    .admin-metro .j-title-icon { width: 2.5rem; height: 2.5rem; border: 0; background: var(--m-accent); color: var(--m-accent-fg); }
    .admin-metro .j-topbar :is(a, button) { min-height: 2.75rem; }
    .admin-metro .j-topbar button[aria-controls="admin-sidebar"] { width: 2.75rem; height: 2.75rem; }
    .admin-metro .j-topbar :is([data-a11y-font], [data-a11y-contrast]) { min-width: 2.75rem; }
    .admin-metro .j-topbar :is(a, button)[class*="bg-gray-100"]:hover { background: var(--m-accent); color: var(--m-accent-fg); }
    .admin-metro .j-pill { border-radius: var(--m-radius); background: var(--m-muted); }
    .admin-metro .j-link { padding: 0 .75rem; }

    /* ── Pasek boczny: jasnoszary, wiersze na całą szerokość, aktywna grupa w kolorze akcentu ── */
    .admin-metro .admin-sidebar { border-right-width: 0; background: var(--m-side); }
    .admin-metro .admin-sidebar.is-open { box-shadow: none; }
    .admin-metro .admin-sidebar :is(.nav-brand, .nav-site, .nav-search, .nav-user) { border-color: transparent; }
    .admin-metro .admin-sidebar :is(.nav-collapse-btn, [aria-label="Zamknij menu"]) { width: 2.75rem; height: 2.75rem; color: var(--m-ink); }
    .admin-metro .admin-sidebar :is(.nav-collapse-btn, [aria-label="Zamknij menu"]):hover { background: var(--m-side-hover); color: var(--m-ink); }
    .admin-metro .nav-site-btn:hover, .admin-metro .nav-site-btn[aria-expanded="true"] { background: var(--m-side-hover); }
    .admin-metro .nav-search input { min-height: 2.75rem; border: 2px solid var(--m-line-strong); background: #fff; }
    .admin-metro .nav-search input:focus { border-color: var(--m-accent); }
    .admin-metro .nav-search a:hover { background: var(--m-side-hover); }
    .admin-metro .nav-scroll { padding-left: 0; padding-right: 0; }
    .admin-metro .tm-group + .tm-group { margin-top: 0; }
    .admin-metro .tm-head { min-height: 2.75rem; padding: .25rem .875rem; border: 0; background: transparent; color: var(--m-ink); font-weight: 600; }
    .admin-metro .tm-head:hover { background: var(--m-side-hover); }
    .admin-metro .tm-head:focus-visible { outline: 3px solid var(--m-focus); outline-offset: -3px; }
    .admin-metro .tm-head .tm-chevron { color: var(--m-muted); }
    .admin-metro .tm-icon { border: 0; background: transparent; color: var(--m-ink); font-size: 1rem; }
    .admin-metro .tm-group.is-active > .tm-head { border: 0; background: var(--m-accent); color: var(--m-accent-fg); }
    .admin-metro .tm-group.is-active > .tm-head:hover { background: var(--m-accent-hover); }
    .admin-metro .tm-group.is-active > .tm-head:focus-visible { outline-color: var(--m-accent-fg); }
    .admin-metro .tm-group.is-active > .tm-head :is(.tm-icon, .tm-chevron) { background: transparent; color: var(--m-accent-fg); }
    .admin-metro .tm-group.is-active > .tm-head .tm-badge { background: var(--m-accent-fg); color: var(--m-accent); }
    .admin-metro .tm-head.tm-premium { box-shadow: inset 0 0 0 2px var(--m-accent); }
    .admin-metro .tm-group.is-active > .tm-head.tm-premium { box-shadow: inset 0 0 0 2px var(--m-accent-fg); }
    .admin-metro .tm-panel { margin: 0; padding: 0 0 .5rem; border-left: 0; }
    .admin-metro .tm-panel .bg-brand-light { background: var(--m-hover); box-shadow: inset 4px 0 0 var(--m-accent); }
    .admin-metro .tm-sub { color: var(--m-muted); }
    .admin-metro .nav-user > button:hover { background: var(--m-side-hover); }
    /* Wysuwane panele grup: nagłówek w kolorze akcentu (jak nagłówek huba Metro), pozycje na białym, aktywna w akcencie. */
    .admin-metro .nav-flyout { padding-top: 0; background: var(--m-surface); }
    .admin-metro .nav-flyout > p { margin: 0 0 .25rem; padding: .75rem 1rem; background: var(--m-accent); color: var(--m-accent-fg); font-weight: 600; }
    .admin-metro .nav-flyout > p i { color: inherit; }
    .admin-metro .nav-flyout a { min-height: 2.75rem; }
    .admin-metro .nav-flyout a:hover, .admin-metro .nav-flyout a:focus-visible { background: var(--m-hover); }
    .admin-metro .nav-flyout a > i { color: var(--m-muted); }
    .admin-metro .nav-flyout a[aria-current="page"] { background: var(--m-accent); color: var(--m-accent-fg); font-weight: 600; }
    .admin-metro .nav-flyout a[aria-current="page"] > i { color: inherit; }
    .admin-metro .nav-flyout a[aria-current="page"]:hover { background: var(--m-accent-hover); }

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

    /* Mega menu grupy: panel pod paskiem na całą szerokość, bloki w kolumnach, w bloku kafle po dwa. */
    .admin-metro .mt-mega { position: absolute; top: 100%; left: 0; right: 0; max-height: calc(100vh - 3.5rem); overflow-y: auto; background: var(--m-bg); color: var(--m-ink); border-bottom: 1px solid var(--m-line-strong); }
    .admin-metro .mt-mega-inner { padding: 1.25rem 1.5rem 1.75rem; }
    .admin-metro .mt-mega-title { margin: 0 0 1rem; color: var(--m-ink); font-family: var(--m-font-heading); font-size: 1.75rem; font-weight: 300; line-height: 1.2; }
    .admin-metro .mt-blocks { display: grid; grid-template-columns: repeat(auto-fill, minmax(22rem, 1fr)); gap: 1.5rem 2rem; align-items: start; }
    .admin-metro .mt-block-title { margin: 0 0 .5rem; color: var(--m-muted); font-size: .8125rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
    .admin-metro .mt-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .375rem; margin: 0; padding: 0; list-style: none; }
    .admin-metro .mt-cell { display: flex; min-width: 0; flex-direction: column; }
    .admin-metro .mt-tile { position: relative; display: flex; flex: 1 1 auto; flex-direction: column; justify-content: space-between; gap: .625rem; min-height: 5rem; padding: .625rem .75rem;
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
    .admin-metro .mt-children { margin: 2px 0 0; padding: 0; list-style: none; background: var(--m-surface); }
    .admin-metro .mt-children a { display: flex; align-items: center; min-height: 2.25rem; padding: .25rem .75rem; color: var(--m-ink); font-size: .8125rem; text-decoration: none; }
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

    /* ── Treść: białe pola na szarym tle, komunikaty jako pełne paski ───────────────────────── */
    .admin-metro #main :is(div, section, article, aside, details, form, fieldset, ul, ol):is(.rounded, .rounded-md, .rounded-lg, .rounded-xl, .rounded-2xl).bg-white { border: 0; box-shadow: none; }
    .admin-metro .j-panel-head { background: var(--m-surface); border-bottom: 0; color: var(--m-ink); font-family: var(--m-font-heading); font-weight: 600; font-size: 1.125rem; }
    .admin-metro .j-panel-head::before { display: none; }
    .admin-metro #main > :is([role="status"], [role="alert"]) { min-height: 3rem; align-items: center; padding: .75rem 1rem; font-weight: 600; }
    /* Komunikat po akcji (np. „Pracujesz teraz na witrynie…”): spokojnie — jasne tło z kreską akcentu, jak informacja, a nie
       pełny pas koloru; ikona i przycisk zamknięcia w kolorze tekstu. Kontrast: tekst --m-ink na --m-surface jak w treści. */
    .admin-metro #main > [role="status"].bg-brand-light { background: var(--m-surface); color: var(--m-ink); box-shadow: inset 6px 0 0 var(--m-accent); padding-left: 1.5rem; }
    .admin-metro #main > [role="status"].bg-brand-light i { color: var(--m-ink); }
    .admin-metro #main > [role="status"] .admin-flash-close:hover { background: var(--m-head, #e4e4e4); }
    .admin-metro #main > [role="status"] .admin-flash-close:focus-visible { outline: 3px solid var(--m-ink); outline-offset: 1px; }
    .admin-metro #main > [role="alert"] { background: var(--m-danger); color: #fff; }
    .admin-metro #main > [role="alert"] * { color: inherit; }
    .admin-metro #main > [role="status"].bg-gray-100 { background: var(--m-surface); color: var(--m-ink); box-shadow: inset 6px 0 0 var(--m-accent); padding-left: 1.5rem; }
    .admin-metro #main > [role="status"].bg-gray-100 i { color: var(--m-ink); }

    /* ── Pola formularzy: prostokąty z obrysem 2 px, wysokość 44 px, fokus w kolorze akcentu ── */
    .admin-metro #main :is(input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]):not([type="color"]):not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea):not([class*="border-0"]):not([class*="border-none"]):not([class*="bg-transparent"]) { border: 2px solid var(--m-line-strong); background-color: #fff; }
    .admin-metro #main :is(input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]):not([type="color"]):not([type="hidden"]):not([type="submit"]):not([type="button"]), select):not([class*="border-0"]):not([class*="border-none"]):not([class*="bg-transparent"]) { min-height: 2.75rem; }
    .admin-metro #main :is(input, select, textarea):focus { border-color: var(--m-accent); }
    .admin-metro #main :is(input[type="checkbox"], input[type="radio"]) { accent-color: var(--m-accent); }
    .admin-metro .lf-bar { padding: 1rem 1.25rem; }
    .admin-metro .lf-bar :is(select, input[type="text"]) { min-height: 2.75rem; border: 2px solid var(--m-line-strong); }
    .admin-metro .lf-bar :is(button, a) { min-height: 2.75rem; display: inline-flex; align-items: center; }
    .admin-metro .lf-bar button[style] { min-height: 2.75rem !important; }
    .admin-metro .lf-label { color: var(--m-muted); }

    /* ── Przyciski: płaskie prostokąty, cel dotyku ≥ 44 px (kolory zostają: akcent, szarości, ink) ── */
    .admin-metro #main :is(a, button)[class*="px-"]:is([class*="bg-brand-dark"], [class*="bg-gray-100"], [class*="bg-white"], [class*="bg-brand-light"], [class*="bg-ink"]) { min-height: 2.75rem; }
    .admin-metro [role="tab"] { min-height: 2.75rem; }

    /* ── Zakładki (Strony / Menu / BIP): duże, lekkie etykiety, aktywna z grubym podkreśleniem ── */
    .admin-metro nav[aria-label^="Sekcje"][style*="border-bottom-style:solid"] { border-bottom-width: 0 !important; gap: 0 1.5rem !important; margin-bottom: 1.5rem !important; }
    .admin-metro nav[aria-label^="Sekcje"][style*="border-bottom-style:solid"] a { min-height: 2.75rem !important; padding: .5rem 0 !important; border-bottom-width: 3px !important; margin-bottom: 0 !important; color: var(--m-muted); font-family: var(--m-font-heading); font-size: 1.25rem; font-weight: 400; }
    .admin-metro nav[aria-label^="Sekcje"][style*="border-bottom-style:solid"] a:hover { border-bottom-color: var(--m-line-strong); color: var(--m-ink); }
    .admin-metro nav[aria-label^="Sekcje"][style*="border-bottom-style:solid"] a[aria-current="page"] { border-bottom-color: var(--m-accent); color: var(--m-ink); font-weight: 700; }
    .admin-metro [style*="border-width:1px;border-style:solid"] { border-width: 0 !important; background: var(--m-surface) !important; }

    /* ── Stronicowanie (domyślny widok Laravela): kwadratowe przyciski 44 px, bieżąca strona w akcencie ── */
    .admin-metro nav[role="navigation"] > div:last-child > div:last-child > span { display: inline-flex; gap: 2px; box-shadow: none; }
    .admin-metro nav[role="navigation"] :is(a, span[aria-disabled] > span, span[aria-current] > span) { min-width: 2.75rem; min-height: 2.75rem; justify-content: center; margin-left: 0; border: 0; background: var(--m-surface); color: var(--m-ink); }
    .admin-metro nav[role="navigation"] a:hover { background: var(--m-side-hover); color: var(--m-ink); }
    .admin-metro nav[role="navigation"] span[aria-current] > span { background: var(--m-accent); color: var(--m-accent-fg); font-weight: 700; }
    .admin-metro nav[role="navigation"] span[aria-disabled] > span { color: var(--m-muted); }

    /* ── Pulpit: ekran startowy z grupami kafli (admin/partials/dashboard-metro) ─────────────── */
    .metro-hero { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem 2rem; margin-bottom: 2rem; }
    .metro-kicker { margin: 0; color: var(--m-muted); font-size: .8125rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
    .metro-display { margin: .25rem 0 0; color: var(--m-ink); font-family: var(--m-font-heading); font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 300; line-height: 1.1; letter-spacing: -.02em; }
    .metro-lead { margin: .5rem 0 0; color: var(--m-muted); font-size: 1rem; }
    .metro-btn { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; padding: 0 1.25rem; background: var(--m-accent); color: var(--m-accent-fg); font-size: .9375rem; font-weight: 600; text-decoration: none; }
    .metro-btn:hover { background: var(--m-accent-hover); }
    .metro-btn:active { background: var(--m-accent-active); }
    .metro-btn:focus-visible { outline: 3px solid var(--m-focus); outline-offset: 3px; }
    .metro-group { margin: 0 0 2.25rem; }
    .metro-group-title { margin: 0 0 .875rem; color: var(--m-ink); font-family: var(--m-font-heading); font-size: 1.5rem; font-weight: 300; line-height: 1.2; }
    .metro-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.375rem, 1fr)); gap: .625rem; margin: 0; padding: 0; list-style: none; }
    .metro-cell { display: flex; min-width: 0; }
    .metro-cell--wide { grid-column: span 2; }
    @media (max-width: 24rem) { .metro-cell--wide { grid-column: auto; } }
    .metro-tile { --t-bg: #fff; --t-fg: var(--m-ink); --t-hover: #f4f4f4; --t-active: #e4e4e4; position: relative; display: flex; flex: 1 1 auto; flex-direction: column; justify-content: flex-end; min-height: 9.375rem; padding: .75rem .875rem; overflow: hidden; background: var(--t-bg); color: var(--t-fg); text-decoration: none; }
    a.metro-tile:hover { background: var(--t-hover); }
    a.metro-tile:active { background: var(--t-active); transform: scale(.98); }
    a.metro-tile:focus-visible { z-index: 1; outline: 3px solid var(--m-focus); outline-offset: 3px; }
    .metro-tile-icon { position: absolute; top: 50%; left: 50%; font-size: 2.75rem; line-height: 1; transform: translate(-50%, -62%); }
    .metro-tile-label { position: relative; font-size: .9375rem; font-weight: 600; line-height: 1.25; }
    .metro-tile-badge { position: absolute; top: .625rem; right: .625rem; display: inline-flex; min-width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; padding: 0 .5rem; background: var(--t-fg); color: var(--t-bg); font-size: .875rem; font-weight: 700; }
    .metro-tile--count .metro-tile-count { position: absolute; top: .5rem; left: .875rem; font-family: var(--m-font-heading); font-size: 3rem; font-weight: 300; line-height: 1; letter-spacing: -.02em; }
    .metro-tile--count .metro-tile-icon { top: .875rem; right: .875rem; left: auto; font-size: 1.25rem; transform: none; }
    .metro-tile[data-tone="paper"] :is(.metro-tile-count, .metro-tile-icon) { color: var(--m-accent-ink); }

    /* ── Dostępność: ruch wyłączony przy prefers-reduced-motion, tryby kontrastu, wymuszone kolory ── */
    @media (prefers-reduced-motion: reduce) {
        .admin-metro *, .admin-metro *::before, .admin-metro *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; scroll-behavior: auto !important; }
        a.metro-tile:active, .admin-metro .mt-tile:active { transform: none; }
    }
    html.contrast-bw .admin-metro :is(.metro-tile, .metro-btn, .metro-tile-badge) { background: #000 !important; color: #ff0 !important; outline: 2px solid #ff0; }
    html.contrast-bw .admin-metro .metro-tile-badge { background: #ff0 !important; color: #000 !important; }
    html.contrast-bw .admin-metro :is(.mt-nav, .mt-group, .mt-tool, .mt-tile, .mt-mega) { background: #000 !important; color: #ff0 !important; }
    html.contrast-bw .admin-metro :is(.mt-group, .mt-tool, .mt-tile).is-open, html.contrast-bw .admin-metro .mt-tile.is-current { background: #ff0 !important; color: #000 !important; }
    html.contrast-bw .admin-metro .mt-tile { box-shadow: inset 0 0 0 2px #ff0 !important; }
    html.contrast-bw .admin-metro .mt-tile :is(.mt-tile-icon, .mt-tile-badge) { color: inherit !important; }
    html.contrast-bw .admin-metro .mt-site-icon { color: inherit !important; }
    html.contrast-bw .admin-metro .mt-tip { background: #000 !important; color: #ff0 !important; border: 2px solid #ff0; }
    @media (forced-colors: active) {
        .metro-tile, .metro-btn, .admin-metro .mt-tile { border: 2px solid CanvasText; }
        .admin-metro :is(.mt-group, .mt-tool).is-open, .admin-metro .mt-tile.is-current { outline: 3px solid Highlight; outline-offset: -3px; }
        .admin-metro .tm-group.is-active > .tm-head, .admin-metro .nav-flyout a[aria-current="page"] { border: 2px solid Highlight; }
    }
</style>
