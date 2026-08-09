<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteSettings->site_name)</title>

    <meta name="description" content="{{ trim($__env->yieldContent('meta_description', $siteSettings->meta_description)) }}">
    <meta name="robots" content="{{ $siteSettings->allow_indexing ? 'index, follow' : 'noindex, nofollow' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    @include('partials.analytics')
</head>
<body class="flex min-h-screen flex-col bg-white text-ink antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">
        Przejdź do treści
    </a>

    @if (! empty($preview))
        @include('partials.preview-bar')
    @endif

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    @include('partials.admin-bar')
    @stack('scripts')
</body>
</html>
