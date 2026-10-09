{{-- Motyw szablonu „FEER" wg Brand booka FEER 2024: Montserrat w całym serwisie, tekst #1D1D1A,
     płaskie powierzchnie (bez gradientów i cieni), ostre, niewielkie zaokrąglenia oraz paleta brandbooka
     (#1E6DFF, #1D1D1A, #CBD5E7, partnerstwa NGO: #EA8F00) nadpisująca kolory z Ustawień → Kolory.
     Dotyczy wszystkich widoków tego szablonu, także listy i strony projektu. --}}
@php
    // Paleta motywu FEER: domyślnie Brand book 2024, ale można ją zmienić w Ustawienia → Kolory (kolory marki 1–4).
    $feerPal = [
        'brand' => \App\Support\Color::isValid($siteSettings->brand_color) ? strtolower($siteSettings->brand_color) : '#1e6dff',
        'b2' => \App\Support\Color::isValid($siteSettings->brand_color_2) ? strtolower($siteSettings->brand_color_2) : '#ea8f00',
        'b3' => \App\Support\Color::isValid($siteSettings->brand_color_3) ? strtolower($siteSettings->brand_color_3) : '#1d1d1a',
        'b4' => \App\Support\Color::isValid($siteSettings->brand_color_4) ? strtolower($siteSettings->brand_color_4) : '#cbd5e7',
    ];
    $feerLight = $feerPal['brand'] === '#1e6dff' ? '#e8f0ff' : $siteSettings->brandPalette()['light'];
@endphp
<style>
    :root {
        /* Paleta motywu FEER (domyślnie Brand book 2024; zmieniana w Ustawienia → Kolory). */
        --color-brand: {{ $feerPal['brand'] }};
        --color-brand-dark: {{ \App\Support\ThemePalette::textColor($feerPal['brand'], $feerLight) }}; /* tekst i linki w kolorze marki: ≥ 4,5:1 na bieli i jasnym tle */
        --color-brand-light: {{ $feerLight }};
        --color-brand-2: {{ $feerPal['b2'] }};   /* działania w partnerstwie z NGO */
        --color-brand-3: {{ $feerPal['b3'] }};
        --color-brand-4: {{ $feerPal['b4'] }};
        --font-sans: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        --color-ink: {{ $feerPal['b3'] }};
    }
    body { font-family: var(--font-sans); color: var(--color-ink); }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; }
    /* Płasko: bez gradientów (przesłony zdjęć zostają jako jednolite przyciemnienie) i bez cieni. */
    [class*="bg-gradient-to"] { background-image: none !important; background-color: rgb(0 0 0 / .62); }
    /* Tailwind 4: gradienty to klasy bg-linear-to-* — też wyłączone (przesłony zdjęć zostają jednolitym przyciemnieniem). */
    [class*="bg-linear-to"] { background-image: none !important; }
    [class*="bg-linear-to"][class*="from-black"] { background-color: rgb(0 0 0 / .62); }
    .shadow, .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl { box-shadow: none !important; }
    /* Mniej kółek i dużych zaokrągleń: karty i przyciski-linki. */
    .rounded-xl, .rounded-2xl, .rounded-3xl { border-radius: .5rem !important; }
    a.rounded-full { border-radius: .375rem !important; }

    /* Mikro-interakcje: miękkie uniesienie karty o 3 px, płynna zmiana tła i bardzo lekki cień przy najechaniu lub fokusie
       wewnątrz karty — informacja zwrotna, że element jest klikalny, bez uciążliwych animacji. */
    .feer-card { transition: transform .2s ease, background-color .2s ease, box-shadow .2s ease; will-change: transform; }
    .feer-card:hover, .feer-card:focus-within { transform: translateY(-3px); box-shadow: 0 10px 22px -14px rgb(0 0 0 / .28); }
    @media (prefers-reduced-motion: reduce) {
        .feer-card { transition: background-color .2s ease; will-change: auto; }
        .feer-card:hover, .feer-card:focus-within { transform: none; box-shadow: none; }
    }

    /* Ciepło zamiast surowości: krótki pasek w kolorze marki pod nagłówkami sekcji i delikatny niebieski odcień tła
       (#E8F0FF — ink na nim ma ≥ 14:1, muted ≥ 6:1) w wybranych sekcjach. */
    #ngo-news-heading::after, #ngo-projects-heading::after, #feer-shortcuts-heading::after, #mix-trainings-heading::after,
    #impact-heading::after, #methods-heading::after, #benefits-heading::after, #stats-heading::after, #photos-heading::after,
    #partners-heading::after, #latest-news-heading::after, #dane-heading::after {
        content: ""; display: block; width: 3rem; height: 4px; margin-top: .65rem; background: var(--color-brand); border-radius: 2px;
    }
    section[aria-labelledby="mix-trainings-heading"] { background-color: var(--color-brand-light); }
    .about-feer #sekcja-stats { background-color: transparent; } /* „W liczbach”: kolorowe kafle na białym tle strony */
    section[aria-labelledby="stats-heading"], section[aria-labelledby="methods-heading"] { background-color: var(--color-brand-light); }

    /* Delikatne efekty tła po bokach (tylko ≥ 1600 px, gdy są wolne marginesy — nie zachodzą na treść): płaskie, bez poświaty i gradientów — dwa cienkie, lekko obrócone kwadraty (niebieski i grafitowy)
       i pomarańczowy znaczek przy lewej krawędzi oraz siatka kropek przy prawej, z bardzo wolnym unoszeniem (tylko bez „ogranicz ruch").
       Czysto dekoracyjne — leżą za treścią (z-index -1), nie przechwytują kliknięć, nie zmieniają kontrastu tekstu;
       wyłączone w trybie wymuszonych kolorów i przy wydruku. */
    @media (min-width: 1600px) {
        body::before {
            content: ""; position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='520' height='520' viewBox='0 0 520 520' fill='none'%3E%3Crect x='60' y='140' width='150' height='150' stroke='%23{{ ltrim($feerPal['brand'], '#') }}' stroke-opacity='.10' stroke-width='2' transform='rotate%2818 135 215%29'/%3E%3Crect x='110' y='210' width='90' height='90' stroke='%23{{ ltrim($feerPal['b3'], '#') }}' stroke-opacity='.07' stroke-width='2' transform='rotate%28-12 155 255%29'/%3E%3Crect x='230' y='90' width='14' height='14' fill='%23{{ ltrim($feerPal['b2'], '#') }}' fill-opacity='.40' transform='rotate%2818 237 97%29'/%3E%3C/svg%3E"), url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220'%3E%3Cdefs%3E%3Cpattern id='d' width='22' height='22' patternUnits='userSpaceOnUse'%3E%3Ccircle cx='3' cy='3' r='2' fill='%23{{ ltrim($feerPal['brand'], '#') }}' fill-opacity='.16'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='220' height='220' fill='url%28%23d%29'/%3E%3C/svg%3E");
            background-repeat: no-repeat, no-repeat;
            background-position: -200px 18%, calc(100% + 20px) 70%;
        }
    }
    @media (min-width: 1600px) and (prefers-reduced-motion: no-preference) {
        @keyframes feer-bg-drift {
            from { background-position: -200px 18%, calc(100% + 20px) 70%; }
            to   { background-position: -200px 22%, calc(100% + 20px) 66%; }
        }
        body::before { animation: feer-bg-drift 18s ease-in-out infinite alternate; }
    }
    @media (forced-colors: active), print { body::before { display: none; } }

    /* Poziome rzędy przycisków-kategorii (na telefonie przewijane palcem): bez widocznego paska przewijania. */
    .feer-pills-row { scrollbar-width: none; -ms-overflow-style: none; }
    .feer-pills-row::-webkit-scrollbar { display: none; }

    /* Kolory linków: tylko niebieski i czarny (brandbook). Linki w treści — niebieskie z podkreśleniem, po najechaniu czarne;
       pozycje menu — czarne, po najechaniu/aktywne niebieskie (#1E6DFF na bieli 4,48:1, var(--color-brand-3) 16,9:1). */
    .prose a:not([class*="bg-"]) { color: var(--color-brand); text-decoration: underline; text-underline-offset: .2em; }
    .prose a:not([class*="bg-"]):hover { color: var(--color-brand-3); }
    /* Małe niebieskie linki akcji („Wszystkie działania →", „Zobacz …") mają podkreślenie — informacja nie opiera się tylko na kolorze
       (niebieski #1E6DFF na jasnoszarym tle ma 4,29:1), a po najechaniu stają się czarne. */
    main a.text-brand-dark:not([class*="bg-"]):not(.no-underline), main a.text-brand:not([class*="bg-"]):not(.no-underline) { text-decoration: underline; text-underline-offset: .2em; text-decoration-thickness: 1px; }
    main a.text-brand-dark:not([class*="bg-"]):hover, main a.text-brand:not([class*="bg-"]):hover { color: var(--color-brand-3); }
    nav .nav-pills > li:not([data-nav-accent]) > a:hover, nav .nav-pills > li:not([data-nav-accent]) > button:hover,
    nav .nav-pills li > ul[role="list"] a:hover { color: var(--color-brand); }

    /* Tytuły stron (H1 „hero”): spokojniejsze, bez wielkiego szarego pasa — rozmiar jak nagłówki sekcji, niebieski akcent pod spodem,
       białe tło i mniejsze odstępy. Jedna reguła dla wszystkich podstron FEER (okruszki płynnie przechodzą w tytuł). */
    h1[class*="text-5xl"][class*="text-ink"] { font-size: 1.875rem; line-height: 1.2; font-weight: 700; letter-spacing: 0; }
    h1[class*="text-5xl"][class*="text-ink"]::after { content: ""; display: block; width: 3.5rem; height: 4px; margin-top: .75rem; background: var(--color-brand); }
    :is(section, header).bg-gray-50:has(h1[class*="text-5xl"][class*="text-ink"]), :is(section, header).bg-white:has(h1[class*="text-5xl"][class*="text-ink"]) { background-color: transparent; border-bottom-width: 0; }
    div:has(> h1[class*="text-5xl"][class*="text-ink"]), div:has(> div > h1[class*="text-5xl"][class*="text-ink"]) { padding-top: 2rem; padding-bottom: 1.5rem; }

    /* Tryb „Czarno-żółty" z paska dostępności: reguły FEER (kolorowe tła pigułek, kafli, przycisków, pasek górny z !important) nie mogą
       zostawiać żółtego tekstu na jasnym lub niebieskim tle. Wszystko dostaje czarne tło, żółty tekst i żółte obramowania. */
    html.contrast-bw body :is(span, a, i, summary, details, time, small, figure, figcaption, dd, dt, blockquote, table, thead, tbody, tr, td, th):not(img) { background-color: #000 !important; background-image: none !important; color: #ff0 !important; border-color: #ff0 !important; box-shadow: none !important; }
    html.contrast-bw body .site-topbar-feer a, html.contrast-bw body .site-topbar-feer > div:first-child > button, html.contrast-bw body .site-topbar-feer > div:first-child > button[aria-expanded="true"],
    html.contrast-bw body .site-topbar-feer > div:first-child > button:hover { background: #000 !important; color: #ff0 !important; border-color: #ff0 !important; }
    html.contrast-bw body .site-topbar-feer form[role="search"] { border-color: #ff0; }
    html.contrast-bw body .feer-card, html.contrast-bw body a.feer-card { border: 2px solid #ff0 !important; }
    html.contrast-bw body a[class*="bg-"], html.contrast-bw body button[class*="bg-"] { border: 2px solid #ff0 !important; }
    html.contrast-bw body ::placeholder { color: #ff0 !important; opacity: .85; }
    html.contrast-bw body h1::after, html.contrast-bw body h2::after { background: #ff0 !important; }
    html.contrast-bw body body::before, html.contrast-bw body::before { display: none !important; }

    /* Delikatne animacje (≤ 0,5 s, bez przesuwania układu): płynne pojawienie się treści, wydłużanie niebieskiego akcentu
       pod tytułem, lekkie uniesienie przycisków i płynna zmiana koloru linków menu. Wyłączone przy „ogranicz ruch". */
    @media (prefers-reduced-motion: no-preference) {
        @keyframes feer-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes feer-bar-grow { from { width: 0; } to { width: 3.5rem; } }
        main { animation: feer-fade-in .4s ease-out both; }
        nav[aria-label="Ścieżka nawigacyjna"] { animation: feer-fade-in .5s ease-out both; }
        h1[class*="text-5xl"][class*="text-ink"]::after { animation: feer-bar-grow .6s .15s ease-out both; }
        .site-header nav a, .site-header nav button { transition: color .2s ease, background-color .2s ease; }
        a.bg-brand, a.bg-ink, button.bg-brand { transition: transform .2s ease, background-color .2s ease; }
        a.bg-brand:hover, a.bg-ink:hover, button.bg-brand:hover { transform: translateY(-1px); }
        a.bg-brand:active, a.bg-ink:active, button.bg-brand:active { transform: none; }
    }

    /* Większe odstępy między pozycjami menu (układ z podkreśleniem) i pigułkami. */
    /* Pozycje menu nie łamią się na dwie linijki (np. „O ORGANIZACJI"); gdy jest ich dużo, cały rząd zawija się czysto. */
    @media (min-width: 1024px) {
        .site-header nav ul.flex { flex-wrap: wrap; row-gap: .125rem; }
        .site-header nav > ul > li > a, .site-header nav > ul > li > button, .site-header nav > ul > li > div > a, .site-header nav > ul > li > div > button,
        .site-header nav > div > ul > li > a, .site-header nav > div > ul > li > button, .site-header nav > div > ul > li > div > a, .site-header nav > div > ul > li > div > button { white-space: nowrap; }
        /* Rozwijane podmenu i mega menu: zwykłe zawijanie tekstu, żeby długie nazwy nie nachodziły na sąsiednie kolumny. */
        .site-header .nav-mega-panel a, .site-header nav li > ul[role="list"] a, .site-header nav li > ul[role="list"] button { white-space: normal; overflow-wrap: anywhere; }
    }
    @media (min-width: 1024px) and (max-width: 1279px) {
        .site-header nav > ul.flex:not(.nav-pills):not(.nav-icons),
        .site-header nav > div > ul.flex:not(.nav-pills):not(.nav-icons) { gap: 1.25rem; }
    }
    @media (min-width: 1280px) {
        .site-header nav > ul.flex:not(.nav-pills):not(.nav-icons),
        .site-header nav > div > ul.flex:not(.nav-pills):not(.nav-icons) { gap: 1.75rem; }
    }
    @media (min-width: 1024px) {
        .site-header nav > ul.nav-pills, .site-header nav > div > ul.nav-pills { gap: .5rem; }
    }

    /* Menu główne i podmenu: większe pozycje (czytelność i cele dotyku ≥ 44 px). */
    nav .nav-pills > li > a, nav .nav-pills > li > button, nav .nav-pills > li > div.border-b-2 { font-size: 1.3125rem; padding: .7rem 1.35rem; }
    nav .nav-pills > li > div.border-b-2 > a, nav .nav-pills > li > div.border-b-2 > button { font-size: 1.3125rem; }
    nav .nav-pills li > ul[role="list"] { min-width: 19rem; padding-top: .5rem; padding-bottom: .5rem; }
    nav .nav-pills li > ul[role="list"] a, nav .nav-pills li > ul[role="list"] button { font-size: 1.25rem; line-height: 1.35; padding: .8rem 1.35rem; min-height: 3rem; display: flex; align-items: center; }
    .nav-mega-panel a { font-size: 1.125rem; line-height: 1.35; }
    /* Menu w układzie z podkreśleniem (classic): pozycje główne o 3 px większe (18 → 21 px). */
    .site-header nav > ul:not(.nav-pills) > li > a, .site-header nav > ul:not(.nav-pills) > li > button,
    .site-header nav > ul:not(.nav-pills) > li > div > a, .site-header nav > ul:not(.nav-pills) > li > div > button,
    .site-header nav > div > ul:not(.nav-pills) > li > a, .site-header nav > div > ul:not(.nav-pills) > li > button,
    .site-header nav > div > ul:not(.nav-pills) > li > div > a, .site-header nav > div > ul:not(.nav-pills) > li > div > button { font-size: clamp(1.0625rem, 1.3vw, 1.3125rem); }

    /* Pasek górny (dostępność, konto, wyszukiwarka, BIP, social): jeden smukły, ciemny rząd — biały tekst na #1D1D1A (16,9:1). */
    .site-topbar-feer > div:first-child { padding-top: .125rem; padding-bottom: .125rem; }
    .site-topbar-feer > div:first-child a,
    .site-topbar-feer > div:first-child > button { color: #fff !important; }
    .site-topbar-feer > div:first-child > button { border-color: #fff !important; background: transparent !important; border-radius: .375rem !important; }
    .site-topbar-feer > div:first-child > button:hover,
    .site-topbar-feer > div:first-child > button[aria-expanded="true"] { background: #fff !important; color: var(--color-brand-3) !important; }
    .site-topbar-feer > div:first-child a:hover { text-decoration: underline; }
    .site-topbar-feer form[role="search"] { border-color: #fff; }

    /* O organizacji FEER: bez ramek i cieni — karty (statystyki, wartości, zespół, dokumenty) mają jasnoszare tło,
       ikony i znaczniki są kwadratami o małym zaokrągleniu, bez „unoszenia" po najechaniu. */
    .about-feer [class*="ring-"] { --tw-ring-shadow: 0 0 #0000 !important; box-shadow: none !important; }
    .about-feer [class*="border-gray"] { border-color: transparent !important; }
    .about-feer [class*="rounded"][class*="bg-white"] { background-color: #f3f4f6; }
    .about-feer [class*="hover:-translate-y"]:hover { transform: none !important; }
    .about-feer .rounded-full { border-radius: .5rem; }

    /* Wsparcie i darowizna FEER (.feer-flat): bez ramek i cieni; bloki treści jasnoszare, pola formularzy zachowują ramki,
       pigułki i plakietki to małe zaokrąglenia, bez „unoszenia"; nagłówki w Montserrat (nie font „vm-display"). */
    .feer-flat :is(section, div, article, form, li, aside, details, ul, header)[class*="ring-"] { box-shadow: none !important; }
    .feer-flat :is(section, div, article, form, li, aside, details, ul, header)[class*="border-gray"] { border-color: transparent !important; }
    .feer-flat :is(section, div, article, form, aside)[class*="rounded"][class*="bg-white"] { background-color: #f3f4f6; }
    .feer-flat [class*="shadow"] { box-shadow: none !important; }
    .feer-flat [class*="hover:-translate-y"]:hover { transform: none !important; }
    .feer-flat .rounded-full:not(.h-4):not([role="progressbar"] *) { border-radius: .5rem; }
    .feer-flat .rounded-2xl, .feer-flat .rounded-xl { border-radius: .5rem !important; }
    .feer-flat .vm-display, .feer-flat .vm-section-title { font-family: inherit; font-weight: 800; }
    .feer-flat .-mt-8 { margin-top: 0; }

    /* Kontakt FEER: bez ramek — bloki sekcji (rachunki, spotkania, przesyłki) tracą obramowanie, zyskują jasnoszare tło. */
    .contact-feer [class*="border-gray"], .contact-feer [class*="border-2"] { border-color: transparent !important; }
    .contact-feer [class*="rounded"][class*="border"]:not(input):not(textarea):not(select):not(button) { background-color: #f9fafb; }
    .contact-feer input, .contact-feer textarea, .contact-feer select { border-color: #6b7280 !important; background-color: #fff; }

    /* Strona główna: jedna szerokość treści (lewa krawędź wspólna dla wszystkich sekcji), jednolite odstępy
       pionowe i naprzemienne tła — bez dwóch szarych sekcji obok siebie i bez kresek między modułami. */
    .max-w-\[1400px\] { max-width: 72rem; }
    section.py-14 { padding-top: 3rem; padding-bottom: 3rem; }
    section[aria-labelledby="ngo-projects-heading"] { background-color: #fff; }

    /* Delikatne ładnie-na-żądanie: obrazki ładowane z opóźnieniem pojawiają się płynnie (po załadowaniu), a karty poniżej
       pierwszego ekranu lekko wsuwają się przy przewijaniu. Klasy dodaje skrypt poniżej tylko tam, gdzie to potrzebne —
       bez JS albo przy „ogranicz ruch" wszystko jest widoczne od razu. */
    @media (prefers-reduced-motion: no-preference) {
        img.feer-img-pending { opacity: 0; }
        img.feer-img-ready { opacity: 1; transition: opacity .45s ease; }
        .feer-reveal { opacity: 0; transform: translateY(10px); }
        .feer-reveal.is-in { opacity: 1; transform: none; transition: opacity .5s ease, transform .5s ease; }
    }
</style>
<script>
    (function () {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
        document.addEventListener('DOMContentLoaded', function () {
            // 1) Obrazki z loading="lazy", które jeszcze się nie załadowały: ukryte do zdarzenia load/error.
            document.querySelectorAll('img[loading="lazy"]').forEach(function (img) {
                if (img.complete) { return; }
                img.classList.add('feer-img-pending');
                var done = function () { img.classList.remove('feer-img-pending'); img.classList.add('feer-img-ready'); };
                img.addEventListener('load', done, { once: true });
                img.addEventListener('error', done, { once: true });
            });
            // 2) Karty poniżej pierwszego ekranu: delikatne wsunięcie, a po animacji zdejmujemy klasy (wraca efekt unoszenia :hover).
            if (!('IntersectionObserver' in window)) { return; }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) { return; }
                    var el = e.target;
                    io.unobserve(el);
                    el.classList.add('is-in');
                    setTimeout(function () { el.classList.remove('feer-reveal', 'is-in'); }, 700);
                });
            }, { rootMargin: '0px 0px -8% 0px' });
            document.querySelectorAll('main .feer-card').forEach(function (el) {
                if (el.getBoundingClientRect().top > window.innerHeight) {
                    el.classList.add('feer-reveal');
                    io.observe(el);
                }
            });
        });
    })();
</script>
