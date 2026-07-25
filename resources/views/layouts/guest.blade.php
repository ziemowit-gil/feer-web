<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif{{ $siteSettings->site_name }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gray-50 px-4 py-10 text-ink antialiased">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-brand/10 blur-3xl"></div>
        <div class="absolute -right-32 -bottom-32 h-96 w-96 rounded-full bg-brand/10 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,theme(colors.gray.300)_1px,transparent_0)] bg-[length:24px_24px] opacity-[0.15]"></div>
    </div>

    <div class="relative w-full max-w-sm">
        <a href="{{ route('home') }}" class="mb-6 flex flex-col items-center gap-2 text-center">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name }}" class="h-14 w-14 rounded-xl object-contain shadow-sm ring-1 ring-gray-900/5">
            @else
                <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-brand to-brand-dark text-xl font-bold text-white shadow-sm">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="font-bold text-ink">{{ $siteSettings->site_name }}</span>
        </a>

        <div class="rounded-2xl border border-gray-200/70 bg-white/90 p-8 shadow-xl shadow-gray-900/5 backdrop-blur-sm">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-sm text-muted">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 transition hover:text-brand">
                <i class="fa-solid fa-arrow-left"></i> Wróć do strony głównej
            </a>
        </p>
    </div>
</body>
</html>
