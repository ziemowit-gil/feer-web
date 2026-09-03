<header x-data="{ mobileOpen: false, searchOpen: false }"
    @keydown.escape="if (searchOpen) { searchOpen = false; $refs.searchToggle.focus() } else { mobileOpen = false; $refs.mobileToggle.focus() }"
    class="sticky top-0 z-40 border-b border-gray-100 bg-white/80 backdrop-blur-md" style="font-family:'Ubuntu', sans-serif; font-weight:700">
    <div class="mx-auto flex max-w-[1400px] items-center gap-4 px-4 py-3">

        {{-- Logo --}}
        <a href="{{ route('home') }}"
           class="flex flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
           aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                    class="h-14 w-auto max-w-[220px] object-contain">
            @else
                <span class="grid h-10 w-10 flex-none grid-cols-2 gap-1 rounded-md bg-gray-900 p-1.5" aria-hidden="true">
                    <span class="rounded-full" style="background:{{ $siteSettings->brandColorN(1) }}"></span>
                    <span class="rounded-full" style="background:{{ $siteSettings->brandColorN(2) }}"></span>
                    <span class="rounded-full" style="background:{{ $siteSettings->brandColorN(3) }}"></span>
                    <span class="rounded-full" style="background:{{ $siteSettings->brandColorN(4) }}"></span>
                </span>
            @endif
            @unless ($siteSettings->showLogoOnly())
                <span class="hidden leading-tight lg:block">
                    <span class="block text-base font-extrabold tracking-tight text-ink">{{ $siteSettings->site_name }}</span>
                    @if ($siteSettings->tagline)
                        <span class="block max-w-[16rem] text-xs font-medium leading-snug text-muted">{{ $siteSettings->tagline }}</span>
                    @endif
                </span>
            @endunless
        </a>

        {{-- Nawigacja: linki z podkreśleniem aktywnej pozycji --}}
        @php
            $navItems = [
                ['label' => 'O nas', 'route' => 'home'],
                ['label' => 'Organizacje', 'route' => 'federation.organizations'],
                ['label' => 'Projekty', 'route' => 'projects.index', 'active' => ['projects.*', 'categories.*']],
                ['label' => 'Kontakt', 'route' => 'contact.show'],
            ];
        @endphp
        <nav class="mx-auto hidden items-center gap-7 md:flex" aria-label="Nawigacja główna">
            @foreach ($navItems as $i => $item)
                @php
                    $isActive = request()->routeIs($item['active'] ?? $item['route']);
                    $isColorful = $siteSettings->isNavItemColorful($i);
                    $itemColor = $isColorful ? $siteSettings->brandColorN(($i % 4) + 1) : 'var(--color-ink)';
                @endphp
                <a href="{{ route($item['route']) }}"
                    class="group relative py-1.5 text-[1.0625rem] font-bold transition-colors duration-200 hover:[color:var(--item-color)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:[color:var(--item-color)] {{ $isActive ? '[color:var(--item-color)]' : 'text-ink/70' }}"
                    style="--item-color:{{ $itemColor }}; --tw-ring-color:{{ $itemColor }}"
                    @if ($isActive) aria-current="page" @endif>
                    {{ $item['label'] }}
                    <span class="pointer-events-none absolute -bottom-[1px] left-0 h-[2px] transition-all duration-200 {{ $isActive ? 'w-full' : 'w-0 group-hover:w-full' }}" style="background:{{ $itemColor }}" aria-hidden="true"></span>
                </a>
            @endforeach

            {{-- "Demo": rozwijane menu prezentujące wszystkie typy podstron (siatka 2 kolumn zamiast długiej listy) --}}
            @php
                $demoLinks = [
                    ['label' => 'Strona standardowa', 'slug' => 'demo-strona-standardowa', 'icon' => 'fa-file-lines'],
                    ['label' => 'Wydarzenie', 'slug' => 'demo-wydarzenie', 'icon' => 'fa-calendar-days'],
                    ['label' => 'Harmonogram zajęć', 'slug' => 'demo-harmonogram', 'icon' => 'fa-table-list'],
                    ['label' => 'O organizacji', 'slug' => 'demo-o-organizacji', 'icon' => 'fa-building-circle-check'],
                    ['label' => 'FAQ', 'slug' => 'najczesciej-zadawane-pytania', 'icon' => 'fa-circle-question'],
                    ['label' => 'Instytucja szkoleniowa', 'slug' => 'demo-instytucja-szkoleniowa', 'icon' => 'fa-chalkboard-user'],
                    ['label' => 'Przeniesiono do BIP', 'slug' => 'demo-przeniesiono-do-bip', 'icon' => 'fa-landmark'],
                    ['label' => 'Strona wewnętrzna (hasło)', 'slug' => 'demo-strona-wewnetrzna', 'icon' => 'fa-lock'],
                    ['label' => 'Strefa członkowska (hasło)', 'slug' => 'strefa-czlonkowska', 'icon' => 'fa-user-lock'],
                    ['label' => 'Siatka kafelków', 'slug' => 'demo-siatka-kafelkow', 'icon' => 'fa-table-cells-large'],
                    ['label' => 'Kafelki (ikony)', 'slug' => 'demo-kafelki-ikony', 'icon' => 'fa-icons'],
                    ['label' => 'Współpraca', 'slug' => 'demo-wspolpraca', 'icon' => 'fa-handshake'],
                    ['label' => 'Prezentacja tego, co było', 'slug' => 'demo-jak-bylo', 'icon' => 'fa-clock-rotate-left'],
                    ['label' => 'Marka (identyfikacja wizualna)', 'slug' => 'demo-marka', 'icon' => 'fa-palette'],
                ];
            @endphp
            <div class="relative" x-data="{ demoOpen: false }" @click.outside="demoOpen = false" @keydown.escape="demoOpen = false">
                <button type="button" @click="demoOpen = !demoOpen" :aria-expanded="demoOpen" aria-controls="federation-demo-menu"
                    class="flex items-center gap-1 py-1.5 text-[1.0625rem] font-bold text-ink/50 transition-colors hover:text-ink/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Demo
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': demoOpen }" aria-hidden="true"></i>
                </button>
                <div id="federation-demo-menu" x-show="demoOpen" x-cloak x-transition
                    class="absolute left-0 top-full z-10 mt-2 w-[30rem] max-w-[90vw] rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                    <ul class="grid max-h-[28rem] grid-cols-2 gap-1 overflow-y-auto" role="list">
                        @foreach ($demoLinks as $link)
                            <li>
                                <a href="{{ url('/'.$link['slug']) }}" class="flex items-start gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50">
                                    <i class="fa-solid {{ $link['icon'] }} mt-0.5 text-xs text-brand" aria-hidden="true"></i>
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                        @if ($siteSettings->isModuleEnabled('help_map'))
                            <li class="col-span-2 mt-1 border-t border-gray-100 pt-1.5">
                                <a href="{{ route('help-map.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50">
                                    <i class="fa-solid fa-map-location-dot text-xs text-brand" aria-hidden="true"></i>
                                    Mapa lokalizacji
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        {{-- Prawa strona: CTA + szukajka + mobile toggle --}}
        <div class="ml-auto flex flex-none items-center gap-2">
            <button type="button" @click="a11yOpen = !a11yOpen" :aria-expanded="a11yOpen" aria-controls="federation-a11y-panel"
                class="flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-200 transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                :class="a11yOpen ? 'text-white' : ''" :style="a11yOpen ? 'background:{{ $siteSettings->brandColorN(1) }}' : 'color:{{ $siteSettings->brandColorN(1) }}'"
                aria-label="Ustawienia dostępności">
                <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
            </button>

            <button type="button" x-ref="searchToggle" @click="searchOpen = true" aria-haspopup="dialog" :aria-expanded="searchOpen"
                class="hidden h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-200 transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:flex"
                style="color:{{ $siteSettings->brandColorN(1) }}"
                aria-label="Szukaj w serwisie">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </button>

            <a href="{{ route('federation.join') }}"
                class="hidden items-center gap-1.5 rounded-md border-2 px-4 py-2 text-sm font-extrabold transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 sm:flex"
                style="border-color:{{ $siteSettings->brandColorN(1) }}; color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                Dołącz do nas
            </a>

            <a href="{{ route('support.show') }}"
                class="hidden items-center gap-1.5 rounded-md px-4 py-2 text-sm font-extrabold text-white shadow-sm transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 sm:flex"
                style="background:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
                Wesprzyj nas
            </a>

            <button type="button" x-ref="mobileToggle" @click="mobileOpen = !mobileOpen"
                class="flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand md:hidden"
                :aria-expanded="mobileOpen" aria-controls="federation-mobile-menu"
                aria-label="Menu">
                <i class="fa-solid fa-bars text-sm" x-show="!mobileOpen" aria-hidden="true"></i>
                <i class="fa-solid fa-xmark text-sm" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- Menu mobilne --}}
    <div id="federation-mobile-menu" x-show="mobileOpen" x-cloak
        class="border-t border-gray-100 bg-white shadow-lg md:hidden"
        @click.outside="mobileOpen = false">
        <nav class="space-y-1 px-4 py-3" aria-label="Nawigacja mobilna">
            @foreach ($navItems as $i => $item)
                @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-base font-bold hover:bg-gray-50 {{ $isActive ? 'text-brand' : 'text-ink' }}">
                    @if ($siteSettings->isNavItemColorful($i))
                        <span class="h-2 w-2 flex-none rounded-full" style="background:{{ $siteSettings->brandColorN(($i % 4) + 1) }}" aria-hidden="true"></span>
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('federation.join') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-base font-bold text-ink hover:bg-gray-50">
                <i class="fa-solid fa-people-group text-sm" style="color:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true"></i>
                Dołącz do nas
            </a>
            <a href="{{ route('support.show') }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-brand hover:bg-gray-50">
                Wesprzyj nas
            </a>
            <button type="button" @click="a11yOpen = !a11yOpen" :aria-expanded="a11yOpen" aria-controls="federation-a11y-panel"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-bold text-ink hover:bg-gray-50">
                <i class="fa-solid fa-universal-access text-sm" style="color:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true"></i>
                Ustawienia dostępności
            </button>
        </nav>
        <form action="{{ route('search') }}" method="GET" role="search" class="px-4 pb-4">
            <label for="federation-search-mobile" class="sr-only">Szukaj w serwisie</label>
            <div class="flex overflow-hidden rounded-full border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                <input id="federation-search-mobile" type="search" name="q" value="{{ request('q') }}"
                    placeholder="Szukaj…"
                    class="flex-1 border-none bg-transparent px-3 py-2 text-sm focus:outline-none">
                <button type="submit" class="flex h-10 w-10 items-center justify-center text-muted hover:text-brand" aria-label="Szukaj">
                    <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal wyszukiwarki, otwierany lupką na desktopie --}}
    <div x-show="searchOpen" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center bg-ink/60 p-4 pt-24 sm:pt-32">
        <div @click.outside="searchOpen = false; $refs.searchToggle.focus()"
            x-transition
            x-init="$watch('searchOpen', (open) => { if (open) $nextTick(() => $refs.searchModalInput.focus()) })"
            role="dialog" aria-modal="true" aria-labelledby="federation-search-modal-heading"
            class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 id="federation-search-modal-heading" class="flex items-center gap-3 text-lg font-extrabold text-ink">
                    <i class="fa-solid fa-magnifying-glass text-2xl" style="color:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true"></i>
                    Szukaj w serwisie
                </h2>
                <button type="button" @click="searchOpen = false; $refs.searchToggle.focus()"
                    class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Zamknij wyszukiwarkę">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form action="{{ route('search') }}" method="GET" role="search">
                <label for="federation-search-modal-input" class="sr-only">Szukaj w serwisie</label>
                <div class="flex items-center overflow-hidden rounded-full border-2 focus-within:ring-2"
                    style="border-color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                    <input id="federation-search-modal-input" x-ref="searchModalInput" type="search" name="q" value="{{ request('q') }}"
                        placeholder="Wpisz szukaną frazę…" autocomplete="off"
                        class="w-full border-none bg-transparent px-4 py-3 text-base focus:outline-none focus:ring-0">
                    <button type="submit" class="flex h-11 w-11 flex-none items-center justify-center text-white"
                        style="background:{{ $siteSettings->brandColorN(1) }}" aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
