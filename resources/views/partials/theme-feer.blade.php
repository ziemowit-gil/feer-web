{{-- Motyw szablonu „FEER" wg Brand booka FEER 2024: Montserrat w całym serwisie, tekst #1D1D1A,
     płaskie powierzchnie (bez gradientów i cieni), ostre, niewielkie zaokrąglenia oraz paleta brandbooka
     (#1E6DFF, #1D1D1A, #CBD5E7, partnerstwa NGO: #EA8F00) nadpisująca kolory z Ustawień → Kolory.
     Dotyczy wszystkich widoków tego szablonu, także listy i strony projektu. --}}
<style>
    :root {
        /* Paleta Brand booka FEER 2024 (FEER jako osobny podmiot) — stała w tym szablonie. */
        /* Kolor główny #1E6DFF (decyzja właściciela; brandbook). Biały na nim ma 4,48:1 — o włos poniżej AA 4,5:1, dlatego tekst i linki na jasnym tle używają ciemniejszego wariantu brand-dark (6,5:1). */
        --color-brand: #1e6dff;
        --color-brand-dark: #1e6dff;
        --color-brand-light: #e8f0ff;
        --color-brand-2: #ea8f00;   /* działania w partnerstwie z NGO */
        --color-brand-3: #1d1d1a;
        --color-brand-4: #cbd5e7;
        --font-sans: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        --color-ink: #1d1d1a;
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
    .about-feer #sekcja-stats { background-color: var(--color-brand-light); }
    section[aria-labelledby="stats-heading"], section[aria-labelledby="methods-heading"] { background-color: var(--color-brand-light); }

    /* Delikatny efekt tła po bokach (szerokie ekrany): dwie bardzo miękkie, niebieskie poświaty przy lewej i prawej krawędzi.
       Czysto dekoracyjne — leżą za treścią (z-index -1), nie przechwytują kliknięć, nie zmieniają kontrastu tekstu na
       sekcjach z własnym tłem; wyłączone w trybie wymuszonych kolorów i przy wydruku. */
    @media (min-width: 1100px) {
        body::before {
            content: ""; position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background:
                radial-gradient(34rem 34rem at -6% 18%, color-mix(in srgb, var(--color-brand) 10%, transparent), transparent 70%),
                radial-gradient(30rem 30rem at 106% 62%, color-mix(in srgb, var(--color-brand) 8%, transparent), transparent 70%),
                radial-gradient(24rem 24rem at -4% 92%, color-mix(in srgb, var(--color-brand) 6%, transparent), transparent 70%);
        }
    }
    @media (forced-colors: active), print { body::before { display: none; } }

    /* Poziome rzędy przycisków-kategorii (na telefonie przewijane palcem): bez widocznego paska przewijania. */
    .feer-pills-row { scrollbar-width: none; -ms-overflow-style: none; }
    .feer-pills-row::-webkit-scrollbar { display: none; }

    /* Kolory linków: tylko niebieski i czarny (brandbook). Linki w treści — niebieskie z podkreśleniem, po najechaniu czarne;
       pozycje menu — czarne, po najechaniu/aktywne niebieskie (#1E6DFF na bieli 4,48:1, #1D1D1A 16,9:1). */
    .prose a:not([class*="bg-"]) { color: #1e6dff; text-decoration: underline; text-underline-offset: .2em; }
    .prose a:not([class*="bg-"]):hover { color: #1d1d1a; }
    nav .nav-pills > li:not([data-nav-accent]) > a:hover, nav .nav-pills > li:not([data-nav-accent]) > button:hover,
    nav .nav-pills li > ul[role="list"] a:hover { color: #1e6dff; }

    /* Tytuły stron (H1 „hero”): spokojniejsze, bez wielkiego szarego pasa — rozmiar jak nagłówki sekcji, niebieski akcent pod spodem,
       białe tło i mniejsze odstępy. Jedna reguła dla wszystkich podstron FEER (okruszki płynnie przechodzą w tytuł). */
    h1[class*="md:text-5xl"][class*="text-ink"] { font-size: 1.875rem; line-height: 1.2; font-weight: 700; letter-spacing: 0; }
    h1[class*="md:text-5xl"][class*="text-ink"]::after { content: ""; display: block; width: 3.5rem; height: 4px; margin-top: .75rem; background: var(--color-brand); }
    :is(section, header).bg-gray-50:has(h1[class*="md:text-5xl"][class*="text-ink"]), :is(section, header).bg-white:has(h1[class*="md:text-5xl"][class*="text-ink"]) { background-color: transparent; border-bottom-width: 0; }
    div:has(> h1[class*="md:text-5xl"][class*="text-ink"]) { padding-top: 2rem; padding-bottom: 1.5rem; }

    /* Delikatne animacje (≤ 0,5 s, bez przesuwania układu): płynne pojawienie się treści, wydłużanie niebieskiego akcentu
       pod tytułem, lekkie uniesienie przycisków i płynna zmiana koloru linków menu. Wyłączone przy „ogranicz ruch". */
    @media (prefers-reduced-motion: no-preference) {
        @keyframes feer-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes feer-bar-grow { from { width: 0; } to { width: 3.5rem; } }
        main { animation: feer-fade-in .4s ease-out both; }
        nav[aria-label="Ścieżka nawigacyjna"] { animation: feer-fade-in .5s ease-out both; }
        h1[class*="md:text-5xl"][class*="text-ink"]::after { animation: feer-bar-grow .6s .15s ease-out both; }
        .site-header nav a, .site-header nav button { transition: color .2s ease, background-color .2s ease; }
        a.bg-brand, a.bg-ink, button.bg-brand { transition: transform .2s ease, background-color .2s ease; }
        a.bg-brand:hover, a.bg-ink:hover, button.bg-brand:hover { transform: translateY(-1px); }
        a.bg-brand:active, a.bg-ink:active, button.bg-brand:active { transform: none; }
    }

    /* Większe odstępy między pozycjami menu (układ z podkreśleniem) i pigułkami. */
    @media (min-width: 1024px) {
        .site-header nav > ul.flex:not(.nav-pills):not(.nav-icons),
        .site-header nav > div > ul.flex:not(.nav-pills):not(.nav-icons) { gap: 2.25rem; }
        .site-header nav > ul.nav-pills, .site-header nav > div > ul.nav-pills { gap: .5rem; }
    }

    /* Menu główne i podmenu: większe pozycje (czytelność i cele dotyku ≥ 44 px). */
    nav .nav-pills > li > a, nav .nav-pills > li > button, nav .nav-pills > li > div.border-b-2 { font-size: 1.3125rem; padding: .7rem 1.35rem; }
    nav .nav-pills > li > div.border-b-2 > a, nav .nav-pills > li > div.border-b-2 > button { font-size: 1.3125rem; }
    nav .nav-pills li > ul[role="list"] { min-width: 19rem; padding-top: .5rem; padding-bottom: .5rem; }
    nav .nav-pills li > ul[role="list"] a, nav .nav-pills li > ul[role="list"] button { font-size: 1.25rem; line-height: 1.35; padding: .8rem 1.35rem; min-height: 3rem; display: flex; align-items: center; }
    .nav-mega-panel a { font-size: 1.25rem; }
    /* Menu w układzie z podkreśleniem (classic): pozycje główne o 3 px większe (18 → 21 px). */
    .site-header nav > ul:not(.nav-pills) > li > a, .site-header nav > ul:not(.nav-pills) > li > button,
    .site-header nav > ul:not(.nav-pills) > li > div > a, .site-header nav > ul:not(.nav-pills) > li > div > button,
    .site-header nav > div > ul:not(.nav-pills) > li > a, .site-header nav > div > ul:not(.nav-pills) > li > button,
    .site-header nav > div > ul:not(.nav-pills) > li > div > a, .site-header nav > div > ul:not(.nav-pills) > li > div > button { font-size: 1.3125rem; }

    /* Pasek górny (dostępność, konto, wyszukiwarka, BIP, social): jeden smukły, ciemny rząd — biały tekst na #1D1D1A (16,9:1). */
    .site-topbar-feer > div:first-child { padding-top: .125rem; padding-bottom: .125rem; }
    .site-topbar-feer > div:first-child a,
    .site-topbar-feer > div:first-child > button { color: #fff !important; }
    .site-topbar-feer > div:first-child > button { border-color: #fff !important; background: transparent !important; border-radius: .375rem !important; }
    .site-topbar-feer > div:first-child > button:hover,
    .site-topbar-feer > div:first-child > button[aria-expanded="true"] { background: #fff !important; color: #1d1d1a !important; }
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
</style>
