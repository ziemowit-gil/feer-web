{{--
    Pasek górny (narzędziowy) nagłówka FEER: przełącznik ułatwień dostępu,
    wyszukiwarka, BIP i media społecznościowe. Sam panel ułatwień jest
    w partials/a11y-panel (wspólne data-a11y-* z resources/js/app.js).

    Stan otwarcia panelu jest zapamiętywany w localStorage, żeby wybór
    użytkownika przetrwał przejście na kolejną stronę.

    WCAG: wszystkie cele dotyku mają ≥ 36×36 px (2.5.8), linki zewnętrzne
    zapowiadają nową kartę (3.2.5 / G201), wyszukiwarka to przycisk z lupą
    rozwijający pole pod paskiem (etykieta, landmark `search`), Escape zamyka
    panel albo wyszukiwarkę i oddaje fokus przyciskowi.
--}}
@php
    $feerBar = ($siteSettings->site_template ?? 'default') === 'feer';
    $bipIsExternal = $siteSettings->bipIsExternal();
    $bipHref       = $siteSettings->bipHref();
    $showBip       = $siteSettings->show_topbar_bip && filled($bipHref);
    $topSocials    = $siteSettings->show_topbar_social ? $siteSettings->socialLinks() : [];
    $searchInNav   = $siteSettings->headerLayoutValue() === 'wide_mission' && ($siteSettings->wide_mission_search_in_nav ?? false);

    // Szablon FEER: „Wesprzyj” i numer konta w jednym, niezawijanym wierszu. Numer (długi) pokazujemy od xl, gdzie mieści się
    // obok wyszukiwarki; niżej zostaje sam link „Wesprzyj” (numer jest na stronie wsparcia i w menu mobilnym).
    $barSupportUrl = \Illuminate\Support\Facades\Route::has('support.show') ? route('support.show') : null;
    $barAccount    = trim((string) $siteSettings->bank_account_number);

    $topLink = 'inline-flex min-h-9 items-center gap-1.5 rounded-md px-2 text-sm font-semibold text-ink transition hover:text-brand '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1';
@endphp

<div class="site-topbar border-b {{ $feerBar ? 'site-topbar-feer border-ink bg-ink' : 'border-gray-200 bg-gray-50' }}"
     x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })(), search: false }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
     @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() } else if (search) { search = false; $refs.searchToggle.focus() }"
     @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">

    <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-1.5 sm:gap-4">

        {{-- Ułatwienia dostępu --}}
        <button type="button" x-ref="a11yToggle" @click="open = ! open"
                :aria-expanded="open.toString()" aria-controls="a11y-panel"
                class="inline-flex min-h-9 shrink-0 items-center gap-2 rounded-full border px-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                :class="open ? 'border-brand bg-brand text-white hover:bg-brand-dark' : 'border-gray-300 bg-white text-ink hover:border-brand hover:text-brand'">
            <i class="fa-solid fa-universal-access text-base" aria-hidden="true"></i>
            <span class="hidden sm:inline">Dostępność</span>
            <span class="sr-only sm:hidden">Ułatwienia dostępu</span>
        </button>

        @if ($feerBar && ($barSupportUrl || $barAccount !== ''))
            {{-- Jeden wiersz, bez zawijania: link „Wesprzyj” (akcja), obok numer konta (informacja, jasnoszary #D1D5DB na #1D1D1A ≈ 12:1). --}}
            <div class="hidden min-w-0 items-center gap-x-5 whitespace-nowrap text-sm md:flex">
                @if ($barSupportUrl)
                    <a href="{{ $barSupportUrl }}"
                       class="inline-flex min-h-9 items-center gap-2 rounded-md px-2 font-bold underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                        <i class="fa-solid fa-heart text-xs" aria-hidden="true"></i>Wesprzyj naszą działalność
                    </a>
                @endif
                @if ($barAccount !== '')
                    <span class="hidden items-center gap-1.5 text-white xl:inline-flex">
                        <span class="font-medium">Nr konta:</span>
                        <span class="font-mono text-gray-300">{{ $barAccount }}</span>
                    </span>
                @endif
            </div>
        @endif

        {{-- Witryna w sieci: zawsze link powrotu do portalu głównego (napis i adres z ustawień tej witryny). --}}
        @if ($parentLink = $siteSettings->parentPortalLink())
            <a href="{{ $parentLink['url'] }}" class="inline-flex min-h-9 min-w-0 items-center gap-1.5 rounded-md px-2 text-sm font-semibold underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 {{ $feerBar ? 'text-white' : 'text-ink' }}">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i><span class="truncate">{{ $parentLink['label'] }}</span>
            </a>
        @endif

        <div class="ml-auto flex min-w-0 shrink-0 items-center gap-1 sm:gap-3">

            {{-- Wyszukiwarka (gdy nie przeniesiona do paska menu): przycisk z lupą rozwija pole pod paskiem (disclosure:
                 aria-expanded, fokus od razu w polu, Esc zamyka i wraca na przycisk). Bez JS pole jest widoczne (x-cloak + noscript). --}}
            @unless ($searchInNav)
                <button type="button" x-ref="searchToggle" data-topbar-search
                        @click="search = ! search; if (search) { open = false; $nextTick(() => $refs.searchInput.focus()) }"
                        aria-expanded="false" :aria-expanded="search.toString()" aria-controls="site-search-panel"
                        class="topbar-pill inline-flex min-h-9 shrink-0 items-center gap-2 rounded-full border px-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                        :class="search ? 'border-brand bg-brand text-white hover:bg-brand-dark' : 'border-gray-300 bg-white text-ink hover:border-brand hover:text-brand'">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Szukaj</span>
                    <span class="sr-only sm:hidden">Szukaj w serwisie</span>
                </button>
            @endunless

            {{-- BIP --}}
            @if ($showBip)
                <a href="{{ $bipHref }}" @if ($bipIsExternal) target="_blank" rel="noopener" @endif class="{{ $topLink }}">
                    <i class="fa-solid fa-landmark" aria-hidden="true"></i>
                    <span>BIP</span>
                    <span class="sr-only">— biuletyn informacji publicznej{{ $bipIsExternal ? ' (otwiera się w nowej karcie)' : '' }}</span>
                </a>
            @endif

            {{-- Media społecznościowe --}}
            @if ($topSocials)
                <ul class="flex items-center" aria-label="Media społecznościowe">
                    @foreach ($topSocials as [$socialUrl, $socialIcon, $socialLabel])
                        <li>
                            <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                               class="flex h-9 w-9 items-center justify-center rounded-md text-base text-ink transition hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                               aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @unless ($searchInNav)
        <div id="site-search-panel" class="site-search-panel border-t {{ $feerBar ? 'border-gray-600' : 'border-gray-200' }}" x-show="search" x-cloak>
            <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu" class="mx-auto flex max-w-6xl flex-wrap gap-2 px-4 py-3">
                <label for="site-search" class="sr-only">Wyszukaj w serwisie</label>
                <input id="site-search" x-ref="searchInput" type="search" name="q" value="{{ request('q') }}"
                       placeholder="Szukaj w serwisie" autocomplete="off"
                       class="min-h-11 min-w-0 flex-1 rounded-md border-2 border-ink bg-white px-3 text-base text-ink placeholder:text-gray-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj
                </button>
            </form>
        </div>
    @endunless

    @include('partials.a11y-panel')
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>
