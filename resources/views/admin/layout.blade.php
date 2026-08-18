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
    <style>
        /* ── Zwijany sidebar ─────────────────────────────────────────── */
        aside.sidebar { width: 16rem; transition: width 200ms ease; }
        aside.sidebar.collapsed { width: 4rem; }

        /* ukryj etykiety i nagłówki sekcji */
        aside.sidebar.collapsed .nav-label,
        aside.sidebar.collapsed .section-header,
        aside.sidebar.collapsed .brand-label,
        aside.sidebar.collapsed .role-indicator,
        aside.sidebar.collapsed .sidebar-bottom .link-label { display: none !important; }

        /* wymuś widoczność zawartości sekcji */
        aside.sidebar.collapsed .section-content { display: block !important; }

        /* wyśrodkuj linki i przyciski poziomo */
        aside.sidebar.collapsed nav a,
        aside.sidebar.collapsed nav > button { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
        aside.sidebar.collapsed .section-content a { padding-left: .5rem; padding-right: .5rem; justify-content: center; }
        aside.sidebar.collapsed .brand-area { justify-content: center; padding-left: .75rem; padding-right: .75rem; }
        aside.sidebar.collapsed .sidebar-bottom a,
        aside.sidebar.collapsed .sidebar-bottom button { justify-content: center; padding-left: .5rem; padding-right: .5rem; }

        /* mały separator zamiast nagłówka sekcji */
        aside.sidebar.collapsed .section-divider { display: block; height: 1px; background: #e5e7eb; margin: .5rem .75rem; }
        aside.sidebar:not(.collapsed) .section-divider { display: none; }
    </style>
</head>
<body class="flex min-h-screen bg-gray-50 text-ink antialiased">

    <aside class="sidebar flex flex-none flex-col border-r border-gray-200 bg-white"
           x-data="{ collapsed: localStorage.getItem('admin-sidebar') === '1' }"
           :class="{ 'collapsed': collapsed }"
           x-effect="localStorage.setItem('admin-sidebar', collapsed ? '1' : '0')">
        <div class="brand-area flex items-center gap-2 border-b border-gray-200 px-5 py-4">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name }}" class="h-9 w-9 flex-none rounded object-contain">
            @else
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-brand text-sm font-bold text-white">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="brand-label min-w-0 leading-tight">
                <span style="font-family:'Pacifico',cursive;color:var(--color-brand)">We</span><span style="font-family:'Pacifico',cursive;font-weight:300">CMS</span>
                <br><span class="text-xs font-normal text-muted">CMS dla NGO</span>
            </span>
        </div>

        <div class="role-indicator border-b border-gray-200 px-4 py-2">
            <p class="text-xs text-muted">Pracujesz jako</p>
            <p class="text-sm font-bold text-ink">
                @php $authUser = auth()->user(); @endphp
                {{ $authUser?->name ?: $authUser?->email }}
                <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $authUser?->isAdmin() ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-muted' }}">
                    {{ \App\Models\User::ROLES[$authUser?->role] ?? 'Edytor' }}
                </span>
            </p>
        </div>

        @php
            $moduleManager = app(\App\Modules\ModuleManager::class);
            $can = fn (string $module) => (
                $moduleManager->get($module) !== null
                    ? $moduleManager->isActive($module)
                    : $siteSettings->isModuleEnabled($module)
            ) && auth()->user()->canAccessModule($module);

            $itemClass = fn ($patterns) => 'group flex items-center gap-3 rounded-lg px-3 py-2 transition-colors '
                . (request()->routeIs($patterns)
                    ? 'bg-brand-light font-semibold text-brand'
                    : 'text-ink hover:bg-gray-100 hover:text-brand');

            $iconClass = fn ($patterns) => 'w-5 shrink-0 text-center '
                . (request()->routeIs($patterns) ? 'text-brand' : 'text-gray-400 group-hover:text-brand');

            // Trasy do aktywnego podświetlenia sekcji nadrzędnych
            $pagesRoutes      = ['admin.podstrony.*', 'admin.pozycje-menu.*', 'admin.os-czasu.*', 'admin.wspolpraca-zgloszenia.*'];
            $appearanceRoutes = ['admin.hero.*', 'admin.galeria.*', 'admin.szybkie-akcje.*', 'admin.partnerzy.*'];
            $marketingRoutes  = ['admin.banery.*', 'admin.strefy-bannerow.*', 'admin.newsletter.*', 'admin.subskrybenci.*', 'admin.kampanie.*'];
            $inboxRoutes      = ['admin.zgloszenia-spotkania.*', 'admin.zgloszenia-barier.*', 'admin.wiadomosci-kontaktowe.*'];
            $usersRoutes      = ['admin.uzytkownicy.*', 'admin.grupy.*', 'admin.zaproszenia-strefy.*'];
            $systemRoutes     = ['admin.ustawienia.*', 'admin.szablony.*', 'admin.tresc.*', 'admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.dziennik.*', 'admin.wcag-scans.*', 'admin.mail-templates.*', 'admin.health.*', 'admin.moduly.*', 'admin.cache.*'];
        @endphp

        <nav class="flex-1 space-y-1.5 overflow-y-auto px-3 py-4 text-sm font-medium">

            {{-- Globalne --}}
            <a href="{{ route('admin.dashboard') }}" class="{{ $itemClass('admin.dashboard') }}" title="Dashboard">
                <i class="fa-solid fa-gauge {{ $iconClass('admin.dashboard') }}"></i>
                <span class="nav-label">Dashboard</span>
            </a>

            @if (auth()->user()->canApproveContent())
                @php $pendingApprovals = \App\Http\Controllers\Admin\ApprovalController::pendingCount(); @endphp
                <a href="{{ route('admin.zatwierdzanie.index') }}" class="{{ $itemClass('admin.zatwierdzanie.*') }}" title="Do zatwierdzenia">
                    <i class="fa-solid fa-clipboard-check {{ $iconClass('admin.zatwierdzanie.*') }}"></i>
                    <span class="nav-label flex-1">Do zatwierdzenia</span>
                    @if ($pendingApprovals > 0)
                        <span class="nav-label rounded-full bg-brand px-2 py-0.5 text-xs font-bold text-white">{{ $pendingApprovals }}</span>
                    @endif
                </a>
            @endif

            {{-- ━━ STRONY ━━ --}}
            @if ($can('pages'))
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($pagesRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-pages"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Strony</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-pages" x-show="open" @unless (request()->routeIs($pagesRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        <a href="{{ route('admin.podstrony.index') }}" class="{{ $itemClass(['admin.podstrony.*', 'admin.pozycje-menu.*']) }}" title="Strony i menu">
                            <i class="fa-solid fa-file-lines {{ $iconClass(['admin.podstrony.*', 'admin.pozycje-menu.*']) }}"></i>
                            <span class="nav-label">Strony i menu</span>
                        </a>
                        <a href="{{ route('admin.os-czasu.edit') }}" class="{{ $itemClass('admin.os-czasu.*') }}" title="Oś czasu">
                            <i class="fa-solid fa-timeline {{ $iconClass('admin.os-czasu.*') }}"></i>
                            <span class="nav-label">Oś czasu (historia)</span>
                        </a>
                        @php $unreadCooperation = \App\Models\CooperationRequest::whereNull('read_at')->count(); @endphp
                        <a href="{{ route('admin.wspolpraca-zgloszenia.index') }}" class="{{ $itemClass('admin.wspolpraca-zgloszenia.*') }}" title="Zgłoszenia współpracy">
                            <i class="fa-solid fa-handshake {{ $iconClass('admin.wspolpraca-zgloszenia.*') }}"></i>
                            <span class="nav-label">Zgłoszenia współpracy</span>
                            @if ($unreadCooperation > 0)
                                <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white" aria-label="{{ $unreadCooperation }} nowych">{{ $unreadCooperation }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            @endif

            {{-- ━━ STRONA GŁÓWNA ━━ --}}
            @if ($can('hero') || $can('gallery') || $can('quick_actions') || $can('partners'))
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($appearanceRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-appearance"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Strona główna</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-appearance" x-show="open" @unless (request()->routeIs($appearanceRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        @if ($can('hero'))
                            <a href="{{ route('admin.hero.index') }}" class="{{ $itemClass('admin.hero.*') }}" title="Slajder (hero)">
                                <i class="fa-solid fa-images {{ $iconClass('admin.hero.*') }}"></i>
                                <span class="nav-label">Slajder (hero)</span>
                            </a>
                        @endif
                        @if ($can('gallery'))
                            <a href="{{ route('admin.galeria.index') }}" class="{{ $itemClass('admin.galeria.*') }}" title="Galeria">
                                <i class="fa-solid fa-panorama {{ $iconClass('admin.galeria.*') }}"></i>
                                <span class="nav-label">Galeria</span>
                            </a>
                        @endif
                        @if ($can('quick_actions'))
                            <a href="{{ route('admin.szybkie-akcje.index') }}" class="{{ $itemClass('admin.szybkie-akcje.*') }}" title="Szybkie akcje">
                                <i class="fa-solid fa-bolt {{ $iconClass('admin.szybkie-akcje.*') }}"></i>
                                <span class="nav-label">Szybkie akcje</span>
                            </a>
                        @endif
                        @if ($can('partners'))
                            <a href="{{ route('admin.partnerzy.index') }}" class="{{ $itemClass('admin.partnerzy.*') }}" title="Partnerzy">
                                <i class="fa-solid fa-handshake {{ $iconClass('admin.partnerzy.*') }}"></i>
                                <span class="nav-label">Partnerzy</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ━━ MODUŁY TREŚCI — każdy moduł = osobna sekcja ━━ --}}
            <div class="section-divider"></div>

            {{-- Aktualności --}}
            @if ($can('news'))
                @php $newsActive = request()->routeIs(['admin.newsy.*', 'admin.kategorie-newsow.*', 'admin.tagi.*']); @endphp
                <div x-data="{ open: {{ $newsActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass(['admin.newsy.*', 'admin.kategorie-newsow.*', 'admin.tagi.*']) }}">
                        <a href="{{ route('admin.newsy.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Aktualności">
                            <i class="fa-solid fa-newspaper {{ $iconClass(['admin.newsy.*', 'admin.kategorie-newsow.*', 'admin.tagi.*']) }}"></i>
                            <span class="nav-label">Aktualności</span>
                        </a>
                        <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-news-sub"
                            class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                            <span class="sr-only">Rozwiń podkategorie aktualności</span>
                            <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                    </div>
                    <div id="nav-news-sub" x-show="open" @unless ($newsActive) style="display: none" @endunless
                        class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                        <a href="{{ route('admin.kategorie-newsow.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.kategorie-newsow.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Kategorie
                        </a>
                        <a href="{{ route('admin.tagi.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.tagi.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Tagi
                        </a>
                    </div>
                </div>
            @endif

            {{-- Blog: Wiem FEER --}}
            @if ($can('blog'))
                @php $blogActive = request()->routeIs(['admin.wiem-feer.*', 'admin.komentarze-bloga.*']); @endphp
                <div x-data="{ open: {{ $blogActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass(['admin.wiem-feer.*', 'admin.komentarze-bloga.*']) }}">
                        <a href="{{ route('admin.wiem-feer.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Wiem FEER (blog)">
                            <i class="fa-solid fa-feather-pointed {{ $iconClass(['admin.wiem-feer.*', 'admin.komentarze-bloga.*']) }}"></i>
                            <span class="nav-label">Wiem FEER (blog)</span>
                        </a>
                        <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-blog-sub"
                            class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                            <span class="sr-only">Rozwiń podkategorie bloga</span>
                            <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                    </div>
                    <div id="nav-blog-sub" x-show="open" @unless ($blogActive) style="display: none" @endunless
                        class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                        <a href="{{ route('admin.komentarze-bloga.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.komentarze-bloga.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Komentarze
                        </a>
                    </div>
                </div>
            @endif

            {{-- Podcasty --}}
            @if ($can('podcasts'))
                <a href="{{ route('admin.podcasty.index') }}" class="{{ $itemClass('admin.podcasty.*') }}" title="Podcasty">
                    <i class="fa-solid fa-podcast {{ $iconClass('admin.podcasty.*') }}"></i>
                    <span class="nav-label flex-1">Podcasty</span>
                </a>
            @endif

            {{-- Kreator formularzy --}}
            @if ($can('forms'))
                @php $formsActive = request()->routeIs(['admin.formularze.*']); @endphp
                <div x-data="{ open: {{ $formsActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass('admin.formularze.*') }}">
                        <a href="{{ route('admin.formularze.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Formularze">
                            <i class="fa-solid fa-wpforms {{ $iconClass('admin.formularze.*') }}"></i>
                            <span class="nav-label">Formularze</span>
                        </a>
                        @php $totalUnread = \App\Models\FormSubmission::whereNull('read_at')->count(); @endphp
                        @if ($totalUnread > 0)
                            <span class="nav-label ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"
                                aria-label="{{ $totalUnread }} nowych zgłoszeń">{{ $totalUnread }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Materiały edukacyjne --}}
            @if ($can('materials'))
                @php $matActive = request()->routeIs(['admin.materialy-edukacyjne.*', 'admin.zapisy-materialy.*']); @endphp
                <div x-data="{ open: {{ $matActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass(['admin.materialy-edukacyjne.*', 'admin.zapisy-materialy.*']) }}">
                        <a href="{{ route('admin.materialy-edukacyjne.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Materiały edukacyjne">
                            <i class="fa-solid fa-graduation-cap {{ $iconClass(['admin.materialy-edukacyjne.*', 'admin.zapisy-materialy.*']) }}"></i>
                            <span class="nav-label">Materiały edukacyjne</span>
                        </a>
                        <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-mat-sub"
                            class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                            <span class="sr-only">Rozwiń podkategorie materiałów</span>
                            <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                    </div>
                    <div id="nav-mat-sub" x-show="open" @unless ($matActive) style="display: none" @endunless
                        class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                        <a href="{{ route('admin.zapisy-materialy.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.zapisy-materialy.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Zapisy uczestników
                        </a>
                    </div>
                </div>
            @endif

            {{-- Szkolenia i wydarzenia --}}
            @if ($can('events'))
                @php $evActive = request()->routeIs(['admin.wydarzenia.*', 'admin.prowadzacy.*']); @endphp
                <div x-data="{ open: {{ $evActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass(['admin.wydarzenia.*', 'admin.prowadzacy.*']) }}">
                        <a href="{{ route('admin.wydarzenia.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Szkolenia i wydarzenia">
                            <i class="fa-solid fa-calendar-days {{ $iconClass(['admin.wydarzenia.*', 'admin.prowadzacy.*']) }}"></i>
                            <span class="nav-label">Szkolenia i wydarzenia</span>
                        </a>
                        <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-ev-sub"
                            class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                            <span class="sr-only">Rozwiń podkategorie wydarzeń</span>
                            <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                    </div>
                    <div id="nav-ev-sub" x-show="open" @unless ($evActive) style="display: none" @endunless
                        class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                        <a href="{{ route('admin.prowadzacy.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.prowadzacy.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Prowadzący
                        </a>
                    </div>
                </div>
            @endif

            {{-- Wolontariat --}}
            @if ($can('volunteering'))
                <a href="{{ route('admin.wolontariat.index') }}" class="{{ $itemClass('admin.wolontariat.*') }}" title="Wolontariat">
                    <i class="fa-solid fa-hands-helping {{ $iconClass('admin.wolontariat.*') }}"></i>
                    <span class="nav-label">Wolontariat</span>
                </a>
            @endif

            {{-- Ogłoszenia o pracę --}}
            @if ($can('jobs'))
                <a href="{{ route('admin.praca.index') }}" class="{{ $itemClass('admin.praca.*') }}" title="Ogłoszenia o pracę">
                    <i class="fa-solid fa-briefcase {{ $iconClass('admin.praca.*') }}"></i>
                    <span class="nav-label">Ogłoszenia o pracę</span>
                </a>
            @endif

            {{-- Projekty --}}
            @if ($can('projects'))
                @php $projActive = request()->routeIs(['admin.projekty.*', 'admin.kategorie.*']); @endphp
                <div x-data="{ open: {{ $projActive ? 'true' : 'false' }} }">
                    <div class="flex items-center {{ $itemClass(['admin.projekty.*', 'admin.kategorie.*']) }}">
                        <a href="{{ route('admin.projekty.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Projekty">
                            <i class="fa-solid fa-diagram-project {{ $iconClass(['admin.projekty.*', 'admin.kategorie.*']) }}"></i>
                            <span class="nav-label">Projekty</span>
                        </a>
                        <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-proj-sub"
                            class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                            <span class="sr-only">Rozwiń podkategorie projektów</span>
                            <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                    </div>
                    <div id="nav-proj-sub" x-show="open" @unless ($projActive) style="display: none" @endunless
                        class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                        <a href="{{ route('admin.kategorie.index') }}"
                            class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.kategorie.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                            Kategorie projektów
                        </a>
                    </div>
                </div>
            @endif

            {{-- FAQ --}}
            @if ($can('faq'))
                <a href="{{ route('admin.faq.index') }}" class="{{ $itemClass('admin.faq.*') }}" title="FAQ">
                    <i class="fa-solid fa-circle-question {{ $iconClass('admin.faq.*') }}"></i>
                    <span class="nav-label">FAQ</span>
                </a>
            @endif

            {{-- Sprawozdania --}}
            @if ($can('reports'))
                <a href="{{ route('admin.sprawozdania.index') }}" class="{{ $itemClass('admin.sprawozdania.*') }}" title="Sprawozdania">
                    <i class="fa-solid fa-file-invoice {{ $iconClass('admin.sprawozdania.*') }}"></i>
                    <span class="nav-label">Sprawozdania</span>
                </a>
            @endif

            {{-- BIP --}}
            @if ($can('bip'))
                <a href="{{ route('admin.bip-dokumenty.index') }}" class="{{ $itemClass('admin.bip-dokumenty.*') }}" title="BIP — dokumenty">
                    <i class="fa-solid fa-landmark {{ $iconClass('admin.bip-dokumenty.*') }}"></i>
                    <span class="nav-label">BIP — dokumenty</span>
                </a>
            @endif

            {{-- Landing pages --}}
            @if ($can('landing'))
                <a href="{{ route('admin.lp.index') }}" class="{{ $itemClass('admin.lp.*') }}" title="Landing pages">
                    <i class="fa-solid fa-bullhorn {{ $iconClass('admin.lp.*') }}"></i>
                    <span class="nav-label">Landing pages</span>
                </a>
            @endif

            {{-- Ankiety --}}
            @if ($can('polls'))
                <a href="{{ route('admin.ankiety.index') }}" class="{{ $itemClass('admin.ankiety.*') }}" title="Ankiety">
                    <i class="fa-solid fa-square-poll-vertical {{ $iconClass('admin.ankiety.*') }}"></i>
                    <span class="nav-label">Ankiety</span>
                </a>
            @endif

            {{-- ━━ MULTIMEDIA ━━ --}}
            <div class="section-divider"></div>
            <a href="{{ route('admin.multimedia.index') }}" class="{{ $itemClass('admin.multimedia.*') }}" title="Multimedia">
                <i class="fa-solid fa-photo-film {{ $iconClass('admin.multimedia.*') }}"></i>
                <span class="nav-label">Multimedia</span>
            </a>

            {{-- ━━ MARKETING (tylko admin) ━━ --}}
            @if (auth()->user()->isAdmin())
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($marketingRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-marketing"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Marketing</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-marketing" x-show="open" @unless (request()->routeIs($marketingRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        <a href="{{ route('admin.banery.index') }}" class="{{ $itemClass(['admin.banery.*', 'admin.strefy-bannerow.*']) }}" title="Bannery">
                            <i class="fa-solid fa-rectangle-ad {{ $iconClass(['admin.banery.*', 'admin.strefy-bannerow.*']) }}"></i>
                            <span class="nav-label">Bannery</span>
                        </a>
                        <a href="{{ route('admin.newsletter.edit') }}" class="{{ $itemClass('admin.newsletter.*') }}" title="Newsletter">
                            <i class="fa-solid fa-envelope {{ $iconClass('admin.newsletter.*') }}"></i>
                            <span class="nav-label">Newsletter</span>
                        </a>
                        <a href="{{ route('admin.subskrybenci.index') }}" class="{{ $itemClass('admin.subskrybenci.*') }}" title="Subskrybenci">
                            <i class="fa-solid fa-bell {{ $iconClass('admin.subskrybenci.*') }}"></i>
                            <span class="nav-label">Subskrybenci</span>
                        </a>
                        <a href="{{ route('admin.kampanie.index') }}" class="{{ $itemClass('admin.kampanie.*') }}" title="Kampanie zbiórkowe">
                            <i class="fa-solid fa-hand-holding-heart {{ $iconClass('admin.kampanie.*') }}"></i>
                            <span class="nav-label">Kampanie zbiórkowe</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- ━━ SKRZYNKA (tylko admin) ━━ --}}
            @if (auth()->user()->isAdmin())
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($inboxRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-inbox"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Skrzynka</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-inbox" x-show="open" @unless (request()->routeIs($inboxRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        @php $contactUnread = \App\Models\ContactMessage::unreadCount(); @endphp
                        <a href="{{ route('admin.wiadomosci-kontaktowe.index') }}" class="{{ $itemClass('admin.wiadomosci-kontaktowe.*') }}" title="Wiadomości kontaktowe">
                            <i class="fa-solid fa-envelope {{ $iconClass('admin.wiadomosci-kontaktowe.*') }}"></i>
                            <span class="nav-label flex items-center gap-1.5">
                                Wiadomości kontaktowe
                                @if ($contactUnread > 0)
                                    <span class="inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white">{{ $contactUnread }}</span>
                                @endif
                            </span>
                        </a>
                        <a href="{{ route('admin.zgloszenia-spotkania.index') }}" class="{{ $itemClass('admin.zgloszenia-spotkania.*') }}" title="Zgłoszenia spotkań">
                            <i class="fa-solid fa-handshake-angle {{ $iconClass('admin.zgloszenia-spotkania.*') }}"></i>
                            <span class="nav-label">Zgłoszenia (spotkania)</span>
                        </a>
                        <a href="{{ route('admin.zgloszenia-barier.index') }}" class="{{ $itemClass('admin.zgloszenia-barier.*') }}" title="Zgłoszenia barier">
                            <i class="fa-solid fa-universal-access {{ $iconClass('admin.zgloszenia-barier.*') }}"></i>
                            <span class="nav-label">Zgłoszenia barier</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- ━━ UŻYTKOWNICY (tylko admin) ━━ --}}
            @if (auth()->user()->isAdmin())
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($usersRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-users"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Użytkownicy</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-users" x-show="open" @unless (request()->routeIs($usersRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        <a href="{{ route('admin.uzytkownicy.index') }}" class="{{ $itemClass('admin.uzytkownicy.*') }}" title="Użytkownicy">
                            <i class="fa-solid fa-users {{ $iconClass('admin.uzytkownicy.*') }}"></i>
                            <span class="nav-label">Użytkownicy</span>
                        </a>
                        <a href="{{ route('admin.grupy.index') }}" class="{{ $itemClass('admin.grupy.*') }}" title="Grupy użytkowników">
                            <i class="fa-solid fa-user-group {{ $iconClass('admin.grupy.*') }}"></i>
                            <span class="nav-label">Grupy użytkowników</span>
                        </a>
                        <a href="{{ route('admin.zaproszenia-strefy.index') }}" class="{{ $itemClass('admin.zaproszenia-strefy.*') }}" title="Zaproszenia do strefy">
                            <i class="fa-solid fa-envelope-open-text {{ $iconClass('admin.zaproszenia-strefy.*') }}"></i>
                            <span class="nav-label">Zaproszenia do strefy</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- ━━ SYSTEM (tylko admin) ━━ --}}
            @if (auth()->user()->isAdmin())
                <div class="section-divider"></div>
                <div x-data="{ open: {{ request()->routeIs($systemRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-system"
                        class="section-header flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>System</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-system" x-show="open" @unless (request()->routeIs($systemRoutes)) style="display: none" @endunless class="section-content mt-1 space-y-1">
                        <div x-data="{ open: {{ request()->routeIs('admin.ustawienia.*') ? 'true' : 'false' }} }">
                            <div class="flex items-center {{ $itemClass('admin.ustawienia.*') }}" title="Ustawienia strony">
                                <a href="{{ route('admin.ustawienia.edit') }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <i class="fa-solid fa-palette {{ $iconClass('admin.ustawienia.*') }}"></i>
                                    <span class="nav-label">Ustawienia strony</span>
                                </a>
                                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-settings-sub"
                                    class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                                    <span class="sr-only">Rozwiń sekcje ustawień</span>
                                    <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                </button>
                            </div>
                            <div id="nav-settings-sub" x-show="open" @unless (request()->routeIs('admin.ustawienia.*')) style="display: none" @endunless
                                class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                                @foreach (\App\Models\SiteSetting::SETTINGS_TABS as $tabKey => $tabLabel)
                                    <a href="{{ route('admin.ustawienia.edit', ['tab' => $tabKey]) }}"
                                        class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.ustawienia.*') && request('tab', 'general') === $tabKey ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                        {{ $tabLabel }}
                                    </a>
                                @endforeach
                                <a href="{{ route('admin.ustawienia.env') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.ustawienia.env*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    <i class="fa-solid fa-file-code mr-1 text-[0.7rem]" aria-hidden="true"></i> Plik .env
                                </a>
                            </div>
                        </div>
                        {{-- Szablony treści + maili --}}
                        @php $szablonyActive = request()->routeIs(['admin.szablony.*', 'admin.mail-templates.*']); @endphp
                        <div x-data="{ open: {{ $szablonyActive ? 'true' : 'false' }} }">
                            <div class="flex items-center {{ $itemClass(['admin.szablony.*', 'admin.mail-templates.*']) }}">
                                <a href="{{ route('admin.szablony.manage') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Szablony">
                                    <i class="fa-solid fa-clone {{ $iconClass(['admin.szablony.*', 'admin.mail-templates.*']) }}"></i>
                                    <span class="nav-label">Szablony</span>
                                </a>
                                <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-szablony-sub"
                                    class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                                    <span class="sr-only">Rozwiń szablony</span>
                                    <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                </button>
                            </div>
                            <div id="nav-szablony-sub" x-show="open" @unless ($szablonyActive) style="display: none" @endunless
                                class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                                <a href="{{ route('admin.szablony.manage') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.szablony.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    Szablony treści
                                </a>
                                <a href="{{ route('admin.mail-templates.index') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.mail-templates.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    Szablony maili
                                </a>
                            </div>
                        </div>

                        {{-- Narzędzia SEO --}}
                        @php $toolsActive = request()->routeIs(['admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.tresc.*']); @endphp
                        <div x-data="{ open: {{ $toolsActive ? 'true' : 'false' }} }">
                            <div class="flex items-center {{ $itemClass(['admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.tresc.*']) }}">
                                <a href="{{ route('admin.przekierowania.index') }}" class="flex min-w-0 flex-1 items-center gap-3" title="Narzędzia SEO">
                                    <i class="fa-solid fa-signs-post {{ $iconClass(['admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.tresc.*']) }}"></i>
                                    <span class="nav-label">Narzędzia SEO</span>
                                </a>
                                <button type="button" @click.stop="open = !open" :aria-expanded="open" aria-controls="nav-tools-sub"
                                    class="nav-label -my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                                    <span class="sr-only">Rozwiń narzędzia SEO</span>
                                    <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                </button>
                            </div>
                            <div id="nav-tools-sub" x-show="open" @unless ($toolsActive) style="display: none" @endunless
                                class="nav-label mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                                <a href="{{ route('admin.przekierowania.index') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.przekierowania.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    Przekierowania 301
                                </a>
                                <a href="{{ route('admin.martwe-linki.index') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.martwe-linki.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    Martwe linki
                                </a>
                                <a href="{{ route('admin.tresc.index') }}"
                                    class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.tresc.*') ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                    Przenoszenie treści
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('admin.dziennik.index') }}" class="{{ $itemClass('admin.dziennik.*') }}" title="Dziennik zdarzeń">
                            <i class="fa-solid fa-clock-rotate-left {{ $iconClass('admin.dziennik.*') }}"></i>
                            <span class="nav-label">Dziennik zdarzeń</span>
                        </a>
                        <a href="{{ route('admin.wcag-scans.index') }}" class="{{ $itemClass('admin.wcag-scans.*') }}" title="Skaner WCAG">
                            <i class="fa-solid fa-universal-access {{ $iconClass('admin.wcag-scans.*') }}"></i>
                            <span class="nav-label">Skaner WCAG</span>
                        </a>
                        <a href="{{ route('admin.moduly.index') }}" class="{{ $itemClass('admin.moduly.*') }}" title="Moduły systemu">
                            <i class="fa-solid fa-puzzle-piece {{ $iconClass('admin.moduly.*') }}"></i>
                            <span class="nav-label">Moduły</span>
                        </a>
                        <a href="{{ route('admin.health.index') }}" class="{{ $itemClass('admin.health.*') }}" title="Health Check">
                            <i class="fa-solid fa-heart-pulse {{ $iconClass('admin.health.*') }}"></i>
                            <span class="nav-label">Health Check</span>
                        </a>
                        <a href="{{ route('admin.cache.index') }}" class="{{ $itemClass('admin.cache.*') }}" title="Cache">
                            <i class="fa-solid fa-bolt {{ $iconClass('admin.cache.*') }}"></i>
                            <span class="nav-label">Cache</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- KOSZ --}}
            <div class="section-divider"></div>
            @php $trashCount = \App\Http\Controllers\Admin\TrashController::count(); @endphp
            <a href="{{ route('admin.kosz.index') }}" class="{{ $itemClass('admin.kosz.*') }}" title="Kosz">
                <i class="fa-solid fa-trash-can {{ $iconClass('admin.kosz.*') }}"></i>
                <span class="nav-label flex-1">Kosz</span>
                @if ($trashCount > 0)
                    <span class="nav-label rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-700">{{ $trashCount }}</span>
                @endif
            </a>

        </nav>

        {{-- Przełącznik zwinięcia + linki profilowe --}}
        <div class="sidebar-bottom space-y-1 border-t border-gray-200 p-3 text-sm">
            <button type="button" @click="collapsed = !collapsed"
                class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink"
                :title="collapsed ? 'Rozwiń menu' : 'Zwiń menu'"
                :aria-label="collapsed ? 'Rozwiń menu boczne' : 'Zwiń menu boczne'">
                <i class="fa-solid w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"
                   :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                <span class="link-label">Zwiń menu</span>
            </button>
            <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink" title="Profil i hasło">
                <i class="fa-solid fa-key w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                <span class="link-label">Profil i hasło</span>
            </a>
            <a href="{{ route('profile.edit') }}#powiadomienia" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink" title="Powiadomienia">
                <i class="fa-solid fa-bell w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                <span class="link-label">Powiadomienia</span>
            </a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dokumentacja') }}" target="_blank" rel="noopener"
                   class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink"
                   title="Dokumentacja techniczna weCMS">
                    <i class="fa-solid fa-book w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                    <span class="link-label">Dokumentacja</span>
                </a>
            @endif
            <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink" title="Wróć do strony">
                <i class="fa-solid fa-arrow-left w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                <span class="link-label">Wróć do strony</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Wyloguj" aria-label="Wyloguj" class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                    <i class="fa-solid fa-right-from-bracket w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                    <span class="link-label min-w-0 truncate">Wyloguj ({{ auth()->user()->email }})</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1">
        <header class="flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-4">
            <h1 class="text-xl font-bold">@yield('title', 'Panel administracyjny')</h1>
            <div class="flex items-center gap-3">

                {{-- Zadania --}}
                @php $myTaskCount = \App\Http\Controllers\Admin\TaskController::myPendingCount(auth()->id()); @endphp
                <a href="{{ route('admin.zadania.index') }}"
                    class="relative flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ request()->routeIs('admin.zadania.*') ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:border-brand hover:text-brand' }}"
                    aria-label="Zadania{{ $myTaskCount ? ' (' . $myTaskCount . ' oczekujących)' : '' }}">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Zadania</span>
                    @if ($myTaskCount > 0)
                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-brand px-1 text-xs font-bold text-white">{{ $myTaskCount > 99 ? '99+' : $myTaskCount }}</span>
                    @endif
                </a>

                {{-- Kalendarz redakcyjny --}}
                @if ($can('news') || $can('events'))
                    <a href="{{ route('admin.kalendarz.index') }}"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ request()->routeIs('admin.kalendarz.*') ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:border-brand hover:text-brand' }}"
                        aria-label="Kalendarz redakcyjny">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        <span class="hidden md:inline">Kalendarz</span>
                    </a>
                @endif

                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Szukaj w panelu (Ctrl+K)">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Szukaj…</span>
                    <kbd class="hidden rounded border border-gray-300 bg-gray-50 px-1.5 text-xs sm:inline">Ctrl K</kbd>
                </button>

                {{-- Dostępność --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        aria-label="Ułatwienia dostępu">
                        <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                        class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
                        role="dialog" aria-label="Ułatwienia dostępu">
                        <div class="border-b border-gray-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-muted">Ułatwienia dostępu</div>

                        {{-- Rozmiar tekstu --}}
                        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                            <span class="flex-1 text-sm text-ink">Rozmiar tekstu</span>
                            <button type="button" data-a11y-font="decrease"
                                class="flex h-8 w-8 items-center justify-center rounded border border-gray-300 text-sm text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                aria-label="Zmniejsz tekst">A<sup>−</sup></button>
                            <button type="button" data-a11y-font="reset"
                                class="flex h-8 w-8 items-center justify-center rounded border border-gray-300 text-sm text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                aria-label="Domyślny rozmiar tekstu">A</button>
                            <button type="button" data-a11y-font="increase"
                                class="flex h-8 w-8 items-center justify-center rounded border border-gray-300 text-sm text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                aria-label="Powiększ tekst">A<sup>+</sup></button>
                        </div>

                        {{-- Tryby kontrastowe --}}
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="mb-2 text-xs font-semibold text-muted">Kontrast</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" data-a11y-contrast="off"
                                    class="rounded border border-gray-300 px-2 py-1 text-xs text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-pressed="false" aria-label="Normalny kontrast">Normalny</button>
                                <button type="button" data-a11y-contrast="contrast"
                                    class="rounded border border-gray-300 px-2 py-1 text-xs text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-pressed="false" aria-label="Wysoki kontrast czarno-biały">Wysoki</button>
                                <button type="button" data-a11y-contrast="contrast-bw"
                                    class="rounded border border-gray-300 px-2 py-1 text-xs text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-pressed="false" aria-label="Kontrast czarno-żółty">Czarno-żółty</button>
                                <button type="button" data-a11y-contrast="contrast-gray"
                                    class="rounded border border-gray-300 px-2 py-1 text-xs text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-pressed="false" aria-label="Skala szarości">Szary</button>
                            </div>
                        </div>

                        {{-- Odstępy i czcionka --}}
                        <div class="flex flex-col gap-1 px-4 py-3">
                            <button type="button" data-a11y-ls
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                aria-pressed="false">
                                <i class="fa-solid fa-text-width w-4 text-center text-muted" aria-hidden="true"></i>
                                Zwiększ odstęp liter
                            </button>
                            <button type="button" data-a11y-sans
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                aria-pressed="false">
                                <i class="fa-solid fa-font w-4 text-center text-muted" aria-hidden="true"></i>
                                Czcionka bezszeryfowa
                            </button>
                            <button type="button" data-a11y-animations
                                class="flex items-center gap-3 rounded px-1 py-1.5 text-sm text-ink hover:bg-gray-50 aria-pressed:font-semibold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
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
                        class="relative rounded-lg border border-gray-300 px-3 py-2 text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        aria-label="Powiadomienia{{ $notifCount ? ' (' . $notifCount . ' nowych)' : '' }}">
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                        @if ($notifCount)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape="open = false"
                        class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
                        role="menu" aria-label="Powiadomienia">
                        <div class="border-b border-gray-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-muted">Powiadomienia</div>
                        @forelse ($notifItems as $it)
                            <a href="{{ $it['url'] }}" role="menuitem"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-ink hover:bg-gray-50">
                                <i class="fa-solid {{ $it['icon'] }} w-4 text-center text-gray-400" aria-hidden="true"></i>
                                <span class="flex-1">{{ $it['label'] }}</span>
                                <span class="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-bold text-brand">{{ $it['count'] }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-muted">Brak nowych powiadomień.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </header>

        {{-- Pasek szybkich skrótów --}}
        @if ($can('news') || $can('events'))
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50/80 px-6 py-2">
            <span class="mr-1 text-xs font-bold uppercase tracking-wide text-muted">Szybko:</span>
            @if ($can('news'))
            <a href="{{ route('admin.newsy.create') }}"
                class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj aktualność
            </a>
            @endif
            @if ($can('events'))
            <a href="{{ route('admin.wydarzenia.create') }}"
                class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Nowe wydarzenie
            </a>
            @endif
            <a href="{{ route('home') }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Strona główna
            </a>
            <a href="{{ route('admin.raporty.brakujace-alt') }}"
                class="ml-auto inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-muted hover:border-amber-400 hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-triangle-exclamation text-amber-500" aria-hidden="true"></i> Brakujące alt
            </a>
        </div>
        @endif

        @stack('page_module_banner')

        <main class="p-6">
            @if (session('status'))
                <div role="status" aria-live="polite" class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('reload_url'))
                <script>try{localStorage.setItem('feer_reload',JSON.stringify({url:@json(session('reload_url')),at:Date.now()}))}catch(e){}</script>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
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
        <div class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
            <div class="flex items-center gap-3 border-b border-gray-200 px-4">
                <i class="fa-solid fa-magnifying-glass text-gray-400" aria-hidden="true"></i>
                <input x-ref="input" x-model="q" @input="onInput()" type="text"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="go(results[active])" @keydown.escape.prevent="close()"
                    placeholder="Szukaj stron, newsów, projektów, sekcji…"
                    aria-label="Szukaj w panelu" autocomplete="off"
                    class="w-full border-0 py-3 text-sm focus:ring-0">
                <span x-show="loading" class="text-xs text-muted">…</span>
            </div>
            <ul class="max-h-96 overflow-y-auto py-1" role="listbox">
                <template x-for="(item, i) in grouped" :key="i">
                    <template x-if="item._header">
                        <li class="sticky top-0 bg-gray-50 px-4 py-1 text-[11px] font-bold uppercase tracking-widest text-muted" role="presentation" x-text="item._header"></li>
                    </template>
                    <template x-if="!item._header">
                        <li role="option" :aria-selected="item._idx === active"
                            @click="go(item)" @mouseenter="active = item._idx"
                            :class="item._idx === active ? 'bg-brand/10' : ''"
                            class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm transition-colors">
                            <i class="fa-solid w-4 shrink-0 text-center" :class="item._idx === active ? 'text-brand' : 'text-gray-400'" aria-hidden="true" x-bind:class="item.icon"></i>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium" :class="item._idx === active ? 'text-brand' : 'text-ink'" x-text="item.title"></span>
                                <span x-show="item.secondary" class="block truncate text-xs text-muted" x-text="item.secondary"></span>
                            </span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted" x-text="item.label"></span>
                        </li>
                    </template>
                </template>
                <li x-show="! loading && q.trim().length >= 2 && results.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted">
                    Brak wyników dla „<span x-text="q"></span>".
                </li>
                <li x-show="q.trim().length < 2"
                    class="px-4 py-8 text-center text-sm text-muted">
                    Zacznij pisać — strony, aktualności, osoby, sekcje…
                    <br><span class="mt-1 block text-xs opacity-60">↑↓ nawigacja &nbsp;·&nbsp; Enter otwiera &nbsp;·&nbsp; Esc zamyka</span>
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
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <p id="confirm-msg" class="mb-6 text-sm leading-relaxed text-ink" x-text="$store.confirm.message"></p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" id="confirm-cancel-btn" @click="$store.confirm.cancel()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    Anuluj
                </button>
                <button type="button" @click="$store.confirm.confirm()"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                    Potwierdź
                </button>
                <button type="button" x-show="$store.confirm.extraLabel" x-text="$store.confirm.extraLabel"
                    @click="$store.confirm.extra()"
                    class="w-full rounded-lg border border-red-300 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
