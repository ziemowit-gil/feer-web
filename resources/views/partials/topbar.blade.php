{{--
    Pasek ułatwień dostępu zwinięty do jednego przełącznika (rekomendowane rozwiązanie
    federation, przeniesione tutaj) — te same data-a11y-* co dotąd, ta sama logika w
    resources/js/app.js, tylko domyślnie schowane pod jednym przyciskiem zamiast zawsze
    widocznego rzędu 8 przycisków. Stan otwarcia zapamiętywany w localStorage, żeby wybór
    użytkownika przetrwał kolejne strony.
--}}
<div x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()">

    <div class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
        <div class="mx-auto flex max-w-6xl items-center gap-4 overflow-x-auto px-4 py-2">

            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="a11y-panel"
                class="flex min-h-11 shrink-0 items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 font-bold text-ink transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                Ułatwienia dostępu
                <i class="fa-solid fa-chevron-down text-[0.6rem] transition-transform" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
            </button>

            {{-- Prawa: wyszukiwarka (gdy nie przeniesiona do menu) + BIP + social --}}
            @php
                $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external';
                $bipHref = $bipIsExternal ? $siteSettings->bip_url : route('bip');
                $showBip = $siteSettings->show_topbar_bip && ($bipIsExternal ? $siteSettings->bip_url : true);
                $showSocial = $siteSettings->show_topbar_social && ($siteSettings->facebook_url || $siteSettings->twitter_url || $siteSettings->instagram_url || $siteSettings->linkedin_url || $siteSettings->youtube_url);
                $searchInNav = $siteSettings->header_layout === 'wide_mission' && ($siteSettings->wide_mission_search_in_nav ?? false);
            @endphp

            <div class="ml-auto flex shrink-0 items-center gap-4">
                @unless ($searchInNav)
                    <form action="{{ route('search') }}" method="GET" class="flex items-center" role="search">
                        <label for="site-search" class="sr-only">Wyszukaj w serwisie</label>
                        <input id="site-search" type="search" name="q" value="{{ request('q') }}" placeholder="Wyszukaj w serwisie" autocomplete="off"
                            class="w-40 rounded-l border border-gray-300 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand">
                        <button type="submit" class="flex min-h-6 min-w-6 items-center justify-center rounded-r border border-l-0 border-gray-300 bg-white hover:text-brand" aria-label="Szukaj">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </form>
                @endunless

                @if ($showBip)
                    <a href="{{ $bipHref }}" @if ($bipIsExternal) target="_blank" rel="noopener" @endif
                        class="flex min-h-6 items-center gap-1 font-bold hover:text-brand focus-visible:outline-2 focus-visible:outline-brand">
                        <i class="fa-solid fa-landmark" aria-hidden="true"></i> BIP
                    </a>
                @endif

                @if ($showSocial)
                    <div role="region" aria-label="Media społecznościowe" class="flex items-center gap-3">
                        @if ($siteSettings->facebook_url)
                            <a href="{{ $siteSettings->facebook_url }}" target="_blank" rel="noopener" class="flex min-h-6 min-w-6 items-center justify-center hover:text-brand" aria-label="Facebook">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($siteSettings->twitter_url)
                            <a href="{{ $siteSettings->twitter_url }}" target="_blank" rel="noopener" class="flex min-h-6 min-w-6 items-center justify-center hover:text-brand" aria-label="Twitter / X">
                                <i class="bi bi-twitter-x" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($siteSettings->instagram_url)
                            <a href="{{ $siteSettings->instagram_url }}" target="_blank" rel="noopener" class="flex min-h-6 min-w-6 items-center justify-center hover:text-brand" aria-label="Instagram">
                                <i class="bi bi-instagram" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($siteSettings->linkedin_url)
                            <a href="{{ $siteSettings->linkedin_url }}" target="_blank" rel="noopener" class="flex min-h-6 min-w-6 items-center justify-center hover:text-brand" aria-label="LinkedIn">
                                <i class="bi bi-linkedin" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($siteSettings->youtube_url)
                            <a href="{{ $siteSettings->youtube_url }}" target="_blank" rel="noopener" class="flex min-h-6 min-w-6 items-center justify-center hover:text-brand" aria-label="YouTube">
                                <i class="bi bi-youtube" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności"
        class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
        <div class="mx-auto flex flex-wrap items-center gap-4 px-4 py-3">
            <div class="flex items-center gap-1.5" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="down"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Zmniejsz czcionkę">A-</button>
                <button type="button" data-a11y-font="reset"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Domyślny rozmiar czcionki">A</button>
                <button type="button" data-a11y-font="up"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Zwiększ czcionkę">A+</button>
            </div>
            <button type="button" data-a11y-ls
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Rozstrzał liter">
                <i class="fa-solid fa-text-width" aria-hidden="true"></i> Zwiększ odstęp
            </button>
            <button type="button" data-a11y-sans
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Czcionka bezszeryfowa">
                <i class="fa-solid fa-font" aria-hidden="true"></i> Czcionka
            </button>
            <div role="group" aria-label="Tryb kontrastowy" class="flex items-center gap-2">
                <button type="button" data-a11y-contrast="contrast" class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-pressed="false" aria-label="Kontrast klasyczny">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> Kontrast
                </button>
                <button type="button" data-a11y-contrast="contrast-bw" class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-pressed="false" aria-label="Kontrast czarno-żółty">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-current text-[9px] font-black leading-none" aria-hidden="true" style="background:#000;color:#ff0">A</span> Czarny/żółty
                </button>
                <button type="button" data-a11y-contrast="contrast-gray" class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-pressed="false" aria-label="Tryb szary (odcienie szarości)">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-current text-[9px] font-black leading-none" aria-hidden="true" style="background:#888;color:#fff">A</span> Szary
                </button>
            </div>
            <button type="button" data-a11y-animations class="flex min-h-6 items-center gap-1 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-pressed="false">
                <i class="fa-solid fa-film" aria-hidden="true"></i> Wyłącz animacje
            </button>
            <button type="button" data-a11y-reset class="flex min-h-6 items-center gap-1 text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-label="Przywróć domyślne ustawienia dostępności">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Resetuj
            </button>
        </div>
    </div>
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>
