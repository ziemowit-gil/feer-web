<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Edytor blokowy: {{ $editorConfig['page']['title'] }} — {{ $siteSettings->site_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;600;700&display=swap">
    @php
        // Po wdrożeniu bez `npm run build` manifest nie zna nowych plików — zamiast błędu 500 ładujemy to, co jest, i pokazujemy komunikat.
        $beEntries = ['resources/css/app.css', 'resources/css/block-editor.css', 'resources/js/block-editor.js'];
        $beMissing = \App\Support\ViteSafe::missing($beEntries);
    @endphp
    @vite(array_values(array_diff($beEntries, $beMissing)))
    @php $brandPalette = $siteSettings->brandPalette(); @endphp
    <style>
        :root {
            --color-brand: {{ $brandPalette['color'] }};
            --color-brand-dark: {{ $brandPalette['dark'] }};
            --color-brand-light: {{ $brandPalette['light'] }};
            --color-brand-2: {{ $siteSettings->brandColorN(2) }};
            --color-brand-3: {{ $siteSettings->brandColorN(3) }};
            --color-brand-4: {{ $siteSettings->brandColorN(4) }};
        }
    </style>
    @if (($siteSettings->site_template ?? 'default') === 'feer')
        @include('partials.theme-feer')
    @endif
</head>
<body class="be-body">
    @if ($beMissing)
        <div role="alert" style="margin:1rem;padding:1rem 1.25rem;border:2px solid #991b1b;border-radius:.375rem;background:#fef2f2;color:#1d1d1a;font:600 15px/1.5 system-ui,sans-serif">
            Edytor blokowy nie może się uruchomić: na serwerze brakuje zbudowanych plików ({{ implode(', ', array_map('basename', $beMissing)) }}).
            Administrator powinien uruchomić wdrożenie (<code>./deploy.sh</code>) albo <code>npm ci &amp;&amp; npm run build</code>.
            <a href="{{ $editorConfig['urls']['admin'] }}" style="color:#1d1d1a;text-decoration:underline">Wróć do zwykłego edytora</a>.
        </div>
    @endif
    <noscript>
        <p class="be-noscript">Edytor blokowy wymaga JavaScriptu. <a href="{{ $editorConfig['urls']['admin'] }}">{{ $editorConfig['labels']['noscript'] }}</a>.</p>
    </noscript>
    <div id="block-editor" data-config='@json($editorConfig, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
    {{-- Kreatory z klasycznego edytora (zestawy kafelków, bloki CTA/akordeon/ramka/cennik) — window.TilesBuilder / window.BlockBuilder. --}}
    @include('admin.partials.tiles-builder')
    @include('admin.partials.content-block-builder')
</body>
</html>
