{{--
    Menu boczne panelu administracyjnego.

    Dane menu: App\Support\AdminMenu (grupy główne = obszary pracy: Start, Treści, Działalność, Komunikacja, BIP, Ustawienia, System →
    bloki → pozycje → podpozycje; rola widzi tylko swoje obszary, własny zaraz po Starcie — AdminMenu::ROLE_FOCUS).
    Stan (zwinięcie na desktopie, otwarcie szuflady na mobile) trzyma Alpine store
    `adminNav`, zdefiniowany w admin/layout.blade.php. Styl `.admin-sidebar`: resources/css/app.css.

    Tryby:
      • desktop (≥ lg) rozwinięty  — pełne etykiety, zwijane sekcje (stan w localStorage),
      • desktop (≥ lg) zwinięty    — sama „szyna" ikon; etykieta i podpozycje w wysuwanym panelu po najechaniu/fokusie,
      • mobile (< lg)              — szuflada wysuwana hamburgerem w nagłówku.
--}}
@php
    $authUser   = auth()->user();
    $menu       = $adminMenu ?? \App\Support\AdminMenu::for($authUser);
    // Ikona bieżącej pozycji menu — przy tytule strony w nagłówku (motyw Joomla, admin/layout).
    $__pageIcon = 'fa-gauge';
    foreach ($menu as $__g) {
        foreach ($__g['blocks'] as $__b) {
            foreach ($__b['items'] as $__it) {
                if ($__it['active']) { $__pageIcon = $__it['icon']; break 3; }
            }
        }
    }
    $__env->share('adminPageIcon', $__pageIcon);
    $adminSites = $authUser?->isAdmin()
        ? \App\Models\SiteSetting::orderBy('parent_site_id')->orderBy('id')->get()
        : collect();
    $initial    = mb_strtoupper(mb_substr($authUser?->name ?: $authUser?->email, 0, 1));
    $roleLabel  = \App\Models\User::ROLES[$authUser?->role] ?? 'Edytor';
@endphp

{{-- Tło szuflady na mobile --}}
<div x-show="$store.adminNav.mobileOpen" x-cloak x-transition.opacity
     @click="$store.adminNav.close()"
     class="fixed inset-0 z-30 bg-ink/50 lg:hidden" aria-hidden="true"></div>

