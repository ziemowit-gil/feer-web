<div class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600" role="region" aria-label="Ustawienia dostępności i wyszukiwanie">
    <div class="mx-auto flex max-w-6xl items-center gap-4 overflow-x-auto px-4 py-2">

        {{-- Lewa: przyciski dostępności --}}
        <div class="flex shrink-0 items-center gap-4">
            <div class="flex items-center gap-2" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="down" class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand" aria-label="Zmniejsz czcionkę">A-</button>
                <button type="button" data-a11y-font="reset" class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand" aria-label="Domyślny rozmiar czcionki">A</button>
                <button type="button" data-a11y-font="up" class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand" aria-label="Zwiększ czcionkę">A+</button>
            </div>
            <button type="button" data-a11y-contrast class="flex min-h-6 items-center gap-1 hover:text-brand" aria-pressed="false">
                <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> Wersja kontrastowa
            </button>
            <button type="button" data-a11y-animations class="flex min-h-6 items-center gap-1 hover:text-brand" aria-pressed="false">
                <i class="fa-solid fa-film" aria-hidden="true"></i> Wyłącz animacje
            </button>
        </div>

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
