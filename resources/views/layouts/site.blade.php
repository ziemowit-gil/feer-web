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
          href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;600;700&family=Pacifico&family=Lato:wght@700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;600;700&family=Pacifico&family=Lato:wght@700&display=swap">
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
    @if (($siteSettings->site_template ?? 'default') === 'feer')
        @include('partials.theme-feer')
    @endif

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
    <style media="print">
        /* Wydruk: sama treść. Bez nagłówka, menu, stopki, paneli bocznych i przycisków; czarny tekst na białym tle; adresy linków w nawiasach. */
        @page { margin: 1.5cm; }
        header, footer, nav, aside, .no-print, .site-header, #page-tree, [x-data*="inlineContentEditor"] > .inline-edit-bar, .proj-nav, .proj-aside, .proj-menu-box, .proj-cta, .skip-link, button, video, iframe { display: none !important; }
        body { background: #fff !important; color: #000 !important; font-size: 12pt; }
        main, .proj-measure { max-width: none !important; }
        main a[href^="http"]:not(.no-url)::after { content: " (" attr(href) ")"; font-size: .85em; font-weight: 400; word-break: break-all; }
        main a { color: #000 !important; text-decoration: underline; }
        h1, h2, h3 { break-after: avoid; } li, blockquote, tr, details { break-inside: avoid; }
        details > *:not(summary) { display: block !important; }
        .proj-note, .proj-callout, .cx-frame, .dl-card, .proj-price-card, .proj-metrics li { border: 1px solid #000 !important; background: #fff !important; color: #000 !important; }
    </style>
    <script>
        /* Przed drukiem rozwijamy wszystkie sekcje <details> (akordeony), po druku przywracamy poprzedni stan. */
        (function () {
            var opened = [];
            window.addEventListener('beforeprint', function () { opened = []; document.querySelectorAll('details:not([open])').forEach(function (d) { d.open = true; opened.push(d); }); });
            window.addEventListener('afterprint', function () { opened.forEach(function (d) { d.open = false; }); opened = []; });
        })();
    </script>
</head>
<body @class(['flex min-h-screen flex-col bg-white text-ink antialiased', 'template-vm' => ($siteSettings->site_template ?? 'default') === 'vm'])>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">
        Przejdź do treści
    </a>

    @if (! empty($preview))
        @include('partials.preview-bar')
    @endif

    @php $siteTemplate = $siteSettings->site_template ?? 'default'; @endphp
    @hasSection('minimal_header')
        {{-- Uproszczony nagłówek (np. BIP): samo logo organizacji, bez górnego menu i paska. --}}
        @include('partials.header-minimal')
    @elseif ($siteTemplate === 'municipality')
        @include('templates.municipality.partials.topbar')
        @include('templates.municipality.partials.header')
    @elseif (in_array($siteTemplate, ['ngo', 'federacja', 'ngo_3']))
        <div x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
             x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()">
            @include('templates.ngo.partials.header')
            @include('templates.ngo.partials.topbar')
        </div>
    @elseif ($siteTemplate === 'federation')
        <div x-data="{ a11yOpen: (function () { try { return localStorage.getItem('federation-a11y-open') === '1' } catch (e) { return false } })() }"
             x-effect="(() => { try { localStorage.setItem('federation-a11y-open', a11yOpen ? '1' : '0') } catch (e) {} })()">
            @include('templates.federation.partials.topbar')
            @include('templates.federation.partials.header')
        </div>
    @elseif ($siteTemplate === 'wrzos')
        @include('templates.wrzos.partials.topbar')
        @include('templates.wrzos.partials.header')
    @elseif ($siteTemplate === 'vm')
        {{-- Jeden landmark „banner": czarny pasek (KRS, dostępność, kontakt, szukajka) + belka z logo i menu. --}}
        <header class="relative z-30" x-data="siteMobileNav()" @keydown.escape="closeMenu()">
            @include('templates.vm.partials.topbar')
            @include('templates.vm.partials.header')
        </header>
    @else
        {{-- Jeden landmark „banner": pasek górny (ułatwienia, szukajka, BIP, social) + belka z logo i menu. --}}
        <header class="site-header relative z-30">
            @include($siteSettings->headerLayoutValue() === 'office_bar' ? 'partials.topbar-info' : 'partials.topbar')
            @include('partials.header')
        </header>
    @endif

    <main id="main-content" class="flex-1">
        @hasSection('breadcrumbs')
            @yield('breadcrumbs')
        @endif

        @include('partials.feer-bands-sitewide', ['where' => 'site_top'])

        @yield('content')

        @include('partials.feer-bands-sitewide', ['where' => 'site_bottom'])
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
                Chcesz dostawać powiadomienia o szkoleniach i aktualnościach {{ $siteSettings->siteNameGenitive() }}?
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
