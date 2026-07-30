<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel administracyjny') — {{ $siteSettings->site_name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen bg-gray-50 text-ink antialiased">

    <aside class="flex w-64 flex-none flex-col border-r border-gray-200 bg-white">
        <div class="flex items-center gap-2 border-b border-gray-200 px-5 py-4">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name }}" class="h-9 w-9 rounded object-contain">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded bg-brand text-sm font-bold text-white">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="font-bold leading-tight">Panel<br><span class="text-xs font-normal text-muted">{{ $siteSettings->site_name }}</span></span>
        </div>

        @php
            $can = fn (string $module) => $siteSettings->isModuleEnabled($module) && auth()->user()->canAccessModule($module);

            // Class helpers keep every menu entry visually identical: fixed-width
            // icons so labels line up, a clear active state (tinted background +
            // bold + brand colour), and a brand-colour hover on the icon.
            $itemClass = fn ($patterns) => 'group flex items-center gap-3 rounded-lg px-3 py-2 transition-colors '
                . (request()->routeIs($patterns)
                    ? 'bg-brand-light font-semibold text-brand'
                    : 'text-ink hover:bg-gray-100 hover:text-brand');

            $iconClass = fn ($patterns) => 'w-5 shrink-0 text-center '
                . (request()->routeIs($patterns) ? 'text-brand' : 'text-gray-400 group-hover:text-brand');

            // Every route a section owns — used to auto-expand the section that
            // holds the current page while the rest start collapsed.
            $contentRoutes = ['admin.podstrony.*', 'admin.os-czasu.*', 'admin.pozycje-menu.*', 'admin.newsy.*', 'admin.kategorie-newsow.*', 'admin.ankiety.*', 'admin.materialy-edukacyjne.*', 'admin.wolontariat.*', 'admin.wydarzenia.*', 'admin.faq.*', 'admin.sprawozdania.*', 'admin.lp.*', 'admin.wiem-feer.*'];
            $appearanceRoutes = ['admin.hero.*', 'admin.galeria.*', 'admin.szybkie-akcje.*', 'admin.partnerzy.*', 'admin.banery.*', 'admin.strefy-bannerow.*'];
            $projectRoutes = ['admin.kategorie.*', 'admin.projekty.*'];
            // „Skrzynka" — wszystko, co przychodzi od odwiedzających i czeka na obsługę.
            $inboxRoutes = ['admin.zgloszenia-spotkania.*', 'admin.zgloszenia-barier.*', 'admin.zapisy-materialy.*', 'admin.komentarze-bloga.*'];
            $systemRoutes = ['admin.multimedia.*', 'admin.ustawienia.*', 'admin.newsletter.*', 'admin.uzytkownicy.*', 'admin.grupy.*', 'admin.tresc.*', 'admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.dziennik.*'];
        @endphp

        <nav class="flex-1 space-y-1.5 overflow-y-auto px-3 py-4 text-sm font-medium">
            <a href="{{ route('admin.dashboard') }}" class="{{ $itemClass('admin.dashboard') }}">
                <i class="fa-solid fa-gauge {{ $iconClass('admin.dashboard') }}"></i> Dashboard
            </a>

            @if (auth()->user()->canApproveContent())
                @php $pendingApprovals = \App\Http\Controllers\Admin\ApprovalController::pendingCount(); @endphp
                <a href="{{ route('admin.zatwierdzanie.index') }}" class="{{ $itemClass('admin.zatwierdzanie.*') }}">
                    <i class="fa-solid fa-clipboard-check {{ $iconClass('admin.zatwierdzanie.*') }}"></i>
                    <span class="flex-1">Do zatwierdzenia</span>
                    @if ($pendingApprovals > 0)
                        <span class="rounded-full bg-brand px-2 py-0.5 text-xs font-bold text-white">{{ $pendingApprovals }}</span>
                    @endif
                </a>
            @endif

            <div x-data="{ open: {{ request()->routeIs($contentRoutes) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-content"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                    <span>Treść</span>
                    <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div id="nav-section-content" x-show="open" @unless (request()->routeIs($contentRoutes)) style="display: none" @endunless class="mt-1 space-y-1">
                    @if ($can('pages'))
                        <a href="{{ route('admin.podstrony.index') }}" class="{{ $itemClass(['admin.podstrony.*', 'admin.pozycje-menu.*']) }}">
                            <i class="fa-solid fa-file-lines {{ $iconClass(['admin.podstrony.*', 'admin.pozycje-menu.*']) }}"></i> Strony i menu
                        </a>
                        <a href="{{ route('admin.os-czasu.edit') }}" class="{{ $itemClass('admin.os-czasu.*') }}">
                            <i class="fa-solid fa-timeline {{ $iconClass('admin.os-czasu.*') }}"></i> Oś czasu (historia)
                        </a>
                    @endif
                    @if ($can('news'))
                        <a href="{{ route('admin.newsy.index') }}" class="{{ $itemClass('admin.newsy.*') }}">
                            <i class="fa-solid fa-newspaper {{ $iconClass('admin.newsy.*') }}"></i> Aktualności
                        </a>
                        <a href="{{ route('admin.kategorie-newsow.index') }}" class="{{ $itemClass('admin.kategorie-newsow.*') }}">
                            <i class="fa-solid fa-tags {{ $iconClass('admin.kategorie-newsow.*') }}"></i> Kategorie newsów
                        </a>
                    @endif
                    @if ($can('polls'))
                        <a href="{{ route('admin.ankiety.index') }}" class="{{ $itemClass('admin.ankiety.*') }}">
                            <i class="fa-solid fa-square-poll-vertical {{ $iconClass('admin.ankiety.*') }}"></i> Ankiety
                        </a>
                    @endif
                    @if ($can('materials'))
                        <a href="{{ route('admin.materialy-edukacyjne.index') }}" class="{{ $itemClass('admin.materialy-edukacyjne.*') }}">
                            <i class="fa-solid fa-graduation-cap {{ $iconClass('admin.materialy-edukacyjne.*') }}"></i> Materiały edukacyjne
                        </a>
                    @endif
                    @if ($can('volunteering'))
                        <a href="{{ route('admin.wolontariat.index') }}" class="{{ $itemClass('admin.wolontariat.*') }}">
                            <i class="fa-solid fa-hands-helping {{ $iconClass('admin.wolontariat.*') }}"></i> Wolontariat
                        </a>
                    @endif
                    @if ($can('events'))
                        <a href="{{ route('admin.wydarzenia.index') }}" class="{{ $itemClass('admin.wydarzenia.*') }}">
                            <i class="fa-solid fa-calendar-days {{ $iconClass('admin.wydarzenia.*') }}"></i> Szkolenia i wydarzenia
                        </a>
                    @endif
                    @if ($can('faq'))
                        <a href="{{ route('admin.faq.index') }}" class="{{ $itemClass('admin.faq.*') }}">
                            <i class="fa-solid fa-circle-question {{ $iconClass('admin.faq.*') }}"></i> FAQ
                        </a>
                    @endif
                    @if ($can('reports'))
                        <a href="{{ route('admin.sprawozdania.index') }}" class="{{ $itemClass('admin.sprawozdania.*') }}">
                            <i class="fa-solid fa-file-invoice {{ $iconClass('admin.sprawozdania.*') }}"></i> Sprawozdania
                        </a>
                    @endif
                    @if ($can('landing'))
                        <a href="{{ route('admin.lp.index') }}" class="{{ $itemClass('admin.lp.*') }}">
                            <i class="fa-solid fa-bullhorn {{ $iconClass('admin.lp.*') }}"></i> Landing pages
                        </a>
                    @endif
                    @if ($siteSettings->isModuleEnabled('blog'))
                        <a href="{{ route('admin.wiem-feer.index') }}" class="{{ $itemClass('admin.wiem-feer.*') }}">
                            <i class="fa-solid fa-feather-pointed {{ $iconClass('admin.wiem-feer.*') }}"></i> Wiem FEER (blog)
                        </a>
                    @endif
                </div>
            </div>

            @if ($can('hero') || $can('gallery') || $can('quick_actions') || $can('partners'))
                <div x-data="{ open: {{ request()->routeIs($appearanceRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-appearance"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Wygląd strony głównej</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-appearance" x-show="open" @unless (request()->routeIs($appearanceRoutes)) style="display: none" @endunless class="mt-1 space-y-1">
                        @if ($can('hero'))
                            <a href="{{ route('admin.hero.index') }}" class="{{ $itemClass('admin.hero.*') }}">
                                <i class="fa-solid fa-images {{ $iconClass('admin.hero.*') }}"></i> Slajder (hero)
                            </a>
                        @endif
                        @if ($can('gallery'))
                            <a href="{{ route('admin.galeria.index') }}" class="{{ $itemClass('admin.galeria.*') }}">
                                <i class="fa-solid fa-panorama {{ $iconClass('admin.galeria.*') }}"></i> Galeria
                            </a>
                        @endif
                        @if ($can('quick_actions'))
                            <a href="{{ route('admin.szybkie-akcje.index') }}" class="{{ $itemClass('admin.szybkie-akcje.*') }}">
                                <i class="fa-solid fa-bolt {{ $iconClass('admin.szybkie-akcje.*') }}"></i> Szybkie akcje
                            </a>
                        @endif
                        @if ($can('partners'))
                            <a href="{{ route('admin.partnerzy.index') }}" class="{{ $itemClass('admin.partnerzy.*') }}">
                                <i class="fa-solid fa-handshake {{ $iconClass('admin.partnerzy.*') }}"></i> Partnerzy
                            </a>
                        @endif
                        <a href="{{ route('admin.banery.index') }}" class="{{ $itemClass(['admin.banery.*', 'admin.strefy-bannerow.*']) }}">
                            <i class="fa-solid fa-rectangle-ad {{ $iconClass(['admin.banery.*', 'admin.strefy-bannerow.*']) }}"></i> Bannery
                        </a>
                    </div>
                </div>
            @endif

            @if ($can('projects'))
                <div x-data="{ open: {{ request()->routeIs($projectRoutes) ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-projects"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                        <span>Projekty</span>
                        <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div id="nav-section-projects" x-show="open" @unless (request()->routeIs($projectRoutes)) style="display: none" @endunless class="mt-1 space-y-1">
                        <a href="{{ route('admin.kategorie.index') }}" class="{{ $itemClass('admin.kategorie.*') }}">
                            <i class="fa-solid fa-tags {{ $iconClass('admin.kategorie.*') }}"></i> Kategorie projektów
                        </a>
                        <a href="{{ route('admin.projekty.index') }}" class="{{ $itemClass('admin.projekty.*') }}">
                            <i class="fa-solid fa-diagram-project {{ $iconClass('admin.projekty.*') }}"></i> Projekty
                        </a>
                    </div>
                </div>
            @endif

            <div x-data="{ open: {{ request()->routeIs($inboxRoutes) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-inbox"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                    <span>Skrzynka</span>
                    <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div id="nav-section-inbox" x-show="open" @unless (request()->routeIs($inboxRoutes)) style="display: none" @endunless class="mt-1 space-y-1">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.zgloszenia-spotkania.index') }}" class="{{ $itemClass('admin.zgloszenia-spotkania.*') }}">
                            <i class="fa-solid fa-handshake-angle {{ $iconClass('admin.zgloszenia-spotkania.*') }}"></i> Zgłoszenia (spotkania)
                        </a>
                        <a href="{{ route('admin.zgloszenia-barier.index') }}" class="{{ $itemClass('admin.zgloszenia-barier.*') }}">
                            <i class="fa-solid fa-universal-access {{ $iconClass('admin.zgloszenia-barier.*') }}"></i> Zgłoszenia barier
                        </a>
                    @endif
                    @if ($can('materials'))
                        <a href="{{ route('admin.zapisy-materialy.index') }}" class="{{ $itemClass('admin.zapisy-materialy.*') }}">
                            <i class="fa-solid fa-envelope-open-text {{ $iconClass('admin.zapisy-materialy.*') }}"></i> Zapisy (materiały)
                        </a>
                    @endif
                    @if ($siteSettings->isModuleEnabled('blog'))
                        <a href="{{ route('admin.komentarze-bloga.index') }}" class="{{ $itemClass('admin.komentarze-bloga.*') }}">
                            <i class="fa-solid fa-comments {{ $iconClass('admin.komentarze-bloga.*') }}"></i> Komentarze (blog)
                        </a>
                    @endif
                </div>
            </div>

            <div x-data="{ open: {{ request()->routeIs($systemRoutes) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-section-system"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                    <span>System</span>
                    <i class="fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div id="nav-section-system" x-show="open" @unless (request()->routeIs($systemRoutes)) style="display: none" @endunless class="mt-1 space-y-1">
                    <a href="{{ route('admin.multimedia.index') }}" class="{{ $itemClass('admin.multimedia.*') }}">
                        <i class="fa-solid fa-photo-film {{ $iconClass('admin.multimedia.*') }}"></i> Multimedia
                    </a>
                    @if (auth()->user()->isAdmin())
                        <div x-data="{ open: {{ request()->routeIs('admin.ustawienia.*') ? 'true' : 'false' }} }">
                            <div class="flex items-center {{ $itemClass('admin.ustawienia.*') }}">
                                <a href="{{ route('admin.ustawienia.edit') }}" class="flex min-w-0 flex-1 items-center gap-3">
                                    <i class="fa-solid fa-palette {{ $iconClass('admin.ustawienia.*') }}"></i> Ustawienia strony
                                </a>
                                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="nav-settings-sub"
                                    class="-my-2 -mr-1 flex items-center rounded p-2 text-gray-400 hover:text-brand">
                                    <span class="sr-only">Rozwiń sekcje ustawień</span>
                                    <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                </button>
                            </div>
                            <div id="nav-settings-sub" x-show="open" @unless (request()->routeIs('admin.ustawienia.*')) style="display: none" @endunless
                                class="mt-1 space-y-0.5 border-l border-gray-200 pl-3">
                                @foreach (\App\Models\SiteSetting::SETTINGS_TABS as $tabKey => $tabLabel)
                                    <a href="{{ route('admin.ustawienia.edit', ['tab' => $tabKey]) }}"
                                        class="block rounded-lg px-3 py-1.5 text-sm {{ request()->routeIs('admin.ustawienia.*') && request('tab', 'general') === $tabKey ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}">
                                        {{ $tabLabel }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ route('admin.newsletter.edit') }}" class="{{ $itemClass('admin.newsletter.*') }}">
                            <i class="fa-solid fa-envelope {{ $iconClass('admin.newsletter.*') }}"></i> Newsletter
                        </a>
                        <a href="{{ route('admin.uzytkownicy.index') }}" class="{{ $itemClass('admin.uzytkownicy.*') }}">
                            <i class="fa-solid fa-users {{ $iconClass('admin.uzytkownicy.*') }}"></i> Użytkownicy
                        </a>
                        <a href="{{ route('admin.grupy.index') }}" class="{{ $itemClass('admin.grupy.*') }}">
                            <i class="fa-solid fa-user-group {{ $iconClass('admin.grupy.*') }}"></i> Grupy użytkowników
                        </a>
                        <a href="{{ route('admin.tresc.index') }}" class="{{ $itemClass('admin.tresc.*') }}">
                            <i class="fa-solid fa-right-left {{ $iconClass('admin.tresc.*') }}"></i> Przenoszenie treści
                        </a>
                        <a href="{{ route('admin.przekierowania.index') }}" class="{{ $itemClass('admin.przekierowania.*') }}">
                            <i class="fa-solid fa-signs-post {{ $iconClass('admin.przekierowania.*') }}"></i> Przekierowania 301
                        </a>
                        <a href="{{ route('admin.martwe-linki.index') }}" class="{{ $itemClass('admin.martwe-linki.*') }}">
                            <i class="fa-solid fa-link-slash {{ $iconClass('admin.martwe-linki.*') }}"></i> Martwe linki
                        </a>
                        <a href="{{ route('admin.dziennik.index') }}" class="{{ $itemClass('admin.dziennik.*') }}">
                            <i class="fa-solid fa-clock-rotate-left {{ $iconClass('admin.dziennik.*') }}"></i> Dziennik zdarzeń
                        </a>
                        @php $trashCount = \App\Http\Controllers\Admin\TrashController::count(); @endphp
                        <a href="{{ route('admin.kosz.index') }}" class="{{ $itemClass('admin.kosz.*') }}">
                            <i class="fa-solid fa-trash-can {{ $iconClass('admin.kosz.*') }}"></i> Kosz
                            @if ($trashCount > 0)
                                <span class="ml-auto rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-700">{{ $trashCount }}</span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>
        </nav>

        <div class="space-y-1 border-t border-gray-200 p-3 text-sm">
            <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                <i class="fa-solid fa-key w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i> Zmień hasło
            </a>
            <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                <i class="fa-solid fa-arrow-left w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i> Wróć do strony
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-muted transition-colors hover:bg-gray-100 hover:text-ink">
                    <i class="fa-solid fa-right-from-bracket w-5 shrink-0 text-center text-gray-400 group-hover:text-ink"></i>
                    <span class="min-w-0 truncate">Wyloguj ({{ auth()->user()->email }})</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1">
        <header class="flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-4">
            <h1 class="text-xl font-bold">@yield('title', 'Panel administracyjny')</h1>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Szukaj w panelu (Ctrl+K)">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="hidden sm:inline">Szukaj…</span>
                <kbd class="hidden rounded border border-gray-300 bg-gray-50 px-1.5 text-xs sm:inline">Ctrl K</kbd>
            </button>
        </header>

        <main class="p-6">
            @if (session('status'))
                <div role="status" aria-live="polite" class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
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
            <ul class="max-h-80 overflow-y-auto py-2" role="listbox">
                <template x-for="(item, i) in results" :key="i">
                    <li role="option" :aria-selected="i === active"
                        @click="go(item)" @mouseenter="active = i"
                        :class="i === active ? 'bg-brand/10 text-brand' : 'text-ink'"
                        class="flex cursor-pointer items-center gap-3 px-4 py-2 text-sm">
                        <i class="fa-solid w-4 text-center text-gray-400" :class="item.icon" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1 truncate" x-text="item.title"></span>
                        <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-xs text-muted" x-text="item.label"></span>
                    </li>
                </template>
                <li x-show="! loading && q.trim().length >= 2 && results.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted">
                    Brak wyników.
                </li>
                <li x-show="q.trim().length < 2"
                    class="px-4 py-6 text-center text-sm text-muted">
                    Wpisz co najmniej 2 znaki. ↑↓ nawigacja, Enter otwiera, Esc zamyka.
                </li>
            </ul>
        </div>
    </div>

    <script>
        function commandPalette() {
            return {
                open: false, q: '', results: [], active: 0, loading: false, timer: null,
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
                go(item) { if (item) { window.location.href = item.url; } },
            };
        }
    </script>

</body>
</html>
