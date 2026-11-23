{{--
    Style modułu „Warsztaty – Kreatywni” (prefiks kc-ws-, kc-cal-): karty terminów, siatka miesiąca i sekcja na stronie
    głównej. Dołączane raz (@once) przez partials/workshops-calendar i partials/home/workshops. Kontrast: zieleń leśna
    na żółtym i białym > 7:1, wyciszony #4a5a44 na kremie ok. 6:1.
--}}
@once
    <style>
        .kc-ws-intro { max-width: 52rem; margin-bottom: 2.5rem; }
        .kc-ws-jump { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; margin: 0 0 2rem; padding: 0; list-style: none; font-weight: 700; }
        .kc-ws-jump a { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.75rem; color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-ws-month { margin: 0 0 1.25rem; padding-bottom: .45rem; border-bottom: 3px solid var(--kc-lime); font-size: clamp(1.35rem, 1.1rem + .8vw, 1.75rem); font-weight: 800; color: var(--kc-forest); }
        .kc-ws-month-sec + .kc-ws-month-sec { margin-top: 3rem; }
        .kc-ws-list-sec { margin-top: 4rem; }
        .kc-ws-list { display: grid; gap: 1.5rem; margin: 0; padding: 0; list-style: none; }
        @media (min-width: 900px) { .kc-ws-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1100px) { .kc-ws-list.is-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .kc-ws-card { position: relative; display: flex; flex-direction: column; overflow: hidden; border-radius: var(--kc-radius); background: #fff; scroll-margin-top: 7rem; }
        .kc-ws-card:target { box-shadow: 0 0 0 4px var(--kc-lime); }
        .kc-ws-photo { aspect-ratio: 2 / 1; background: var(--kc-mint); }
        .kc-ws-photo img { width: 100%; height: 100%; object-fit: cover; }
        .kc-ws-date { position: absolute; top: 1rem; left: 1rem; display: flex; flex-direction: column; align-items: center; min-width: 4.25rem;
            padding: .6rem .65rem .5rem; border-radius: 1rem; background: var(--kc-sun); color: var(--kc-forest); line-height: 1; }
        .kc-ws-card.no-photo .kc-ws-date { position: static; align-self: flex-start; margin: 1.35rem 1.5rem 0; }
        .kc-ws-day { font-size: 1.9rem; font-weight: 800; }
        .kc-ws-mon { margin-top: .2rem; font-size: .9rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .kc-ws-body { display: flex; flex: 1; flex-direction: column; gap: .6rem; padding: 1.35rem 1.5rem 1.5rem; }
        .kc-ws-name { margin: 0; font-size: 1.3rem; font-weight: 800; line-height: 1.3; color: var(--kc-forest); }
        .kc-ws-name a { color: inherit; text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-ws-name a:hover { text-decoration-color: var(--kc-forest); }
        .kc-ws-meta { display: grid; gap: .35rem; margin: 0; padding: 0; list-style: none; font-weight: 600; }
        .kc-ws-meta li { display: flex; gap: .6rem; align-items: flex-start; line-height: 1.45; }
        .kc-ws-meta i { flex: none; width: 1.1rem; margin-top: .2rem; color: var(--kc-green); text-align: center; }
        .kc-ws-desc p { margin: 0 0 .6rem; line-height: 1.65; }
        .kc-ws-desc p:last-child { margin-bottom: 0; }
        .kc-ws-cta { margin-top: auto; padding-top: .5rem; }
        .kc-ws-empty { max-width: 44rem; padding: 2rem; border-radius: var(--kc-radius); background: #fff; }
        .kc-ws-empty p { margin: 0 0 1rem; line-height: 1.6; }
        .kc-ws-past { margin-top: 4rem; }
        .kc-ws-past ul { margin: 0; padding: 0; list-style: none; }
        .kc-ws-past li { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; padding: .75rem 0; border-bottom: 1px solid var(--kc-line); line-height: 1.5; }
        .kc-ws-past time { min-width: 9rem; font-weight: 700; color: var(--kc-forest); }
        .kc-ws-past-place { color: var(--kc-muted); }

        /* Siatka miesiąca (partials/workshops-month): tabela z nagłówkami dni, przejścia ?miesiac=RRRR-MM. */
        .kc-cal-sec { scroll-margin-top: 7rem; }
        .kc-cal-nav { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1rem; margin-bottom: 1rem; }
        .kc-cal-nav h2 { margin: 0; font-size: clamp(1.35rem, 1.1rem + .8vw, 1.75rem); font-weight: 800; color: var(--kc-forest); }
        .kc-cal-nav a { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.75rem; padding: 0 .25rem; font-weight: 700; color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-cal-nav a:hover { text-decoration-color: var(--kc-forest); }
        .kc-cal { width: 100%; border-collapse: separate; border-spacing: .3rem; table-layout: fixed; }
        .kc-cal th { padding: .35rem 0; font-size: .85rem; font-weight: 800; color: var(--kc-forest); text-align: center; text-transform: uppercase; letter-spacing: .04em; }
        .kc-cal td { height: 6.75rem; padding: .45rem; border-radius: .8rem; background: #fff; vertical-align: top; }
        .kc-cal td.is-out { background: transparent; color: var(--kc-muted); }
        .kc-cal td.is-today { box-shadow: inset 0 0 0 3px var(--kc-lime); }
        .kc-cal-day { display: block; font-weight: 800; color: var(--kc-forest); }
        .kc-cal td.is-out .kc-cal-day { font-weight: 600; color: var(--kc-muted); }
        .kc-cal ul { display: grid; gap: .25rem; margin: .3rem 0 0; padding: 0; list-style: none; }
        .kc-cal-ev { display: block; padding: .3rem .45rem; border-radius: .55rem; background: var(--kc-sun); color: var(--kc-forest); font-size: .8rem; font-weight: 700; line-height: 1.25; text-decoration: none; overflow-wrap: anywhere; }
        a.kc-cal-ev:hover { text-decoration: underline; }
        a.kc-cal-ev:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 2px; }
        .kc-cal-ev.is-past { background: var(--kc-mint); }
        .kc-cal-time { display: block; font-weight: 800; }
        .kc-cal-none { margin: 1rem 0 0; font-weight: 600; }
        @media (max-width: 640px) {
            .kc-cal { border-spacing: .15rem; }
            .kc-cal th { font-size: .72rem; }
            .kc-cal td { height: 3.4rem; padding: .25rem; border-radius: .5rem; }
            .kc-cal-ev { padding: .15rem .2rem; font-size: .7rem; text-align: center; }
            .kc-cal-ev .kc-cal-name { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        }
        @media (forced-colors: active) { .kc-ws-date, .kc-cal-ev { border: 1px solid CanvasText; } .kc-cal td.is-today { outline: 2px solid Highlight; } }
    </style>
@endonce
