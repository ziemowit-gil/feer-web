{{--
    Substyl „Urzędowy" nagłówka FEER — belka jak w szablonie gminnym, ale
    zamiast wyszukiwarki na środku jest numer konta, a szukajka trafia z boku,
    obok BIP i ikon social. Menu ląduje na pasku w kolorze marki poniżej.
--}}
@php
    $officeSocials  = $siteSettings->socialLinks(3);
    $officeAccount  = $siteSettings->office_show_account ? trim((string) $siteSettings->bank_account_number) : '';
    $officeBipMode  = ($siteSettings->bip_mode ?? 'internal') === 'external';
    $officeBipHref  = $officeBipMode ? $siteSettings->bip_url : route('bip');
    $officeShowBip  = $siteSettings->isModuleEnabled('bip') && ($officeBipMode ? filled($siteSettings->bip_url) : true);
@endphp

<header x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">

    {{-- Górna belka: logo | nr konta | szukajka + BIP + social --}}
    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-[1400px] items-center gap-4 px-4 py-3">

            {{-- Logo + nazwa --}}
            <a href="{{ site_route('home') }}" class="flex flex-none items-center gap-3"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                         class="h-16 w-auto max-w-[80px] object-contain">
                @else
                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand text-2xl font-bold text-white">
                        {{ mb_substr($siteSettings->site_name, 0, 1) }}
                    </span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-xs uppercase tracking-wide text-muted">{{ $siteSettings->tagline }}</span>
                        <span class="block text-xl font-bold text-brand">{{ $siteSettings->site_name }}</span>
                    </span>
                @endunless
            </a>

            {{-- Środek: numer konta (zamiast wyszukiwarki) --}}
            @if ($officeAccount)
                <div class="mx-auto hidden max-w-md flex-1 text-center lg:block">
                    <p class="text-xs uppercase tracking-wide text-muted">Numer konta</p>
                    <p class="font-mono text-sm font-bold tracking-wide text-ink">{{ $officeAccount }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('support.show'))
                        <a href="{{ route('support.show') }}" class="text-xs font-bold text-brand hover:text-brand-dark">
                            <i class="fa-solid fa-heart text-[10px]" aria-hidden="true"></i>
                            Wesprzyj naszą działalność
                        </a>
                    @endif
                </div>
            @else
                <span class="flex-1" aria-hidden="true"></span>
            @endif

            {{-- Prawa strona: szukajka, BIP, social --}}
            <div class="ml-auto flex shrink-0 items-center gap-3">

                @if ($siteSettings->office_show_search)
                    <form action="{{ route('search') }}" method="GET" role="search"
                          class="hidden w-44 md:block lg:w-56">
                        <label for="office-search" class="sr-only">wpisz szukaną frazę</label>
                        <div class="flex overflow-hidden rounded-full border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                            <input id="office-search" type="search" name="q" value="{{ request('q') }}"
                                   placeholder="Szukaj w serwisie" autocomplete="off"
                                   class="w-full border-none bg-transparent px-3 py-1.5 text-sm focus:outline-none">
                            <button type="submit"
                                    class="flex h-9 w-9 flex-none items-center justify-center bg-brand text-white transition hover:bg-brand-dark"
                                    aria-label="Szukaj">
                                <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                @endif

                @if ($officeShowBip)
                    <a href="{{ $officeBipHref }}"
                       @if ($officeBipMode) target="_blank" rel="noopener" @endif
                       class="flex-none transition hover:opacity-80 focus-visible:outline-2 focus-visible:outline-brand"
                       aria-label="Biuletyn Informacji Publicznej">
                        <img src="{{ asset('img/bip-logo.png') }}"
                             onerror="this.outerHTML='<span class=\'text-xs font-black tracking-tight text-[#e53935]\'>▶bip</span>'"
                             alt="BIP" class="h-8 w-auto object-contain">
                    </a>
                @endif

                @if ($officeSocials)
                    <span class="hidden h-7 w-px flex-none bg-gray-200 sm:block" aria-hidden="true"></span>
                    <nav aria-label="Media społecznościowe" class="flex items-center">
                        @include('partials.social-icons', ['socialIcons' => $officeSocials])
                    </nav>
                @endif
            </div>
        </div>
    </div>

    {{-- Pasek nawigacyjny --}}
    <nav class="bg-brand shadow-sm" aria-label="Nawigacja główna">
        <div class="mx-auto max-w-[1400px] px-4">
            <div class="flex items-center justify-between">

                <div class="hidden md:block">
                    @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
                </div>

                <button type="button" @click="mobileOpen = !mobileOpen"
                    class="ml-auto flex h-12 w-12 items-center justify-center {{ $siteSettings->navDarkText() ? 'text-gray-900' : 'text-white' }} md:hidden"
                    :aria-expanded="mobileOpen" aria-controls="office-mobile-menu"
                    aria-label="Menu">
                    <i class="fa-solid fa-bars text-xl" x-show="!mobileOpen" aria-hidden="true"></i>
                    <i class="fa-solid fa-xmark text-xl" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
                </button>
            </div>

            {{-- Menu mobilne --}}
            <div id="office-mobile-menu" x-show="mobileOpen" x-cloak
                 class="border-t border-white/20 pb-3"
                 @click.outside="mobileOpen = false">
                <div class="mt-1">
                    @include('partials.main-nav-items', ['mobile' => true])
                </div>

                @if ($officeAccount)
                    <p class="mt-3 text-sm text-white">
                        <span class="text-white/70">Nr konta:</span>
                        <span class="font-mono font-bold">{{ $officeAccount }}</span>
                    </p>
                @endif

                @if ($siteSettings->office_show_search)
                    <form action="{{ route('search') }}" method="GET" role="search" class="mt-3">
                        <label for="office-search-mobile" class="sr-only">wpisz szukaną frazę</label>
                        <div class="flex overflow-hidden rounded border border-white/30">
                            <input id="office-search-mobile" type="search" name="q" value="{{ request('q') }}"
                                   placeholder="wpisz szukaną frazę"
                                   class="flex-1 border-none bg-white/10 px-3 py-2 text-sm text-white placeholder-white/60 focus:outline-none">
                            <button type="submit"
                                    class="flex h-10 w-10 items-center justify-center bg-white/10 text-white hover:bg-white/20"
                                    aria-label="Szukaj">
                                <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                @endif

                @if ($officeSocials)
                    <nav aria-label="Media społecznościowe" class="mt-3 flex items-center">
                        @include('partials.social-icons', ['socialIcons' => $officeSocials])
                    </nav>
                @endif
            </div>
        </div>
    </nav>
</header>
