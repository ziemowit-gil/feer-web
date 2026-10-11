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
<body class="min-h-screen bg-white text-ink antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- ===================== Panel marki (lewa strona) =====================
             Płasko: jednolite tło w kolorze firmowym, bez gradientów i ozdobników.
             Biały tekst na brand-dark ma kontrast ≥ 6:1. --}}
        <aside class="flex flex-col justify-between bg-brand-dark px-8 py-10 text-white lg:w-1/2 lg:px-16 lg:py-16">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-12 w-12 rounded-md bg-white object-contain p-1">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-md bg-white text-xl font-bold text-brand-dark" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                    @endif
                    <span class="text-lg font-bold">{{ $siteSettings->site_name }}</span>
                </a>
            </div>

            <div class="hidden lg:block">
                <span class="mb-6 block h-1 w-14 bg-white" aria-hidden="true"></span>
                <p class="max-w-md text-3xl font-bold leading-tight">@yield('brand_heading', 'Witaj ponownie')</p>
                <p class="mt-4 max-w-md text-lg leading-relaxed text-white">@yield('brand_lead', 'Zaloguj się, aby kontynuować.')</p>
            </div>

            <p class="hidden text-sm text-white lg:block">
                © {{ date('Y') }} {{ $siteSettings->site_name }} · <span style="font-family:'Pacifico',cursive">We</span>CMS
            </p>
        </aside>

        {{-- ===================== Formularz (prawa strona) ===================== --}}
        <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                {{-- Logo widoczne na mobile (panel brandowy jest tam skrócony) --}}

                {{ $slot }}

                <p class="mt-8 text-center text-sm">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do strony głównej
                    </a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
