<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Edytor blokowy: {{ $page->title }} — {{ $siteSettings->site_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/css/block-editor.css', 'resources/js/block-editor.js'])
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
    <noscript>
        <p class="be-noscript">Edytor blokowy wymaga JavaScriptu. <a href="{{ route('admin.podstrony.edit', $page) }}">Przejdź do klasycznego formularza strony</a>.</p>
    </noscript>
    <div id="block-editor" data-config='@json($editorConfig, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
</body>
</html>
