<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif{{ $siteSettings->site_name }}</title>

    @php
        $faviconColor = $siteSettings->brandPalette()['color'];
        $faviconSvg   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="7" fill="' . e($faviconColor) . '"/><text x="16" y="24" text-anchor="middle" font-family="serif" font-size="22" font-weight="bold" fill="white">W</text></svg>';
    @endphp
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,{{ base64_encode($faviconSvg) }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;600;700&family=Pacifico&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php $brandPalette = $siteSettings->brandPalette(); @endphp
    <style>
        :root {
            --color-brand:       {{ $brandPalette['color'] }};
            --color-brand-dark:  {{ $brandPalette['dark'] }};
            --color-brand-light: {{ $brandPalette['light'] }};
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 text-ink antialiased">
    {{-- Logowanie i strony konta: jedna wyśrodkowana karta na jasnym tle, nad nią marka, pod nią powrót i stopka.
         Płasko, w kolorach firmowych; nagłówek strony (h1) jest w treści karty. --}}
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:py-16">
        <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-4 focus-visible:ring-offset-gray-100">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-12 w-12 rounded-md bg-white object-contain p-1 shadow-sm">
            @else
                <span class="flex h-12 w-12 items-center justify-center rounded-md bg-brand-dark text-xl font-bold text-white" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="max-w-xs text-lg font-bold leading-snug text-ink">{{ $siteSettings->site_name }}</span>
        </a>

        <main class="w-full max-w-md rounded-md bg-white px-6 py-8 shadow-sm sm:px-10 sm:py-10">
            {{ $slot }}
        </main>

        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do strony głównej
        </a>
        <p class="mt-8 text-xs text-muted">
            © {{ date('Y') }} {{ $siteSettings->site_name }} · <span style="font-family:'Pacifico',cursive">We</span>CMS
        </p>
    </div>
</body>
</html>