<aside id="admin-sidebar"
       class="admin-sidebar nav-style-{{ auth()->user()?->adminNavStyle() ?? \App\Models\User::DEFAULT_ADMIN_NAV_STYLE }} fixed inset-y-0 left-0 z-40 flex flex-col border-r border-gray-200 bg-white lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
       :class="{ 'is-collapsed': $store.adminNav.collapsed, 'is-open': $store.adminNav.mobileOpen }"
       @keydown.escape.window="$store.adminNav.close()"
       aria-label="Panel administracyjny">
    {{-- Stan zwiniętej szyny ustawiany jeszcze przed pierwszym malowaniem (Alpine robi to dopiero po załadowaniu skryptów,
         co dawało widoczne „przeładowanie" menu przy każdym wejściu na stronę). --}}
    <script>try { if (localStorage.getItem('admin-sidebar') === '1') document.getElementById('admin-sidebar').classList.add('is-collapsed'); } catch (e) {}</script>

    {{-- ── Marka + przełączniki ─────────────────────────────────── --}}
    <div class="nav-brand flex h-14 flex-none items-center gap-2 border-b border-gray-200 px-3">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Dashboard">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-9 w-9 flex-none rounded-lg object-contain">
            @else
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand-dark text-sm font-bold text-white" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="nav-label min-w-0 leading-tight">
                <span class="block truncate text-base">
                    <span style="font-family:'Pacifico',cursive;color:var(--color-brand)">We</span><span style="font-family:'Pacifico',cursive;font-weight:300">CMS</span>
                </span>
                <span class="block truncate text-[11px] text-muted">{{ $siteSettings->site_name }}</span>
            </span>
        </a>

        {{-- Zwiń/rozwiń (desktop) --}}
        <button type="button" @click="$store.adminNav.toggleCollapsed()"
                class="nav-collapse-btn hidden h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark lg:flex"
                :aria-expanded="(! $store.adminNav.collapsed).toString()"
                :aria-label="$store.adminNav.collapsed ? 'Rozwiń menu boczne' : 'Zwiń menu boczne'"
                :title="$store.adminNav.collapsed ? 'Rozwiń menu' : 'Zwiń menu'">
            <i class="fa-solid text-xs" :class="$store.adminNav.collapsed ? 'fa-angles-right' : 'fa-angles-left'" aria-hidden="true"></i>
        </button>

        {{-- Zamknij (mobile) --}}
        <button type="button" @click="$store.adminNav.close()"
                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark lg:hidden"
                aria-label="Zamknij menu">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    {{-- ── Selektor witryny (sieć witryn, tylko admin): osobny wiersz pod logo, nazwa i rodzaj bieżącej witryny, lista
         rozwijana w dół pod przyciskiem (nie wyjeżdża poza pasek). W zwężonej szynie zostaje sama ikona z podpowiedzią. --}}
    @if ($adminSites->count() > 1)
        @php $siteIsSub = (bool) $siteSettings->parent_site_id; @endphp
        <div class="nav-site relative flex-none border-b border-gray-200 px-2 py-2" x-data="{ open: false }" @keydown.escape.stop="open = false; $refs.siteBtn.focus()" @click.outside="open = false">
            <button type="button" x-ref="siteBtn" @click="open = ! open; if (open) $nextTick(() => $refs.siteList.querySelector('button, a')?.focus())" :aria-expanded="open.toString()" aria-controls="nav-site-list" aria-haspopup="dialog"
                    class="nav-site-btn flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                    :class="open && 'bg-gray-100'"
                    title="Witryna: {{ $siteSettings->site_name }}">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-lg {{ $siteIsSub ? 'bg-ink text-white' : 'bg-brand-light text-brand-dark' }}" aria-hidden="true">
                    <i class="fa-solid {{ $siteIsSub ? 'fa-sitemap' : 'fa-house' }} text-xs"></i>
                </span>
                <span class="nav-label min-w-0 flex-1 leading-tight">
                    <span class="block text-[10px] font-bold uppercase tracking-wide text-muted">Edytujesz</span>
                    <span class="block truncate text-sm font-bold text-ink">{{ $siteSettings->site_name }}</span>
                </span>
                <i class="nav-label fa-solid fa-chevron-down text-xs text-gray-500 transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
                <span class="sr-only">Zmień witrynę (bieżąca: {{ $siteSettings->site_name }}, {{ $siteIsSub ? 'witryna w sieci' : 'witryna główna' }})</span>
            </button>
            {{-- Pop-up obok przycisku: nad paskiem bocznym, nie rozpycha menu; w zwężonej szynie wysuwa się w prawo od ikony. --}}
            <div id="nav-site-list" x-show="open" x-cloak x-ref="siteList" role="dialog" aria-label="Wybierz witrynę"
                 class="nav-site-list absolute left-2 right-2 top-full z-50 mt-1 rounded-lg border border-gray-200 bg-white p-2 shadow-xl">
                @include('admin.partials.site-switch-list')
            </div>
        </div>
        <style>
            .admin-sidebar.is-collapsed .nav-site-list { left: 100%; right: auto; top: 0; margin: 0 0 0 .5rem; width: 18rem; }
            .admin-sidebar.is-collapsed .nav-site-btn { justify-content: center; padding-left: .25rem; padding-right: .25rem; }
        </style>
    @endif

    {{-- ── Nawigacja: moduły jak w TYPO3 — najwyżej sześć grup głównych ──────────────
         Grupy rozwijają się pojedynczo (akordeon); aktywna grupa jest otwarta od razu. W zwężonej szynie
         widać tylko ikony grup, a lista pozycji wysuwa się po najechaniu lub fokusie. Przycisk grupy
         w szynie rozwija całe menu — to dostęp z klawiatury i dotyku. --}}
    <style>
        .tm-head { display: flex; width: 100%; align-items: center; gap: .625rem; min-height: 2.25rem; padding: .25rem .5rem; border-radius: .375rem; text-align: left; font-weight: 700; font-size: .875rem; color: #1f2937; transition: background-color .15s; }
        .tm-head:hover { background: #f3f4f6; }
        .tm-head:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 1px; }
        .tm-icon { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: .375rem; background: #f3f4f6; color: #6b7280; font-size: .9rem; transition: background-color .15s, color .15s; }
        .tm-group.is-active > .tm-head, .tm-group.is-active .tm-head { color: var(--color-brand-dark); }
        .tm-group.is-active .tm-icon { background: var(--color-brand-dark); color: #fff; }
        .tm-panel { margin: .125rem 0 .25rem .9rem; padding-left: .625rem; border-left: 2px solid #e5e7eb; }
        .tm-sub { margin: .5rem .5rem .125rem; font-size: .6875rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #4b5563; }
        .tm-sub:first-child { margin-top: .25rem; }
        /* Wygląd paska: wyraźniejsze oddzielenie grup, cele dotyku ≥ 2,5 rem, pionowy znacznik bieżącej pozycji. */
        .tm-group + .tm-group { margin-top: .25rem; padding-top: .25rem; border-top: 1px solid #eef0f2; }
        .tm-head { min-height: 2.5rem; font-size: .9375rem; }
        .tm-panel a, .tm-panel .tm-premium-item { min-height: 2.25rem; }
        .tm-panel [aria-current="page"] { position: relative; font-weight: 700; }
        .tm-panel [aria-current="page"]::before { content: ""; position: absolute; left: -.9rem; top: .2rem; bottom: .2rem; width: 3px; border-radius: 2px; background: var(--color-brand-dark); }
        .tm-sub { color: #374151; letter-spacing: .08em; }
        .admin-sidebar.is-collapsed .tm-panel, .admin-sidebar.is-collapsed .nav-search, .admin-sidebar.is-collapsed .nav-quick { display: none !important; }
        /* Moduły premium (AdminMenu::PREMIUM_*): niebieska obwódka w kolorze marki (brand-dark ≥ 3:1 dla obramowań). */
        .tm-head.tm-premium { box-shadow: inset 0 0 0 2px var(--color-brand-dark), inset 0 0 0 4px #fff; }
        .admin-joomla .tm-head.tm-premium { border-color: var(--color-brand-dark); }
        .tm-panel .tm-premium-item { box-shadow: inset 0 0 0 2px var(--color-brand-dark); }
        .nav-flyout .tm-premium-item { margin: .125rem .5rem; border-radius: .375rem; box-shadow: inset 0 0 0 2px var(--color-brand-dark); }
        /* Szeroki ekran, styl „wysuwane” (Mój profil → Menu panelu): grupy wysuwane w prawo zamiast akordeonu (navItem.flyIn);
           strzałka wskazuje w prawo. Styl „rozwijane” zostawia akordeon jak na telefonie. */
        @media (min-width: 1024px) {
            .admin-sidebar.nav-style-flyout .tm-panel { display: none !important; }
            .admin-sidebar.nav-style-flyout .tm-chevron { transform: rotate(-90deg) !important; }
            .nav-flyout a { min-height: 2.5rem; font-size: .9375rem !important; }
            .nav-flyout--wide { width: min(48rem, calc(100vw - 20rem)); }
            .nav-flyout--wide .nav-flyout-cols { display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); column-gap: .5rem; align-items: start; }
            .nav-flyout > p { font-size: 1rem !important; }
            /* Nagłówki bloków (WYGLĄD, TREŚCI I DANE…) — czytelne, nie drobne 10 px; ikony pozycji większe. */
            .nav-flyout p.uppercase { padding-top: .75rem; font-size: .8125rem !important; letter-spacing: .04em; color: var(--color-ink, #1a1a1a); }
            .nav-flyout a > i { font-size: .9375rem !important; width: 1.25rem; }
            .nav-flyout a:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: -2px; }
        }
        .admin-sidebar.is-collapsed .tm-head { justify-content: center; padding-left: .25rem; padding-right: .25rem; }
        .admin-sidebar.is-collapsed .tm-head .tm-badge { position: absolute; top: .125rem; right: .25rem; }
    </style>

    {{-- ── Wyszukiwarka w menu: filtruje pozycje i podpozycje wszystkich grup ──────────
         Wpisanie frazy zastępuje akordeon płaską listą wyników (z nazwą grupy). Enter otwiera
         pierwszy wynik, Escape czyści pole. Ukryta w zwiniętej szynie ikon. --}}
    @php
        ['entries' => $menuEntries, 'current' => $currentEntry] = \App\Support\AdminMenu::entries($menu); // bieżąca strona: „Ostatnio otwarte” i „Dodaj do ulubionych”
        $quickKey = 'admin-nav-quick:' . ($authUser?->id ?? 0) . ':' . ($siteSettings->id ?? 0);
    @endphp
    <div class="nav-search flex-none border-b border-gray-200 px-3 py-1.5"
         x-data="{
            q: '',
            entries: @js($menuEntries),
            norm(s) { return (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l'); },
            get results() {
                const n = this.norm(this.q).trim();
                if (n === '') return [];
                return this.entries.filter(e => this.norm(e.label + ' ' + e.path).includes(n)).slice(0, 30);
            },
            update() { $store.adminNav.searching = this.q.trim() !== ''; },
            clear() { this.q = ''; this.update(); },
            go() { const r = this.results[0]; if (r) window.location.href = r.url; },
            focusFirst() { this.$root.querySelector('#admin-menu-results a')?.focus(); },
         }"
         @keydown.window="if ($event.key === '/' && ! $store.adminNav.topActive() && ! $event.ctrlKey && ! $event.metaKey && ! $event.altKey && ! /^(input|textarea|select)$/i.test($event.target.tagName) && ! $event.target.isContentEditable) { $event.preventDefault(); if ($store.adminNav.collapsed) { $store.adminNav.toggleCollapsed(); } $nextTick(() => $refs.search.focus()); }">
        <label for="admin-menu-search" class="sr-only">Szukaj w menu panelu</label>
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
            <input id="admin-menu-search" type="search" x-model="q" @input="update()" @keydown.enter.prevent="go()" @keydown.escape.stop="clear()" @keydown.arrow-down.prevent="focusFirst()" x-ref="search"
                   placeholder="Szukaj w menu… (naciśnij /)" autocomplete="off" aria-controls="admin-menu-results"
                   class="w-full rounded-lg border-gray-500 bg-gray-50 py-1.5 pl-8 pr-8 text-sm focus:border-brand-dark focus:bg-white focus:ring-brand-dark">
            <button type="button" x-show="q !== ''" x-cloak @click="clear(); $refs.search?.focus()" aria-label="Wyczyść wyszukiwanie"
                    class="absolute right-1.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded text-gray-400 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
            </button>
        </div>
        <div id="admin-menu-results" x-show="q.trim() !== ''" x-cloak class="mt-2 max-h-[60vh] overflow-y-auto" role="region" aria-label="Wyniki wyszukiwania w menu">
            <p class="sr-only" aria-live="polite" x-text="results.length + ' wyników'"></p>
            <ul class="space-y-0.5" role="list">
                <template x-for="r in results" :key="r.url + r.label + r.path">
                    <li>
                        <a :href="r.url" class="block rounded-lg px-2 py-1.5 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <span class="block truncate text-sm font-medium text-ink" x-text="r.label"></span>
                            <span class="block truncate text-[11px] text-muted" x-text="r.path"></span>
                        </a>
                    </li>
                </template>
            </ul>
            <p x-show="results.length === 0" class="px-2 py-3 text-sm text-muted">Brak pozycji menu dla „<span x-text="q"></span>".</p>
        </div>
    </div>

    {{-- ── Ulubione i ostatnio otwarte: skróty użytkownika (przeglądarka, osobno dla konta i witryny; localStorage) ── --}}
    <div class="nav-quick flex-none border-b border-gray-200 px-3 py-2" x-show="! $store.adminNav.searching" x-cloak
         x-data="navQuick(@js($quickKey), @js($currentEntry))">
        <template x-if="pins.length > 0">
            <section aria-labelledby="nav-pins-h">
                <p class="tm-sub" id="nav-pins-h" style="margin: 0 .25rem .25rem">Ulubione</p>
                <ul class="space-y-0.5" role="list">
                    <template x-for="p in pins" :key="p.url">
                        <li class="nav-quick-row flex items-center">
                            <a :href="p.url" class="flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1 text-[13px] font-medium text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <i class="fa-solid fa-star w-4 flex-none text-center text-[0.7rem] text-brand-dark" aria-hidden="true"></i>
                                <span class="truncate" x-text="p.label"></span>
                            </a>
                            <button type="button" @click="unpin(p.url)" class="flex h-8 w-8 flex-none items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                                <span class="sr-only" x-text="'Usuń z ulubionych: ' + p.label"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </section>
        </template>
        <template x-if="visibleRecents.length > 0">
            <section aria-labelledby="nav-recent-h" :class="pins.length > 0 && 'mt-2'">
                <p class="tm-sub" id="nav-recent-h" style="margin: 0 .25rem .25rem">Ostatnio otwarte</p>
                <ul class="space-y-0.5" role="list">
                    <template x-for="r in visibleRecents" :key="r.url">
                        <li>
                            <a :href="r.url" class="flex min-w-0 items-center gap-2 rounded-md px-2 py-1 text-[13px] text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <i class="fa-solid fa-clock-rotate-left w-4 flex-none text-center text-[0.7rem] text-gray-500" aria-hidden="true"></i>
                                <span class="truncate" x-text="r.label"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>
        </template>
        <button type="button" x-show="current" @click="togglePin()" :aria-pressed="isPinned.toString()"
                class="mt-1 flex w-full items-center gap-2 rounded-md px-2 py-1 text-left text-[13px] font-bold text-brand-dark hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                :class="(pins.length > 0 || visibleRecents.length > 0) && 'mt-2'">
            <i class="fa-star w-4 flex-none text-center text-[0.75rem]" :class="isPinned ? 'fa-solid' : 'fa-regular'" aria-hidden="true"></i>
            <span x-text="isPinned ? 'Usuń tę stronę z ulubionych' : 'Dodaj tę stronę do ulubionych'"></span>
        </button>
        <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>
    </div>
    @include('admin.partials.nav-quick-script')

    <nav class="nav-scroll flex-1 overflow-y-auto overscroll-contain px-2 py-2 text-sm" aria-label="Menu panelu" x-show="! $store.adminNav.searching"
         x-data="navAccordion(@js(collect($menu)->firstWhere('active', true)['key'] ?? ''), @js($menu[0]['key'] ?? ''))">
        <ul class="space-y-0.5" role="list">
            @foreach ($menu as $group)
                @php
                    $gid = 'tm-panel-' . $group['key'];
                @endphp
                <li class="tm-group relative {{ $group['active'] ? 'is-active' : '' }}" x-data="navItem(false)"
                    @mouseenter="flyIn($el)" @mouseleave="flyOut()" @focusin="flyIn($el)" @focusout="flyOut($event)"
                    @click.outside="fly = false" @keydown.escape.stop="if (fly) { fly = false; $el.querySelector('.tm-head')?.focus(); }">

                    <button type="button" class="tm-head nav-link relative{{ ($group['premium'] ?? false) ? ' tm-premium' : '' }}" @click="clickHead($el.closest('li'), @js($group['key']), (k) => toggle(k))"
                            :aria-expanded="($store.adminNav.flyout() ? fly : openKey === @js($group['key'])).toString()" aria-controls="{{ $gid }}"
                            title="{{ $group['label'] }}{{ ($group['premium'] ?? false) ? ' — moduł premium' : '' }}">
                        <span class="tm-icon" aria-hidden="true"><i class="fa-solid {{ $group['icon'] }}"></i></span>
                        <span class="nav-label min-w-0 flex-1 truncate">{{ $group['label'] }}@if ($group['premium'] ?? false)<span class="sr-only"> (moduł premium)</span>@endif</span>
                        @if ($group['badge'])
                            <span class="tm-badge nav-badge inline-flex h-5 min-w-5 flex-none items-center justify-center rounded-full bg-brand-dark px-1.5 text-[11px] font-bold leading-none text-white"
                                  aria-label="{{ $group['badge'] }} do obsłużenia">{{ $group['badge'] > 99 ? '99+' : $group['badge'] }}</span>
                        @endif
                        <i class="nav-label tm-chevron fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openKey === @js($group['key']) }" aria-hidden="true"></i>
                    </button>

                    {{-- Lista pozycji grupy --}}
                    <div id="{{ $gid }}" class="tm-panel" data-nav-key="{{ $group['key'] }}" x-show="openKey === @js($group['key'])" @if (! $group['active']) style="display:none" @endif>
                        @include('admin.partials.sidebar-items', ['group' => $group])
                    </div>

                    {{-- Panel grupy wysuwany w prawo (szeroki ekran i zwężona szyna): prawdziwe, osiągalne klawiaturą linki --}}
                    <div x-show="fly && $store.adminNav.flyout()" x-cloak :style="'top:' + flyTop + 'px; left:' + flyLeft + 'px'"
                         @php $flyCount = collect($group['blocks'])->sum(fn ($bl) => count($bl['items'])); @endphp
                         class="nav-flyout fixed z-50 hidden overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-xl lg:block {{ $flyCount > 10 ? 'nav-flyout--wide' : 'w-72' }}"
                         style="max-height: calc(100vh - 1rem)" role="group" aria-label="{{ $group['label'] }}">
                        <p class="px-3 pb-1 text-sm font-bold text-ink"><i class="fa-solid {{ $group['icon'] }} mr-1.5 text-brand-dark" aria-hidden="true"></i>{{ $group['label'] }}</p>
                        {{-- Dużo pozycji (np. Ustawienia): bloki w kolumnach obok siebie. --}}
                        <div class="nav-flyout-cols">
                        @foreach ($group['blocks'] as $block)
                            <div class="nav-flyout-block">
                            @if ($block['heading'])<p class="px-3 pt-2 text-[10px] font-bold uppercase tracking-wider text-muted">{{ $block['heading'] }}</p>@endif
                            @foreach ($block['items'] as $item)
                                <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif @if ($item['premium'] ?? false) title="Moduł premium" @endif
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none {{ ($item['premium'] ?? false) ? 'tm-premium-item' : '' }} {{ $item['active'] ? 'font-semibold text-brand-dark' : 'text-ink hover:text-ink' }}">
                                    <i class="fa-solid {{ $item['icon'] }} w-4 flex-none text-center text-[0.75rem] text-gray-400" aria-hidden="true"></i>
                                    <span class="truncate">{{ $item['label'] }}@if ($item['premium'] ?? false)<span class="sr-only"> (moduł premium)</span>@endif</span>
                                    @if ($item['badge'])<span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold leading-none {{ $item['badge_tone'] === 'muted' ? 'bg-gray-200 text-gray-700' : 'bg-brand-dark text-white' }}">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif
                                </a>
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" @if ($child['active']) aria-current="page" @endif
                                       class="flex min-h-9 items-center gap-2 py-1 pl-10 pr-3 text-[13px] hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none {{ $child['active'] ? 'font-semibold text-brand-dark' : 'text-ink' }}">
                                        <span class="truncate">{{ $child['label'] }}</span>
                                    </a>
                                @endforeach
                            @endforeach
                            </div>
                        @endforeach
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ── Użytkownik ────────────────────────────────────────────── --}}
    <div class="nav-user relative flex-none border-t border-gray-200 p-2" x-data="{ open: false }">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="nav-user-menu"
                class="nav-link flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                title="{{ $authUser?->name ?: $authUser?->email }}">
            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-light text-sm font-bold text-brand-dark" aria-hidden="true">{{ $initial }}</span>
            <span class="nav-label min-w-0 flex-1 leading-tight">
                <span class="block truncate text-sm font-semibold text-ink">{{ $authUser?->name ?: $authUser?->email }}</span>
                <span class="block truncate text-[11px] text-muted">{{ $roleLabel }}</span>
            </span>
            <i class="nav-label fa-solid fa-ellipsis-vertical text-gray-400" aria-hidden="true"></i>
            <span class="sr-only">Menu użytkownika</span>
        </button>

        <div id="nav-user-menu" x-show="open" x-cloak @click.outside="open = false" @keydown.escape.stop="open = false"
             class="absolute bottom-full left-2 z-50 mb-1 w-64 overflow-hidden rounded-md border border-gray-200 bg-white py-1 text-sm shadow-xl"
             role="menu" aria-label="Menu użytkownika">
            @include('admin.partials.user-menu-items')
        </div>
    </div>
    {{-- Grupa otwarta ostatnio (gdy żadna nie jest aktywna) — otwierana od razu, bez skoku po starcie Alpine. --}}
    <script>
        try {
            var nav = document.getElementById('admin-sidebar');
            if (nav && ! nav.querySelector('.tm-group.is-active')) {
                var k = localStorage.getItem('admin-nav-open');
                var p = k && nav.querySelector('.tm-panel[data-nav-key="' + k + '"]');
                if (p) { p.style.display = ''; }
            }
        } catch (e) {}
    </script>
</aside>
