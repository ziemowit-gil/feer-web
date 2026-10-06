{{-- Motyw szablonu „FEER" wg Brand booka FEER 2024: Montserrat w całym serwisie, tekst #1D1D1A,
     płaskie powierzchnie (bez gradientów i cieni), ostre, niewielkie zaokrąglenia oraz paleta brandbooka
     (#1E6DFF, #1D1D1A, #CBD5E7, partnerstwa NGO: #EA8F00) nadpisująca kolory z Ustawień → Kolory.
     Dotyczy wszystkich widoków tego szablonu, także listy i strony projektu. --}}
<style>
    :root {
        /* Paleta Brand booka FEER 2024 (FEER jako osobny podmiot) — stała w tym szablonie. */
        /* #1E6DFF z brandbooka ma na bieli 4,48:1 (poniżej AA); #1B66F5 to najbliższy odcień ≥ 4,5:1 (4,9:1). */
        --color-brand: #1b66f5;
        --color-brand-dark: #1456cc;
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
    .shadow, .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl { box-shadow: none !important; }
    /* Mniej kółek i dużych zaokrągleń: karty i przyciski-linki. */
    .rounded-xl, .rounded-2xl, .rounded-3xl { border-radius: .5rem !important; }
    a.rounded-full { border-radius: .375rem !important; }

    /* Strona główna: jedna szerokość treści (lewa krawędź wspólna dla wszystkich sekcji), jednolite odstępy
       pionowe i naprzemienne tła — bez dwóch szarych sekcji obok siebie. */
    .max-w-\[1400px\] { max-width: 72rem; }
    section.py-14 { padding-top: 3rem; padding-bottom: 3rem; }
    section[aria-labelledby="ngo-projects-heading"] { background-color: #fff; border-top: 1px solid #f3f4f6; }
    section[aria-labelledby="ngo-news-heading"] { border-top: 1px solid #f3f4f6; }
</style>
