<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif{{ $siteSettings->site_name }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Pacifico&family=Lato:wght@700&display=swap">
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
        {{-- ===================== Panel brandowy (lewa strona) ===================== --}}
        <aside class="relative flex flex-col justify-between overflow-hidden bg-gradient-to-br from-brand to-brand-dark px-8 py-10 text-white lg:w-1/2 lg:px-16 lg:py-16">
            {{-- Delikatny wzór w tle (dekoracyjny) --}}
            <div class="pointer-events-none absolute inset-0 opacity-20" aria-hidden="true">
                <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.35)_1px,transparent_0)] bg-[length:26px_26px]"></div>
            </div>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-12 w-12 rounded-xl bg-white/95 object-contain p-1 shadow-sm">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/95 text-xl font-bold text-brand shadow-sm">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                    @endif
                    <span class="text-lg font-bold">{{ $siteSettings->site_name }}</span>
                </a>
            </div>

            <div class="relative hidden lg:block">
                <p class="mb-2 text-4xl leading-none">
                    <span style="font-family:'Pacifico',cursive">We</span><span style="font-family:'Lato',sans-serif;font-weight:700">CMS</span>
                </p>
                <p class="mb-6 text-sm text-white/60">Autorski CMS dla NGO</p>
                <h2 class="max-w-md text-3xl font-bold leading-tight">@yield('brand_heading', 'Witaj ponownie')</h2>
                <p class="mt-4 max-w-md text-white/80">@yield('brand_lead', 'Zaloguj się, aby kontynuować.')</p>
            </div>

            <div class="relative hidden text-sm text-white/70 lg:block">
                © {{ date('Y') }} {{ $siteSettings->site_name }}
            </div>
        </aside>

        {{-- ===================== Formularz (prawa strona) ===================== --}}
        <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                {{-- Logo widoczne na mobile (panel brandowy jest tam skrócony) --}}
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-2 lg:hidden">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-10 w-10 rounded-lg object-contain ring-1 ring-gray-900/5">
                    @endif
                    <span class="font-bold text-ink">{{ $siteSettings->site_name }}</span>
                </a>

                {{ $slot }}

                <p class="mt-8 text-center text-sm text-muted">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 transition hover:text-brand">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do strony głównej
                    </a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
