{{--
    503 — krótka przerwa przy wgrywaniu nowej wersji portalu (deploy.sh: artisan down --render="errors::503").
    Strona jest samodzielna: bez bazy danych, bez Vite i bez zewnętrznych czcionek/ikon — musi działać, gdy
    aplikacja jest w trakcie aktualizacji (widok renderuje się raz, przy `artisan down`).
    Wygląd jak pozostałe strony serwisowe („Przerwa techniczna”, „Witryna w budowie”): wyśrodkowane okienko
    z okrągłą ikoną na ilustrowanym tle (errors/partials/construction-backdrop — statyczny SVG i CSS, bez zasobów z zewnątrz).
    Kolory marki są stałe (kontrast ≥ 7:1), nie z palety witryny: strona nie może zależeć od ustawień.
    Sama odświeża się po 15 s (meta refresh + odliczanie). Pasek postępu animuje CSS, a przy ograniczonym ruchu
    przesuwa się co sekundę razem z odliczaniem (bez animacji, ale zgodnie z napisem). Komunikat w regionie role="status".
--}}
@php
    $siteName = (function () {
        try { return \App\Models\SiteSetting::current()->site_name ?: config('app.name'); } catch (\Throwable $e) { return config('app.name'); }
    })();
    $retry = 15;
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="{{ $retry }}">
    <title>Wgrywamy nową wersję — {{ $siteName }}</title>
    <style>
        :root { --brand: #1752bf; --brand-dark: #0f3d99; --brand-light: #e8f0ff; --ink: #1d1d1a; --muted: #4b5563; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; color: var(--ink);
               background: linear-gradient(135deg, var(--brand-light), #fff);
               font-style: normal; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.6; }
        main { width: 100%; max-width: 34rem; padding: 2.5rem 2rem; text-align: center; background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem;
               box-shadow: 0 10px 40px rgba(0, 0, 0, .06); }
        .icon { display: inline-flex; width: 4.5rem; height: 4.5rem; align-items: center; justify-content: center; border-radius: 9999px; background: var(--brand-light); color: var(--brand); }
        .icon svg { width: 2rem; height: 2rem; animation: spin 2.4s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .kicker { margin: 1.5rem 0 0; font-size: .75rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: .35rem 0 0; font-size: 1.75rem; line-height: 1.25; font-weight: 800; }
        .bar { display: block; width: 3.5rem; height: .25rem; margin: .75rem auto 0; background: #1e6dff; }
        p { max-width: 26rem; margin: 1.25rem auto 0; font-size: 1.0625rem; }
        .progress { margin-top: 1.75rem; height: .5rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
        .progress span { display: block; height: 100%; width: 0; background: var(--brand); animation: fill {{ $retry }}s linear forwards; }
        @keyframes fill { to { width: 100%; } }
        .meta { margin-top: .75rem; font-size: .9375rem; color: var(--muted); }
        a.btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-top: 1.5rem; padding: 0 1.25rem; border-radius: .375rem;
                background: var(--brand); color: #fff; font-weight: 700; text-decoration: none; }
        a.btn:hover { background: var(--ink); }
        a.btn:focus-visible { outline: 3px solid var(--ink); outline-offset: 3px; }
        @media (prefers-reduced-motion: reduce) { .icon svg, .progress span { animation: none; } }
        @media (max-width: 30rem) { main { padding: 2rem 1.25rem; } h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>
    @include('errors.partials.construction-backdrop', ['backdropLabel' => 'wgrywamy zmiany'])
    <main>
        <span class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
        </span>
        <p class="kicker">{{ $siteName }}</p>
        <h1>Wgrywamy nową wersję portalu</h1>
        <span class="bar" aria-hidden="true"></span>
        <div role="status">
            <p>Za kilkanaście sekund wszystko będzie działać. Strona odświeży się sama. Nie musisz nic robić.</p>
        </div>
        <div class="progress" aria-hidden="true"><span></span></div>
        <p class="meta" aria-hidden="true">Odświeżenie za <strong id="countdown">{{ $retry }}</strong> s</p>
        <a class="btn" href="">Odśwież teraz</a>
    </main>
    <script>
        (function () {
            var total = {{ $retry }}, left = total, el = document.getElementById('countdown'), bar = document.querySelector('.progress span');
            var t = setInterval(function () {
                left -= 1;
                if (el) el.textContent = Math.max(left, 0);
                // Przy ograniczonym ruchu pasek nie jest animowany CSS-em — przesuwamy go co sekundę, żeby zgadzał się z odliczaniem.
                // (Przy animacji CSS ta wartość jest ignorowana: animacja ma pierwszeństwo przed stylem z atrybutu.)
                if (bar) bar.style.width = Math.min(100, (total - Math.max(left, 0)) / total * 100) + '%';
                if (left <= 0) { clearInterval(t); location.reload(); }
            }, 1000);
        })();
    </script>
</body>
</html>
