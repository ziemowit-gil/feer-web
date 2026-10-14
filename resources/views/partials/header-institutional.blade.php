{{--
    Układ nagłówka „Instytucjonalny" — jak w serwisach instytucji wojewódzkich:
    u góry logo organizacji i logotypy partnerów / źródeł finansowania, pod
    cienką linią menu (pogrubione, zwykłe litery) z rozwijanymi panelami
    w ciemnym kolorze marki, a po prawej BIP, ułatwienia dostępu i wyszukiwarka.
    Pasek górny (partials/topbar) nie jest w tym układzie dołączany — jego
    funkcje (dostępność, szukaj, BIP) są w rzędzie menu.

    Style rzędu menu są tutaj (a nie w klasach Tailwind), bo nadpisują wygląd
    wspólnych partiali menu tylko w tym układzie i nie wymagają przebudowy CSS.

    WCAG: panel rozwijany — biały tekst na brand-dark (≥ 4,5:1), widoczny fokus
    (biały obrys) także w panelu; przyciski ikon mają nazwy i cel ≥ 44 px
    (2.5.8); stan dostępności/wyszukiwarki w aria-expanded (4.1.2); Escape
    zamyka panele i oddaje fokus (2.1.2, 2.4.3).
--}}
@php
    $instPartners = $siteSettings->headerPartnerLogos();
    $instBipHref  = $siteSettings->bipHref();
    $instBipExt   = $siteSettings->bipIsExternal();
    $instShowBip  = $siteSettings->show_topbar_bip && filled($instBipHref);
    $instIconBtn  = 'flex h-11 w-11 items-center justify-center rounded-md text-xl transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp

<style>
    .header-inst-nav > ul { color: var(--color-brand-dark); font-size: 1.0625rem; text-transform: none; letter-spacing: normal; column-gap: 1.5rem; }
    .header-inst-nav .uppercase { text-transform: none; }
    .header-inst-nav > ul > li > ul {
        background: var(--color-brand-dark); border: 0; border-radius: 0; margin-top: .5rem;
        padding: .75rem 0; width: max-content; min-width: 15rem; max-width: 26rem;
        box-shadow: 0 10px 24px rgba(0, 0, 0, .18);
    }
    .header-inst-nav > ul > li > ul a,
    .header-inst-nav > ul > li > ul p { color: #fff; font-weight: 700; font-size: 1rem; padding: .375rem 1.75rem; }
    .header-inst-nav > ul > li > ul a:hover { background: rgba(255, 255, 255, .12); color: #fff; text-decoration: underline; text-underline-offset: 3px; }
    .header-inst-nav > ul > li > ul a:focus-visible { background: rgba(255, 255, 255, .12); outline: 2px solid #fff; outline-offset: -4px; }
    .header-inst-nav > ul > li > ul a[aria-current="page"],
    .header-inst-nav > ul > li > ul .menu-hl { color: #fff !important; text-decoration: underline; text-underline-offset: 3px; }
    .header-inst-nav > ul > li > ul p { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; padding-top: .75rem; }
    .header-inst-nav > ul > li > ul .border-t { border-color: rgba(255, 255, 255, .3); }
</style>

<div class="header-inst bg-white" x-data="siteMobileNav(1024)" @keydown.escape.window="closeMenu()">
    <div class="mx-auto max-w-6xl px-4">

        {{-- Górny rząd: logo · logotypy partnerów --}}
        <div class="flex items-center gap-4 border-b border-gray-200 py-3 sm:gap-6 sm:py-4">
            <a href="{{ site_home_url() }}"
               class="flex min-w-0 flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                         class="h-12 w-auto max-w-[12rem] object-contain sm:h-16 sm:max-w-[16rem]">
                @else
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-brand text-xl font-bold text-white" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-lg font-bold text-ink sm:text-xl">{{ $siteSettings->site_name }}</span>
                        @if ($siteSettings->tagline)
                            <span class="hidden text-xs font-medium text-muted sm:block">{{ $siteSettings->tagline }}</span>
                        @endif
                    </span>
                @endunless
            </a>

            @if ($instPartners)
                <ul class="ml-auto hidden items-center gap-6 md:flex lg:gap-10" role="list" aria-label="Partnerzy i źródła finansowania">
                    @foreach ($instPartners as $partner)
                        <li><img src="{{ $partner['url'] }}" alt="{{ $partner['alt'] }}" class="h-10 w-auto max-w-[14rem] object-contain lg:h-12"></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Rząd menu + narzędzia (BIP, dostępność, szukaj) — narzędzia widoczne na każdej szerokości --}}
    <div x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })(), searchOpen: false }"
         x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
         @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() } if (searchOpen) { searchOpen = false; $refs.searchToggle.focus() }"
         @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">

        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-2 lg:py-3">
            {{-- Hamburger (< lg) w rzędzie narzędzi — górny rząd zostaje dla logo --}}
            <span class="lg:hidden">
                @include('partials.mobile-nav-toggle', ['panelId' => 'main-nav-panel', 'onBrand' => false, 'hideAt' => 'lg'])
            </span>

            <nav aria-label="Menu główne" class="header-inst-nav relative hidden lg:block">
                @include('partials.main-nav-items', ['onBrand' => false, 'navDarkText' => false])
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-1 text-brand-dark">
                @if ($instShowBip)
                    <a href="{{ $instBipHref }}" @if ($instBipExt) target="_blank" rel="noopener" @endif
                       class="inline-flex min-h-11 items-center rounded-md px-2 text-lg font-bold transition hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        BIP<span class="sr-only"> — Biuletyn Informacji Publicznej{{ $instBipExt ? ' (otwiera się w nowej karcie)' : '' }}</span>
                    </a>
                @endif

                <button type="button" x-ref="a11yToggle" @click="open = ! open"
                        :aria-expanded="open.toString()" aria-controls="a11y-panel"
                        class="{{ $instIconBtn }}" :class="open ? 'bg-brand-dark text-white' : 'hover:bg-brand-light'">
                    <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                    <span class="sr-only">Ułatwienia dostępu</span>
                </button>

                <button type="button" x-ref="searchToggle"
                        @click="searchOpen = ! searchOpen; if (searchOpen) $nextTick(() => $refs.searchInput.focus())"
                        :aria-expanded="searchOpen.toString()" aria-controls="inst-search"
                        class="{{ $instIconBtn }}" :class="searchOpen ? 'bg-brand-dark text-white' : 'hover:bg-brand-light'">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="sr-only">Wyszukiwarka</span>
                </button>
            </div>
        </div>

        {{-- Rozwijana wyszukiwarka --}}
        <div id="inst-search" x-show="searchOpen" x-cloak class="border-t border-gray-200 bg-gray-50">
            <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu"
                  class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-3">
                <label for="inst-search-input" class="sr-only">Wyszukaj w serwisie</label>
                <input id="inst-search-input" x-ref="searchInput" type="search" name="q" value="{{ request('q') }}"
                       placeholder="Wpisz szukaną frazę" autocomplete="off"
                       class="min-h-11 w-full flex-1 rounded-md border border-gray-400 bg-white px-3 text-base text-ink placeholder:text-gray-600 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand">
                <button type="submit"
                        class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    Szukaj
                </button>
            </form>
        </div>

        @include('partials.a11y-panel')
    </div>

    @include('partials.mobile-nav-panel', ['panelId' => 'main-nav-panel', 'hideAt' => 'lg', 'showSupport' => true, 'socials' => $siteSettings->socialLinks()])
</div>
