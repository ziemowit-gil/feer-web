<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel administracyjny') — {{ $siteSettings->site_name }}</title>
    @php
        $faviconColor = $siteSettings->brandPalette()['color'];
        $faviconSvg   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="7" fill="' . e($faviconColor) . '"/><text x="16" y="24" text-anchor="middle" font-family="serif" font-size="22" font-weight="bold" fill="white">W</text></svg>';
    @endphp
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,{{ base64_encode($faviconSvg) }}">
    {{-- Font Awesome: self-hosted via npm (bundled in app.css). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Pacifico&family=Lato:wght@700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Pacifico&family=Lato:wght@700&display=swap">
    </noscript>
    <script>
        // Stan menu bocznego panelu (patrz admin/partials/sidebar.blade.php).
        document.addEventListener('alpine:init', () => {
            Alpine.store('adminNav', {
                collapsed: localStorage.getItem('admin-sidebar') === '1',
                mobileOpen: false,
                searching: false,
                toggleCollapsed() {
                    this.collapsed = ! this.collapsed;
                    localStorage.setItem('admin-sidebar', this.collapsed ? '1' : '0');
                },
                // Grupy menu wysuwane w prawo: na szerokim ekranie (≥ 1024 px) zawsze, w zwężonej szynie też; na telefonie akordeon.
                flyout() { return this.collapsed || window.matchMedia('(min-width: 1024px)').matches; },
                open()  { this.mobileOpen = true;  setTimeout(() => document.querySelector('#admin-sidebar [aria-label="Zamknij menu"]')?.focus(), 50); },
                close() { if (this.mobileOpen) { this.mobileOpen = false; document.querySelector('[aria-controls="admin-sidebar"]')?.focus(); } },
            });

            // Akordeon grup głównych menu: otwarta jest jedna grupa naraz. Grupa z aktywną pozycją otwiera się od razu;
            // bez niej wraca ostatni wybór z localStorage. W zwężonej szynie kliknięcie grupy rozwija całe menu.
            Alpine.data('navAccordion', (activeKey, firstKey) => ({
                openKey: (() => {
                    if (activeKey) return activeKey;
                    try { return localStorage.getItem('admin-nav-open') ?? firstKey; } catch (e) { return firstKey; }
                })(),
                toggle(key) {
                    const nav = Alpine.store('adminNav');
                    if (nav.collapsed) {
                        nav.toggleCollapsed();
                        this.openKey = key;
                    } else {
                        this.openKey = this.openKey === key ? '' : key;
                    }
                    try { localStorage.setItem('admin-nav-open', this.openKey); } catch (e) {}
                },
            }));

            // Sekcja menu: zapamiętuje zwinięcie w localStorage; sekcja z aktywną pozycją jest zawsze otwarta.
            Alpine.data('navSection', (key, defaultOpen, active) => ({
                open: active || (localStorage.getItem('admin-nav:' + key) ?? (defaultOpen ? '1' : '0')) === '1',
                toggle() {
                    this.open = ! this.open;
                    localStorage.setItem('admin-nav:' + key, this.open ? '1' : '0');
                },
            }));

            // Grupa menu: panel wysuwany w prawo (najechanie, fokus, kliknięcie; Esc zamyka) — przy krawędzi menu bocznego.
            Alpine.data('navItem', (initiallyOpen) => ({
                open: initiallyOpen,
                fly: false,
                flyTop: 0,
                flyLeft: 0,
                flyIn(el) {
                    if (! Alpine.store('adminNav').flyout()) return;
                    const r = el.getBoundingClientRect();
                    const side = el.closest('.admin-sidebar')?.getBoundingClientRect();
                    this.flyTop = Math.max(8, Math.min(r.top, window.innerHeight - 320));
                    this.flyLeft = side ? side.right : r.right;
                    this.fly = true;
                },
                clickHead(el, key, toggle) {
                    // Zwężona szyna też wysuwa panel — kliknięcie nagłówka zamyka go (wcześniej przełączało ukryty akordeon i panel zostawał).
                    if (! Alpine.store('adminNav').flyout()) return toggle(key);
                    this.fly ? (this.fly = false) : this.flyIn(el);
                },
                flyOut(e) {
                    if (e && e.relatedTarget && e.currentTarget.contains(e.relatedTarget)) return;
                    this.fly = false;
                },
            }));
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $brandPalette = $siteSettings->brandPalette();
        $pickerBrandColors = collect([
            ['hex' => $brandPalette['color'], 'label' => 'Kolor marki'],
            ['hex' => $brandPalette['dark'], 'label' => 'Kolor marki (ciemny)'],
            ['hex' => $siteSettings->brand_color_2, 'label' => 'Kolor marki 2'],
            ['hex' => $siteSettings->brand_color_3, 'label' => 'Kolor marki 3'],
            ['hex' => $siteSettings->brand_color_4, 'label' => 'Kolor marki 4'],
            ['hex' => $brandPalette['light'], 'label' => 'Kolor marki (jasne tło)'],
        ])->filter(fn ($c) => \App\Support\Color::isValid($c['hex']))->unique('hex')->values();
    @endphp
    {{-- Bez animacji menu do pierwszego namalowania strony (klasa admin-ready dodawana na końcu <body>). --}}
    <style>html:not(.admin-ready) .admin-sidebar, html:not(.admin-ready) .admin-sidebar * { transition: none !important; animation: none !important; }</style>
    <meta name="admin-brand-colors" content="{{ $pickerBrandColors->toJson() }}">
    <meta name="admin-icons-url" content="{{ route('admin.ikony') }}">
    @if (Route::has('admin.multimedia.images'))<meta name="admin-library-images-url" content="{{ route('admin.multimedia.images') }}">@endif
    <meta name="admin-material-icons-url" content="{{ route('admin.ikony-material') }}">
    <style>
        :root {
            --color-brand:       {{ $brandPalette['color'] }};
            --color-brand-dark:  {{ $brandPalette['dark'] }};
            --color-brand-light: {{ $brandPalette['light'] }};
        }
        /* Białe karty i przyciski bez ramki zlewały się z jasnoszarym tłem panelu — delikatny obrys i cień je odcinają. */
        #main :is(.rounded, .rounded-md, .rounded-lg, .rounded-xl).bg-white:not([class*="border"]):not([class*="shadow"]) {
            box-shadow: 0 0 0 1px #d9dde4, 0 1px 2px rgba(16, 24, 40, .05);
        }
    </style>
    @include('admin.partials.theme-joomla')
</head>
@php $adminScheme = auth()->user()?->adminScheme() ?? \App\Models\User::DEFAULT_ADMIN_SCHEME; @endphp
<body data-scheme="{{ $adminScheme }}" class="admin-joomla flex min-h-screen text-ink antialiased" data-admin-pickers x-data :class="{ 'overflow-hidden lg:overflow-auto': $store.adminNav.mobileOpen }">

    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-brand-dark focus:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-dark">Przejdź do treści</a>

    {{-- ?osadzony=1 — formularz osadzony w edytorze blokowym (okno „Ustawienia aktualności”): bez menu i nagłówka panelu. --}}
    @php $adminEmbedded = request()->boolean('osadzony'); @endphp
    @unless ($adminEmbedded)
    @include('admin.partials.sidebar')
    @endunless

    @php
        $moduleManager = app(\App\Modules\ModuleManager::class);
        $can = fn (string $module) => (
            $moduleManager->get($module) !== null
                ? $moduleManager->isActive($module)
                : $siteSettings->isModuleEnabled($module)
        ) && auth()->user()->canAccessModule($module);
    @endphp

    <div class="flex min-w-0 flex-1 flex-col">
        @unless ($adminEmbedded)
        <header class="j-topbar sticky top-0 z-20 flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <button type="button" @click="$store.adminNav.open()"
                    class="flex h-10 w-10 flex-none items-center justify-center rounded-md bg-gray-100 text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark lg:hidden"
                    aria-label="Otwórz menu" aria-controls="admin-sidebar" :aria-expanded="$store.adminNav.mobileOpen.toString()">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                {{-- Tytuł strony z ikoną bieżącej sekcji menu (jak „Control Panel” w Joomla); ikonę ustawia admin/partials/sidebar. --}}
                <h1 class="j-title min-w-0 text-lg font-bold sm:text-xl">
                    <span class="j-title-icon" aria-hidden="true"><i class="fa-solid {{ $__env->shared('adminPageIcon', 'fa-gauge') }}"></i></span>
                    <span class="truncate">@yield('title', 'Panel administracyjny')</span>
                </h1>
            </div>
            <div class="flex flex-none items-center gap-2 sm:gap-3">

                {{-- Zobacz stronę (jak „View Site” w Joomla) --}}
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="j-link hidden md:inline-flex">
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i> Zobacz stronę<span class="sr-only"> (otwiera się w nowej karcie)</span>
                </a>

                {{-- Zadania --}}
                @php $myTaskCount = \App\Http\Controllers\Admin\TaskController::myPendingCount(auth()->id()); @endphp
                <a href="{{ route('admin.zadania.index') }}"
                    class="relative flex min-h-10 items-center gap-2 rounded-md px-3 py-2 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ request()->routeIs('admin.zadania.*') ? 'bg-brand-light text-brand-dark' : 'bg-gray-100 text-ink hover:bg-brand-light' }}"
                    aria-label="Zadania{{ $myTaskCount ? ' (' . $myTaskCount . ' oczekujących)' : '' }}">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Zadania</span>
                    <span class="j-pill {{ $myTaskCount ? '' : 'is-zero' }}" aria-hidden="true">{{ $myTaskCount > 99 ? '99+' : $myTaskCount }}</span>
                </a>

                {{-- Kalendarz redakcyjny --}}
                @if ($can('news') || $can('events'))
                    <a href="{{ route('admin.kalendarz.index') }}"
                        class="flex min-h-10 items-center gap-2 rounded-md px-3 py-2 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ request()->routeIs('admin.kalendarz.*') ? 'bg-brand-light text-brand-dark' : 'bg-gray-100 text-ink hover:bg-brand-light' }}"
                        aria-label="Kalendarz redakcyjny">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        <span class="hidden md:inline">Kalendarz</span>
                    </a>
                @endif

                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                    class="flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                    aria-label="Szukaj w panelu (Ctrl+K)">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Szukaj…</span>
                    <kbd class="hidden rounded bg-white px-1.5 text-xs font-normal text-muted sm:inline">Ctrl K</kbd>
                </button>

                {{-- Dostępność --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                        class="min-h-10 rounded-md bg-gray-100 px-3 py-2 text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                        aria-label="Ułatwienia dostępu">
                        <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                        class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-md border border-gray-200 bg-white shadow-xl"
                        role="dialog" aria-label="Ułatwienia dostępu">
                        <div class="border-b border-gray-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-muted">Ułatwienia dostępu</div>

                        {{-- Rozmiar tekstu --}}
                        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                            <span class="flex-1 text-sm text-ink">Rozmiar tekstu</span>
                            <button type="button" data-a11y-font="decrease"
                                class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-label="Zmniejsz tekst">A<sup>−</sup></button>
                            <button type="button" data-a11y-font="reset"
                                class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-label="Domyślny rozmiar tekstu">A</button>
                            <button type="button" data-a11y-font="increase"
                                class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-label="Powiększ tekst">A<sup>+</sup></button>
                        </div>

                        {{-- Tryby kontrastowe --}}
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="mb-2 text-xs font-semibold text-muted">Kontrast</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" data-a11y-contrast="off"
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-ink hover:bg-brand-light aria-pressed:bg-brand-dark aria-pressed:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    aria-pressed="false" aria-label="Normalny kontrast">Normalny</button>
                                <button type="button" data-a11y-contrast="contrast"
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-ink hover:bg-brand-light aria-pressed:bg-brand-dark aria-pressed:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    aria-pressed="false" aria-label="Wysoki kontrast czarno-biały">Wysoki</button>
                                <button type="button" data-a11y-contrast="contrast-bw"
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-ink hover:bg-brand-light aria-pressed:bg-brand-dark aria-pressed:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    aria-pressed="false" aria-label="Kontrast czarno-żółty">Czarno-żółty</button>
                                <button type="button" data-a11y-contrast="contrast-gray"
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-ink hover:bg-brand-light aria-pressed:bg-brand-dark aria-pressed:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    aria-pressed="false" aria-label="Skala szarości">Szary</button>
                            </div>
                        </div>

                        {{-- Odstępy i czcionka --}}
                        <div class="flex flex-col gap-1 px-4 py-3">
                            <button type="button" data-a11y-ls
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-pressed="false">
                                <i class="fa-solid fa-text-width w-4 text-center text-muted" aria-hidden="true"></i>
                                Zwiększ odstęp liter
                            </button>
                            <button type="button" data-a11y-sans
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-pressed="false">
                                <i class="fa-solid fa-font w-4 text-center text-muted" aria-hidden="true"></i>
                                Czcionka bezszeryfowa
                            </button>
                            <button type="button" data-a11y-animations
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                aria-pressed="false">
                                <i class="fa-solid fa-person-running w-4 text-center text-muted" aria-hidden="true"></i>
                                Wstrzymaj animacje
                            </button>
                        </div>
                    </div>
                </div>

                @php
                    $notifItems = \App\Support\AdminNotifications::items(auth()->user());
                    $notifCount = array_sum(array_column($notifItems, 'count'));
                @endphp
                <div x-data="{ open: false, markSeen() { fetch('{{ route('admin.powiadomienia.seen') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' } }); } }"
                    class="relative">
                    <button type="button" @click="open = ! open; if (open) markSeen()" :aria-expanded="open.toString()"
                        class="relative min-h-10 rounded-md bg-gray-100 px-3 py-2 text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                        aria-label="Powiadomienia{{ $notifCount ? ' (' . $notifCount . ' nowych)' : '' }}">
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                        @if ($notifCount)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape="open = false"
                        class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-md border border-gray-200 bg-white shadow-xl"
                        role="menu" aria-label="Powiadomienia">
                        <div class="border-b border-gray-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-muted">Powiadomienia</div>
                        @forelse ($notifItems as $it)
                            <a href="{{ $it['url'] }}" role="menuitem"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-ink hover:bg-gray-50">
                                <i class="fa-solid {{ $it['icon'] }} w-4 text-center text-muted" aria-hidden="true"></i>
                                <span class="flex-1">{{ $it['label'] }}</span>
                                <span class="text-sm font-bold text-brand-dark">{{ $it['count'] }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-muted">Brak nowych powiadomień.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Wyloguj (jak „Log out” w Joomla) --}}
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                    @csrf
                    <button type="submit" class="j-link"><i class="fa-solid fa-right-from-bracket text-xs" aria-hidden="true"></i> Wyloguj</button>
                </form>
            </div>
        </header>

        {{-- Pasek skrótów tylko w kontekście bloga „Wiem FEER” (ogólny pasek „Szybko” usunięty — skróty są w menu i na pulpicie). --}}
        @if (\App\Support\BlogAdmin::inContext(auth()->user()))
        @php $blogPending = \App\Support\BlogAdmin::pendingCommentsCount(); @endphp
        <div class="j-toolbar flex flex-wrap items-center gap-2 px-4 py-2 sm:px-6" data-quickbar="blog">
            <span class="mr-1 text-xs font-bold uppercase tracking-wide text-muted">Szybko:</span>
            <a href="{{ route('admin.wiem-feer.create') }}"
                class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy wpis
            </a>
            <a href="{{ route('admin.wiem.dashboard') }}"
                class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-gauge" aria-hidden="true"></i> Pulpit Wiem FEER
            </a>
            <a href="{{ route('admin.komentarze-bloga.index') }}"
                class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-comments" aria-hidden="true"></i> Komentarze do moderacji ({{ $blogPending }})
            </a>
            <a href="{{ \App\Support\BlogAdmin::publicUrl() }}" target="_blank" rel="noopener"
                class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz blog<span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>
        </div>
        @endif

        @endunless

        @stack('page_module_banner')

        <main id="main" class="{{ $adminEmbedded ? 'p-3 sm:p-4' : 'p-4 sm:p-6' }}" tabindex="-1">
            @if (session('status'))
                <div role="status" aria-live="polite" class="mb-4 flex items-start gap-2 rounded-md bg-brand-light px-4 py-3 text-sm font-bold text-ink">
                    <i class="fa-solid fa-circle-check mt-0.5 text-brand-dark" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if (session('slug_redirect'))
                <div role="status" class="mb-4 flex items-start gap-2 rounded-md bg-gray-100 px-4 py-3 text-sm text-ink">
                    <i class="fa-solid fa-signs-post mt-0.5 text-brand-dark" aria-hidden="true"></i>
                    <span>{{ session('slug_redirect') }}</span>
                </div>
            @endif
            @if (session('reload_url'))
                <script>try{localStorage.setItem('feer_reload',JSON.stringify({url:@json(session('reload_url')),at:Date.now()}))}catch(e){}</script>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-4 flex items-start gap-2 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                    <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @isset($editionLocked)
                <div role="status" class="mb-4 flex items-start gap-2 rounded-md bg-gray-100 px-4 py-3 text-sm font-bold text-ink">
                    <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                    <span>{{ $editionLocked }}</span>
                </div>
            @endisset

            @if (\App\Support\BlogAdmin::isBlogRoute() && \App\Support\BlogAdmin::canAccess(auth()->user()))
                @include('admin.partials.blog-subnav')
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Globalna wyszukiwarka panelu (paleta poleceń Ctrl/⌘+K) --}}
    <div x-data="commandPalette()"
         @open-command-palette.window="openPalette()"
         @keydown.window="hotkey($event)"
         x-show="open" x-cloak
         class="fixed inset-0 z-[200] flex items-start justify-center bg-black/40 p-4 pt-[10vh]"
         role="dialog" aria-modal="true" aria-label="Wyszukiwarka panelu"
         @click.self="close()">
        <div class="w-full max-w-xl overflow-hidden rounded-md bg-white shadow-2xl" @click.stop>
            <div class="flex items-center gap-3 border-b border-gray-200 px-4">
                <i class="fa-solid fa-magnifying-glass text-gray-400" aria-hidden="true"></i>
                <input x-ref="input" x-model="q" @input="onInput()" type="text"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="go(results[active])" @keydown.escape.prevent="close()"
                    placeholder="Szukaj stron, treści, plików, ustawień…"
                    aria-label="Szukaj w panelu" autocomplete="off"
                    role="combobox" aria-autocomplete="list" aria-controls="cp-list"
                    :aria-expanded="(results.length > 0).toString()"
                    :aria-activedescendant="results.length ? 'cp-opt-' + active : null"
                    class="w-full border-0 py-3 text-sm focus:ring-0">
                <span x-show="loading" class="text-xs text-muted">…</span>
            </div>
            <p class="sr-only" role="status" aria-live="polite" x-text="q.trim().length >= 2 && ! loading ? (results.length ? 'Wyników: ' + results.length : 'Brak wyników') : ''"></p>
            <ul id="cp-list" class="max-h-96 overflow-y-auto py-1" role="listbox" aria-label="Wyniki wyszukiwania">
                <template x-for="(item, i) in grouped" :key="i">
                    <template x-if="item._header">
                        <li class="sticky top-0 bg-gray-50 px-4 py-1 text-[11px] font-bold uppercase tracking-widest text-muted" role="presentation" x-text="item._header"></li>
                    </template>
                    <template x-if="!item._header">
                        <li role="option" :id="'cp-opt-' + item._idx" :aria-selected="(item._idx === active).toString()"
                            @click="go(item)" @mouseenter="active = item._idx"
                            :class="item._idx === active ? 'bg-brand/10' : ''"
                            class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm transition-colors">
                            <i class="fa-solid w-4 shrink-0 text-center" :class="item._idx === active ? 'text-brand-dark' : 'text-gray-400'" aria-hidden="true" x-bind:class="item.icon"></i>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium" :class="item._idx === active ? 'text-brand-dark' : 'text-ink'" x-text="item.title"></span>
                                <span x-show="item.secondary" class="block truncate text-xs text-muted" x-text="item.secondary"></span>
                            </span>
                            <span class="shrink-0 text-xs text-muted font-bold" x-text="item.label"></span>
                        </li>
                    </template>
                </template>
                <li x-show="! loading && q.trim().length >= 2 && results.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted">
                    Brak wyników dla „<span x-text="q"></span>".
                </li>
                <li x-show="q.trim().length < 2"
                    class="px-4 py-8 text-center text-sm text-muted">
                    Zacznij pisać — strony i ich treść, aktualności, pliki, osoby, ustawienia…
                    <br><span class="mt-1 block text-xs text-muted">↑↓ nawigacja &nbsp;·&nbsp; Enter otwiera &nbsp;·&nbsp; Esc zamyka</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        function commandPalette() {
            return {
                open: false, q: '', results: [], active: 0, loading: false, timer: null,
                get grouped() {
                    const out = [];
                    let lastLabel = null, idx = 0;
                    for (const item of this.results) {
                        if (item.label !== lastLabel) {
                            out.push({ _header: item.label });
                            lastLabel = item.label;
                        }
                        out.push({ ...item, _idx: idx++ });
                    }
                    return out;
                },
                openPalette() { this.open = true; this.$nextTick(() => this.$refs.input && this.$refs.input.focus()); },
                close() { this.open = false; this.q = ''; this.results = []; this.active = 0; },
                hotkey(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.open ? this.close() : this.openPalette(); }
                },
                onInput() {
                    clearTimeout(this.timer);
                    const term = this.q.trim();
                    if (term.length < 2) { this.results = []; this.loading = false; return; }
                    this.loading = true;
                    this.timer = setTimeout(() => this.search(term), 200);
                },
                async search(term) {
                    try {
                        const res = await fetch('{{ route('admin.search') }}?q=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const data = await res.json();
                        this.results = data.results || [];
                        this.active = 0;
                    } catch (err) { this.results = []; }
                    this.loading = false;
                },
                move(d) { if (this.results.length) { this.active = (this.active + d + this.results.length) % this.results.length; } },
                go(item) { if (item && item.url) { window.location.href = item.url; } },
            };
        }
    </script>

    {{-- Alpine confirm modal — zastępuje natywne confirm() we wszystkich formularzach admin. --}}
    <div x-data x-cloak x-show="$store.confirm.open"
         class="fixed inset-0 z-[300] flex items-center justify-center bg-black/40 p-4"
         role="alertdialog" aria-modal="true" aria-labelledby="confirm-msg"
         @keydown.escape.window="$store.confirm.cancel()">
        <div class="w-full max-w-sm rounded-md bg-white p-6 shadow-2xl" @click.stop>
            <p id="confirm-msg" class="mb-6 text-sm leading-relaxed text-ink" x-text="$store.confirm.message"></p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" id="confirm-cancel-btn" @click="$store.confirm.cancel()"
                    class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    Anuluj
                </button>
                <button type="button" @click="$store.confirm.confirm()"
                    class="rounded-md bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                    Potwierdź
                </button>
                <button type="button" x-show="$store.confirm.extraLabel" x-text="$store.confirm.extraLabel"
                    @click="$store.confirm.extra()"
                    class="w-full rounded-md bg-red-50 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                </button>
            </div>
        </div>
    </div>

    @include('admin.partials.default-editor-prompt')

    @stack('scripts')
    <script>requestAnimationFrame(function () { requestAnimationFrame(function () { document.documentElement.classList.add('admin-ready'); }); });</script>
</body>
</html>
