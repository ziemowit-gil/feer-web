{{-- Motyw szablonu „FEER" wg Brand booka FEER 2024: Montserrat w całym serwisie, tekst #1D1D1A,
     płaskie powierzchnie (bez gradientów i cieni), ostre, niewielkie zaokrąglenia oraz paleta brandbooka
     (#1E6DFF, #1D1D1A, #CBD5E7, partnerstwa NGO: #EA8F00) nadpisująca kolory z Ustawień → Kolory.
     Dotyczy wszystkich widoków tego szablonu, także listy i strony projektu. --}}
<style>
    :root {
        /* Paleta Brand booka FEER 2024 (FEER jako osobny podmiot) — stała w tym szablonie. */
        --color-brand: #1e6dff;
        --color-brand-dark: #1457cc;
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
</style>
