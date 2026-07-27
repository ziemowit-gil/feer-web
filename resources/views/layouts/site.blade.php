<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $siteSettings->site_name)</title>

    <meta name="description" content="{{ trim($__env->yieldContent('meta_description', $siteSettings->meta_description)) }}">
    <meta name="robots" content="{{ $siteSettings->allow_indexing ? 'index, follow' : 'noindex, nofollow' }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteSettings->site_name }}">
    <meta property="og:title" content="{{ trim($__env->yieldContent('title', $siteSettings->site_name)) }}">
    <meta property="og:description" content="{{ trim($__env->yieldContent('meta_description', $siteSettings->meta_description)) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage = $__env->yieldContent('og_image', $siteSettings->ogImageUrl()))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php $brandPalette = $siteSettings->brandPalette($brandColor ?? null); @endphp
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
</head>
<body class="flex min-h-screen flex-col bg-white text-ink antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">
        Przejdź do treści
    </a>

    @include('partials.topbar')
    @include('partials.header')

    <main id="main-content" class="flex-1">
        @hasSection('breadcrumbs')
            @yield('breadcrumbs')
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.lightbox')
</body>
</html>
