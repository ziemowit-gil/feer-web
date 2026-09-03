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
    @if ($siteSettings->isModuleEnabled('news'))
        <link rel="alternate" type="application/rss+xml" title="{{ $siteSettings->site_name }} — Aktualności" href="{{ route('feed') }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteSettings->site_name }}">
    <meta property="og:title" content="{{ trim($__env->yieldContent('title', $siteSettings->site_name)) }}">
    <meta property="og:description" content="{{ trim($__env->yieldContent('meta_description', $siteSettings->meta_description)) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage = $__env->yieldContent('og_image', $siteSettings->ogImageUrl()))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Czcionki Google Fonts: ładowane nieblokująco (preload + onload swap). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;700&family=Pacifico&family=Lato:wght@700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;700&family=Pacifico&family=Lato:wght@700&display=swap">
    </noscript>
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

    {{-- Dane strukturalne: organizacja (globalnie) + slot na typ strony (Article/Event) --}}
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteSettings->site_name,
            'url' => url('/'),
            'logo' => $siteSettings->logoUrl(),
            'email' => $siteSettings->contact_email,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('structured_data')
    @include('partials.analytics')
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="{{ $brandPalette['color'] }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <link rel="apple-touch-icon" href="/img/pwa-icon-192.png">
</head>
<body class="flex min-h-screen flex-col bg-white text-ink antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">
        Przejdź do treści
    </a>

    @if (! empty($preview))
        @include('partials.preview-bar')
    @endif

    @php $siteTemplate = $siteSettings->site_template ?? 'default'; @endphp
    @if ($siteTemplate === 'municipality')
        @include('templates.municipality.partials.topbar')
        @include('templates.municipality.partials.header')
    @elseif (in_array($siteTemplate, ['ngo', 'federacja']))
        @include('templates.ngo.partials.topbar')
        @include('templates.ngo.partials.header')
    @elseif ($siteTemplate === 'federation')
        <div x-data="{ a11yOpen: (function () { try { return localStorage.getItem('federation-a11y-open') === '1' } catch (e) { return false } })() }"
             x-effect="(() => { try { localStorage.setItem('federation-a11y-open', a11yOpen ? '1' : '0') } catch (e) {} })()">
            @include('templates.federation.partials.topbar')
            @include('templates.federation.partials.header')
        </div>
    @elseif ($siteTemplate === 'wrzos')
        @include('templates.wrzos.partials.topbar')
        @include('templates.wrzos.partials.header')
    @else
        @include($siteSettings->headerLayoutValue() === 'office_bar' ? 'partials.topbar-info' : 'partials.topbar')
        @include('partials.header')
    @endif

    <main id="main-content" class="flex-1">
        @hasSection('breadcrumbs')
            @yield('breadcrumbs')
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.lightbox')
    @include('partials.cookie-banner')
    @include('partials.admin-bar')

    {{-- Baner zgody na powiadomienia push (ukryty domyślnie, pokazywany przez JS). --}}
    <div id="push-prompt"
         class="fixed bottom-4 left-4 right-4 z-50 mx-auto flex max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-lg ring-1 ring-gray-200 hidden"
         role="region"
         aria-live="polite"
         aria-label="Powiadomienia push">
        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
            <i class="fa-solid fa-bell"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="mb-3 text-sm font-medium text-gray-800">
                Chcesz dostawać powiadomienia o szkoleniach i aktualnościach {{ $siteSettings->site_name }}?
            </p>
            <div class="flex gap-2">
                <button id="push-subscribe-btn"
                        class="rounded-lg bg-brand px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Włącz
                </button>
                <button onclick="document.getElementById('push-prompt').remove();localStorage.setItem('push-dismissed','1')"
                        class="rounded-lg px-4 py-1.5 text-sm text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Nie teraz
                </button>
            </div>
        </div>
    </div>
    <script>
        if ('serviceWorker' in navigator && 'PushManager' in window
            && !localStorage.getItem('push-subscribed')
            && !localStorage.getItem('push-dismissed')) {
            document.getElementById('push-prompt')?.classList.remove('hidden');
        }
    </script>
    <script>
        (function () {
            function checkAdminReload() {
                try {
                    var d = JSON.parse(localStorage.getItem('feer_reload') || 'null');
                    if (d && d.url === window.location.href && (Date.now() - d.at) < 600000) {
                        localStorage.removeItem('feer_reload');
                        window.location.reload();
                    }
                } catch (e) {}
            }
            document.addEventListener('visibilitychange', function () { if (!document.hidden) checkAdminReload(); });
            window.addEventListener('focus', checkAdminReload);
        })();
    </script>
</body>
</html>
