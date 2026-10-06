{{--
    Pasek górny (narzędziowy) nagłówka FEER: przełącznik ułatwień dostępu,
    wyszukiwarka, BIP i media społecznościowe. Sam panel ułatwień jest
    w partials/a11y-panel (wspólne data-a11y-* z resources/js/app.js).

    Stan otwarcia panelu jest zapamiętywany w localStorage, żeby wybór
    użytkownika przetrwał przejście na kolejną stronę.

    WCAG: wszystkie cele dotyku mają ≥ 36×36 px (2.5.8), linki zewnętrzne
    zapowiadają nową kartę (3.2.5 / G201), wyszukiwarka ma etykietę
    i landmark `search`, Escape zamyka panel i oddaje fokus przyciskowi.
--}}
@php
    $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external';
    $bipHref       = $bipIsExternal ? $siteSettings->bip_url : route('bip');
    $showBip       = $siteSettings->show_topbar_bip && ($bipIsExternal ? filled($siteSettings->bip_url) : true);
    $topSocials    = $siteSettings->show_topbar_social ? $siteSettings->socialLinks() : [];
    $searchInNav   = $siteSettings->header_layout === 'wide_mission' && ($siteSettings->wide_mission_search_in_nav ?? false);

    $topLink = 'inline-flex min-h-9 items-center gap-1.5 rounded-md px-2 text-sm font-semibold text-ink transition hover:text-brand '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1';
@endphp

<div class="site-topbar relative border-b border-gray-200 bg-gray-50" style="position:relative;z-index:50"
     x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
     @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() }">

    <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-1.5 sm:gap-4">

        {{-- Ułatwienia dostępu --}}
        <button type="button" x-ref="a11yToggle" @click="open = ! open"
                :aria-expanded="open.toString()" aria-controls="a11y-panel"
                class="inline-flex min-h-9 shrink-0 items-center gap-2 rounded-full border border-gray-300 bg-white px-3 text-sm font-semibold text-ink transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                :class="open ? 'border-brand bg-brand text-white hover:text-white' : ''">
            <i class="fa-solid fa-universal-access text-base" aria-hidden="true"></i>
            <span class="hidden sm:inline">Dostępność</span>
            <span class="sr-only sm:hidden">Ułatwienia dostępu</span>
        </button>

        <div class="ml-auto flex min-w-0 shrink-0 items-center gap-1 sm:gap-3">

            {{-- Wyszukiwarka (gdy nie przeniesiona do paska menu) --}}
            @unless ($searchInNav)
                <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu"
                      class="hidden items-center overflow-hidden rounded-md border border-gray-300 bg-white transition focus-within:border-brand focus-within:ring-2 focus-within:ring-brand sm:flex">
                    <label for="site-search" class="sr-only">Wyszukaj w serwisie</label>
                    <input id="site-search" type="search" name="q" value="{{ request('q') }}"
                           placeholder="Wyszukaj w serwisie" autocomplete="off"
                           class="min-h-9 w-40 border-0 bg-transparent px-3 text-sm text-ink placeholder:text-gray-600 focus:outline-none focus:ring-0 md:w-52">
                    <button type="submit"
                            class="flex h-9 w-9 flex-none items-center justify-center text-ink transition hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand"
                            aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </form>
                <a href="{{ route('search') }}" class="{{ $topLink }} min-w-9 justify-center sm:hidden" aria-label="Wyszukaj w serwisie">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </a>
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

    @include('partials.a11y-panel')
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>
