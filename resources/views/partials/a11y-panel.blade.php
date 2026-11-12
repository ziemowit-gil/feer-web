{{--
    Pasek ułatwień dostępu pod paskiem górnym (partials/topbar i nagłówki szablonów), widoczny po kliknięciu
    przycisku „Dostępność”. Płasko: szare pole, białe przyciski bez ramek, opcje w podpisanych grupach
    (Rozmiar tekstu, Czytelność, Kontrast). Włączona opcja ma pełne tło marki z białym tekstem, rozmiar tekstu
    pokazuje bieżącą wartość (np. 120%), a Wiersze i Litery dwie kropki poziomu (0, 1 albo 2 kroki).

    Wymaga w rodzicu Alpine `open` (stan paska) oraz `$refs.a11yToggle` (przycisk otwierający — Escape
    zamyka pasek i oddaje mu fokus). Logika przycisków (data-a11y-*) jest w resources/js/app.js;
    tutaj tylko markup zgodny z WCAG 2.2:
      • każdy przycisk ma widoczną etykietę zawartą w nazwie dostępnej (2.5.3), a cel dotyku ≥ 36 px wysokości (2.5.8),
      • przełączniki mają aria-pressed (4.1.2), grupy mają nazwę (1.3.1),
      • tekst ≥ 4,5:1 we wszystkich stanach (w spoczynku ≥ 7:1; włączony zależy od koloru marki, np. FEER 5,3:1, domyślny 7:1);
        stan włączenia to zmiana jasności tła,
        nie samego odcienia (1.4.1), a w trybie czarno-żółtym i wymuszonych kolorach ma własne style.
    Szerokość treści jak w nagłówku (72rem); szablon może ją zmienić zmienną --a11y-bar-w (np. BU2: 1200px).
    Style są wbudowane (bez buildu CSS).
--}}
@once
<style>
    #a11y-panel.a11yb { background: #f3f4f6; color: #1a1a1a; }
    .a11yb-in { max-width: var(--a11y-bar-w, 72rem); margin: 0 auto; padding: .625rem 1rem .75rem; display: flex; flex-wrap: wrap; align-items: flex-end; gap: .625rem 1.25rem; }
    .a11yb-grp { display: flex; flex-direction: column; gap: .3125rem; min-width: 0; }
    .a11yb-lbl { font-size: .6875rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; line-height: 1; color: #374151; }
    .a11yb-btns { display: flex; flex-wrap: wrap; gap: .25rem; }
    .a11yb-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4375rem; min-height: 2.25rem; padding: .375rem .6875rem;
        border: 0; border-radius: .375rem; background: #fff; color: #1a1a1a; font: inherit; font-size: .8125rem; font-weight: 600; line-height: 1.2;
        white-space: nowrap; text-decoration: none; cursor: pointer; transition: background-color .15s, color .15s; }
    .a11yb-btn > i { width: 1rem; flex: none; text-align: center; color: #4b5563; }
    .a11yb-btn:hover { background: var(--color-brand-light, #e8f0ff); color: var(--color-brand-dark, #1752bf); }
    .a11yb-btn:hover > i { color: inherit; }
    .a11yb-btn[aria-pressed="true"] { background: var(--color-brand-dark, #1752bf); color: #fff; }
    .a11yb-btn[aria-pressed="true"] > i { color: #fff; }
    .a11yb-btn[aria-pressed="true"]:hover { background: #1a1a1a; color: #fff; }
    .a11yb-btn:focus-visible { outline: 3px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; }
    .a11yb-btn[aria-disabled="true"] { color: #6b7280; cursor: not-allowed; }
    .a11yb-btn[aria-disabled="true"]:hover { background: #fff; color: #6b7280; }

    /* Rozmiar tekstu: A− | bieżąca wartość (klik = domyślny) | A+ */
    .a11yb-size { min-width: 2.25rem; padding-left: .5rem; padding-right: .5rem; font-size: .875rem; font-weight: 700; }
    .a11yb-size sup { font-size: .7em; }
    .a11yb-now { min-width: 3.75rem; font-variant-numeric: tabular-nums; }

    /* Poziom odstępów (Wiersze, Litery): dwie kropki, wypełnione wg data-level ustawianego w app.js. */
    .a11yb-lvl { display: inline-flex; gap: .1875rem; margin-left: .125rem; }
    .a11yb-lvl > span { box-sizing: border-box; width: .375rem; height: .375rem; border-radius: 50%; border: 1.5px solid currentColor; }
    .a11yb-btn[data-level="1"] .a11yb-lvl > span:first-child,
    .a11yb-btn[data-level="2"] .a11yb-lvl > span { background: currentColor; }

    /* Próbki trybów kontrastu */
    .a11yb-sw { display: inline-flex; width: 1rem; height: 1rem; flex: none; align-items: center; justify-content: center; border-radius: .1875rem;
        font-size: .625rem; font-weight: 900; line-height: 1; }

    /* Akcje po prawej: Domyślne i Deklaracja bez tła, Schowaj jako przycisk. */
    .a11yb-end { margin-left: auto; display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; }
    .a11yb-quiet { background: transparent; }
    .a11yb-quiet > i { color: var(--color-brand-dark, #1752bf); }
    .a11yb-quiet:hover { background: #fff; color: #1a1a1a; }
    .a11yb-quiet:hover > i { color: var(--color-brand-dark, #1752bf); }

    @media (max-width: 639px) {
        .a11yb-in { gap: .75rem; padding-top: .75rem; }
        .a11yb-grp { width: 100%; }
        .a11yb-end { margin-left: 0; width: 100%; }
    }

    /* Czarno-żółty: wszystkie przyciski są czarne z żółtą ramką (app.css) — włączony dostaje żółte tło. */
    html.contrast-bw #a11y-panel .a11yb-btn > i { color: inherit !important; }
    html.contrast-bw #a11y-panel .a11yb-btn[aria-pressed="true"] { background: #ff0 !important; color: #000 !important; }
    html.contrast-bw #a11y-panel .a11yb-btn[aria-pressed="true"] :is(i, .a11yb-lvl, .a11yb-lvl > span) { background-color: transparent !important; color: #000 !important; border-color: #000 !important; }
    html.contrast-bw #a11y-panel .a11yb-btn[data-level="1"] .a11yb-lvl > span:first-child,
    html.contrast-bw #a11y-panel .a11yb-btn[data-level="2"] .a11yb-lvl > span { background-color: currentColor !important; }
    @media (forced-colors: active) {
        #a11y-panel .a11yb-btn { border: 1px solid ButtonText; }
        #a11y-panel .a11yb-btn[aria-pressed="true"] { forced-color-adjust: none; background: Highlight; color: HighlightText; border-color: Highlight; }
        #a11y-panel .a11yb-btn[aria-pressed="true"] * { color: HighlightText; }
    }
    @media print { #a11y-panel { display: none !important; } }
</style>
@endonce

<div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności" class="a11yb">
    <div class="a11yb-in">

        <div class="a11yb-grp">
            <span class="a11yb-lbl" aria-hidden="true">Rozmiar tekstu</span>
            <div class="a11yb-btns" role="group" aria-label="Rozmiar tekstu">
                <button type="button" data-a11y-font="down" class="a11yb-btn a11yb-size" aria-label="Zmniejsz tekst">A<sup aria-hidden="true">−</sup></button>
                <button type="button" data-a11y-font="reset" class="a11yb-btn a11yb-now" aria-label="Rozmiar tekstu 100% — przywróć domyślny"><span data-a11y-font-value>100%</span></button>
                <button type="button" data-a11y-font="up" class="a11yb-btn a11yb-size" aria-label="Powiększ tekst">A<sup aria-hidden="true">+</sup></button>
            </div>
        </div>

        <div class="a11yb-grp">
            <span class="a11yb-lbl" aria-hidden="true">Czytelność</span>
            <div class="a11yb-btns" role="group" aria-label="Czytelność tekstu">
                <button type="button" data-a11y-lh data-a11y-name="Wiersze" class="a11yb-btn" aria-pressed="false" aria-label="Wiersze: Normalne → kliknij: Zwiększone">
                    <i class="fa-solid fa-arrows-up-down" aria-hidden="true"></i>Wiersze<span class="a11yb-lvl" aria-hidden="true"><span></span><span></span></span>
                </button>
                <button type="button" data-a11y-ls data-a11y-name="Litery" class="a11yb-btn" aria-pressed="false" aria-label="Litery: Normalny → kliknij: Szeroki">
                    <i class="fa-solid fa-text-width" aria-hidden="true"></i>Litery<span class="a11yb-lvl" aria-hidden="true"><span></span><span></span></span>
                </button>
                <button type="button" data-a11y-sans class="a11yb-btn" aria-pressed="false" aria-label="Bez szeryfów — czcionka bezszeryfowa">
                    <i class="fa-solid fa-font" aria-hidden="true"></i>Bez szeryfów
                </button>
                <button type="button" data-a11y-underline-links class="a11yb-btn" aria-pressed="false" aria-label="Podkreśl linki">
                    <i class="fa-solid fa-underline" aria-hidden="true"></i>Linki
                </button>
                <button type="button" data-a11y-focus class="a11yb-btn" aria-pressed="false" aria-label="Wyraźny fokus">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i>Fokus
                </button>
                <button type="button" data-a11y-animations class="a11yb-btn" aria-pressed="false" aria-label="Wstrzymaj animacje">
                    <i class="fa-solid fa-pause" aria-hidden="true"></i>Animacje
                </button>
            </div>
        </div>

        <div class="a11yb-grp">
            <span class="a11yb-lbl" aria-hidden="true">Kontrast</span>
            <div class="a11yb-btns" role="group" aria-label="Tryb kontrastu">
                <button type="button" data-a11y-contrast="contrast" class="a11yb-btn" aria-pressed="false" aria-label="Wysoki kontrast">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>Kontrast
                </button>
                <button type="button" data-a11y-contrast="contrast-bw" class="a11yb-btn" aria-pressed="false" aria-label="Czarno-żółty">
                    <span class="a11yb-sw" aria-hidden="true" style="background:#000;color:#ff0">A</span>Czarno-żółty
                </button>
                <button type="button" data-a11y-contrast="contrast-gray" class="a11yb-btn" aria-pressed="false" aria-label="Szarość — skala szarości">
                    <span class="a11yb-sw" aria-hidden="true" style="background:#6b7280;color:#fff">A</span>Szarość
                </button>
            </div>
        </div>

        <div class="a11yb-end">
            <button type="button" data-a11y-reset class="a11yb-btn a11yb-quiet" aria-label="Przywróć domyślne ustawienia dostępności">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Domyślne
            </button>
            @if (\Illuminate\Support\Facades\Route::has('accessibility.show'))
                <a href="{{ route('accessibility.show') }}" class="a11yb-btn a11yb-quiet" aria-label="Deklaracja dostępności">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>Deklaracja
                </a>
            @endif
            {{-- Schowaj pasek (stan zapamiętany); fokus wraca na przycisk „Dostępność”. --}}
            <button type="button" @click="open = false; $nextTick(() => $refs.a11yToggle?.focus())" class="a11yb-btn" aria-label="Schowaj pasek dostępności">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>Schowaj
            </button>
        </div>
    </div>
</div>
