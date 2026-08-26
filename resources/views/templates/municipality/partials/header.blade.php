<header x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">

    {{-- Górna belka: logo | wyszukaj | BIP + EU + social --}}
    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-[1400px] items-center gap-4 px-4 py-3">

            {{-- Logo + nazwa --}}
            <a href="{{ route('home') }}"
               class="flex flex-none items-center gap-3 group"
               aria-label="{{ $siteSettings->site_name }} — strona główna"
               title="Kliknij aby przejść do strony głównej.">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                        class="h-16 w-auto max-w-[80px] object-contain">
                @else
                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand text-2xl font-bold text-white">
                        {{ mb_substr($siteSettings->site_name, 0, 1) }}
                    </span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="hidden sm:block leading-tight">
                        <span class="block text-xs text-muted uppercase tracking-wide">{{ $siteSettings->tagline }}</span>
                        <span class="block text-xl font-bold text-brand">{{ $siteSettings->site_name }}</span>
                    </span>
                @endunless
            </a>

            {{-- Wyszukiwarka --}}
            <div class="mx-auto hidden max-w-sm flex-1 sm:block">
                <form action="{{ route('search') }}" method="GET" role="search"
                    class="flex overflow-hidden rounded-full border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                    <label for="mun-search" class="sr-only">wpisz szukaną frazę</label>
                    <input id="mun-search" type="search" name="q" value="{{ request('q') }}"
                        placeholder="wpisz szukaną frazę"
                        autocomplete="off"
                        class="flex-1 border-none bg-transparent px-4 py-2 text-sm focus:outline-none">
                    <button type="submit"
                        class="flex h-10 w-10 flex-none items-center justify-center rounded-r-full bg-brand text-white hover:bg-brand-dark transition"
                        aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            {{-- Prawa strona: BIP, EU, social --}}
            <div class="ml-auto flex shrink-0 items-center gap-3">
                @php
                    $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external';
                    $bipHref = $bipIsExternal ? $siteSettings->bip_url : route('bip');
                    $showBip = $bipIsExternal ? $siteSettings->bip_url : true;
                @endphp
                @if ($showBip)
                    <a href="{{ $bipHref }}"
                       @if ($bipIsExternal) target="_blank" rel="noopener" @endif
                       class="flex-none transition hover:opacity-80 focus-visible:outline-2 focus-visible:outline-brand"
                       aria-label="Biuletyn Informacji Publicznej">
                        <img src="{{ asset('img/bip-logo.png') }}"
                             onerror="this.outerHTML='<span class=\'text-xs font-black tracking-tight text-[#e53935]\'>▶bip</span>'"
                             alt="BIP" class="h-8 w-auto object-contain">
                    </a>
                @endif

                {{-- Flaga UE --}}
                <span class="hidden md:flex flex-none items-center" aria-label="Unia Europejska">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 810 540" class="h-7 w-auto" aria-hidden="true">
                        <rect width="810" height="540" fill="#039"/>
                        <g fill="#fc0">
                            <use xlink:href="#s" x="405" y="54"/>
                            <use xlink:href="#s" x="551" y="105"/>
                            <use xlink:href="#s" x="608" y="243"/>
                            <use xlink:href="#s" x="551" y="381"/>
                            <use xlink:href="#s" x="405" y="432"/>
                            <use xlink:href="#s" x="259" y="381"/>
                            <use xlink:href="#s" x="202" y="243"/>
                            <use xlink:href="#s" x="259" y="105"/>
                            <use xlink:href="#s" x="488" y="84"/>
                            <use xlink:href="#s" x="579" y="157"/>
                            <use xlink:href="#s" x="611" y="271"/>
                            <use xlink:href="#s" x="579" y="385"/>
                        </g>
                        <defs>
                            <path id="s" d="M0-23 6-7H23L10 2l5 16-14-10-14 10 5-16L-10-7H7z"/>
                        </defs>
                    </svg>
                </span>

                {{-- Do 3 ustawionych profili social media, oddzielone od BIP/UE cienką kreską --}}
                @php $munSocials = $siteSettings->socialLinks(3); @endphp
                @if ($munSocials)
                    <span class="hidden h-7 w-px flex-none bg-gray-200 sm:block" aria-hidden="true"></span>
                    <nav aria-label="Media społecznościowe" class="flex items-center">
                        @include('partials.social-icons', ['socialIcons' => $munSocials])
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- Pasek nawigacyjny --}}
    <nav class="bg-brand shadow-sm" aria-label="Nawigacja główna">
        <div class="mx-auto max-w-[1400px] px-4">
            <div class="flex items-center justify-between">

                {{-- Menu desktop --}}
                <div class="hidden md:block">
                    @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
                </div>

                {{-- Przycisk mobilny --}}
                <button type="button" @click="mobileOpen = !mobileOpen"
                    class="ml-auto flex h-12 w-12 items-center justify-center {{ $siteSettings->navDarkText() ? 'text-gray-900' : 'text-white' }} md:hidden"
                    :aria-expanded="mobileOpen" aria-controls="mun-mobile-menu"
                    aria-label="Menu">
                    <i class="fa-solid fa-bars text-xl" x-show="!mobileOpen" aria-hidden="true"></i>
                    <i class="fa-solid fa-xmark text-xl" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
                </button>
            </div>

            {{-- Menu mobilne --}}
            <div id="mun-mobile-menu" x-show="mobileOpen" x-cloak
                class="border-t border-white/20 pb-3"
                @click.outside="mobileOpen = false">
                <div class="mt-1">
                    @include('partials.main-nav-items', ['mobile' => true])
                </div>

                {{-- Wyszukiwarka mobilna --}}
                <form action="{{ route('search') }}" method="GET" role="search" class="mt-3">
                    <label for="mun-search-mobile" class="sr-only">wpisz szukaną frazę</label>
                    <div class="flex overflow-hidden rounded border border-white/30">
                        <input id="mun-search-mobile" type="search" name="q" value="{{ request('q') }}"
                            placeholder="wpisz szukaną frazę"
                            class="flex-1 border-none bg-white/10 px-3 py-2 text-sm text-white placeholder-white/60 focus:outline-none">
                        <button type="submit"
                            class="flex h-10 w-10 items-center justify-center bg-white/10 text-white hover:bg-white/20"
                            aria-label="Szukaj">
                            <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

</header>


