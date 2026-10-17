{{--
    Motyw panelu w duchu Joomla (klasyczny „Control Panel”): ciepłe, jasne tło, kremowe pasy nagłówków,
    karty i paski narzędzi z cienką ramką, kafle szybkich akcji na pulpicie. Działa na <body class="admin-joomla">,
    więc da się go wyłączyć jedną klasą. Style wbudowane (push nie buduje CSS).
    Kontrast: ink #1a1a1a na kremie #f7f7e8 ≈ 16:1, brand-dark na kremie ≥ 6:1, biały na gray-600 (pigułki) ≥ 7:1.
--}}
<style>
    .admin-joomla {
        --j-bg: #f4f4ee;
        --j-cream: #f7f7e8;
        --j-cream-hover: #efefd6;
        --j-line: #d6d6c2;
        --j-head: #efefdf;
        background: var(--j-bg);
    }

    /* Nagłówek panelu: tytuł z ikoną strony, po prawej skróty i liczniki w pigułkach. */
    .admin-joomla .j-topbar { border-bottom: 1px solid var(--j-line); background: #fff; }
    .admin-joomla .j-title { display: flex; align-items: center; gap: .625rem; font-style: italic; }
    .admin-joomla .j-title-icon { display: inline-flex; width: 2.25rem; height: 2.25rem; flex: none; align-items: center; justify-content: center; border-radius: 4px; background: var(--j-cream); border: 1px solid var(--j-line); color: var(--color-brand-dark); font-size: 1rem; font-style: normal; }
    .admin-joomla .j-pill { display: inline-flex; min-width: 1.4rem; height: 1.4rem; align-items: center; justify-content: center; padding: 0 .4rem; border-radius: 999px; background: #4b5563; color: #fff; font-size: .75rem; font-weight: 700; line-height: 1; }
    .admin-joomla .j-pill.is-zero { background: #6b7280; }
    .admin-joomla .j-link { display: inline-flex; min-height: 2.5rem; align-items: center; gap: .4rem; padding: 0 .5rem; border-radius: 4px; color: var(--color-brand-dark); font-size: .875rem; font-weight: 700; }
    .admin-joomla .j-link:hover { text-decoration: underline; text-underline-offset: 3px; }
    .admin-joomla .j-link:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 1px; }

    /* Pasek „Szybko” jak pasek narzędzi Joomla. */
    .admin-joomla .j-toolbar { background: var(--j-cream); border-bottom: 1px solid var(--j-line); }
    .admin-joomla .j-toolbar a { background: #fff; border: 1px solid var(--j-line); border-radius: 3px; }
    .admin-joomla .j-toolbar a:hover { background: var(--color-brand-light); border-color: var(--color-brand-dark); }

    /* Menu boczne: grupy jako kremowe pasy (jak górne menu Joomla), aktywna w kolorze marki. */
    .admin-joomla .admin-sidebar { background: #fff; border-right-color: var(--j-line); }
    .admin-joomla .tm-group + .tm-group { margin-top: .25rem; }
    .admin-joomla .tm-head { min-height: 2.5rem; background: var(--j-cream); border: 1px solid var(--j-line); border-radius: 3px; color: #1a1a1a; }
    .admin-joomla .tm-head:hover { background: var(--j-cream-hover); }
    .admin-joomla .tm-icon { background: #fff; border: 1px solid var(--j-line); color: var(--color-brand-dark); }
    .admin-joomla .tm-group.is-active > .tm-head { background: var(--color-brand-light); border-color: var(--color-brand-dark); color: var(--color-brand-dark); }
    .admin-joomla .tm-panel { border-left-color: var(--j-line); }

    /* Karty w treści: cienka ramka zamiast cienia, nagłówki tabel na kremowym tle. */
    .admin-joomla #main :is(.rounded, .rounded-md, .rounded-lg, .rounded-xl).bg-white { border: 1px solid var(--j-line); box-shadow: none; border-radius: 4px; }
    .admin-joomla #main thead, .admin-joomla #main thead tr, .admin-joomla #main thead th { background-color: var(--j-head); }
    .admin-joomla #main thead th { color: #1a1a1a; }

    /* Nagłówek sekcji jak moduł Joomla — pas z trójkątem (klasa j-panel-head na pierwszym wierszu karty). */
    .admin-joomla .j-panel-head { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; background: var(--j-head); border-bottom: 1px solid var(--j-line); color: var(--color-brand-dark); font-weight: 700; }
    .admin-joomla .j-panel-head::before { content: ''; border-left: 6px solid currentColor; border-top: 5px solid transparent; border-bottom: 5px solid transparent; }

    /* Kafle szybkich akcji (pulpit). */
    .j-tiles { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr)); }
    .j-tile { display: flex; min-height: 7.5rem; flex-direction: column; align-items: center; justify-content: center; gap: .625rem; padding: 1rem .5rem; border: 1px solid var(--j-line, #d6d6c2); border-radius: 4px; background: var(--j-cream, #f7f7e8); color: var(--color-brand-dark); text-align: center; font-size: .8125rem; font-weight: 700; line-height: 1.25; transition: background-color .15s, border-color .15s; }
    .j-tile i { font-size: 2rem; }
    .j-tile:hover { background: var(--color-brand-light); border-color: var(--color-brand-dark); }
    .j-tile:focus-visible { outline: 3px solid var(--color-brand-dark); outline-offset: 2px; }
    .j-tile .j-tile-count { margin-top: -.25rem; color: #4b5563; font-size: .75rem; font-weight: 400; }
    @media (prefers-reduced-motion: reduce) { .j-tile { transition: none; } }

    /* Tryby wysokiego kontrastu panelu mają pierwszeństwo. */
    html.contrast .admin-joomla, html.contrast .admin-joomla .tm-head, html.contrast .j-tile { background: #fff; }
</style>
