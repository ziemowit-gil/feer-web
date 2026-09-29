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

<div x-data="siteMobileNav(768)" @keydown.escape.window="closeMenu()">

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
    <nav class="relative bg-brand shadow-sm" aria-label="Nawigacja główna">
        <div class="mx-auto max-w-[1400px] px-4">
            <div class="flex items-center justify-between">

                <div class="hidden md:block">
                    @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
                </div>

                <span class="ml-auto md:hidden">
                    @include('partials.mobile-nav-toggle', ['panelId' => 'office-mobile-menu', 'onBrand' => ! $siteSettings->navDarkText(), 'hideAt' => 'md'])
                </span>
            </div>
        </div>
    </nav>

    {{-- Menu mobilne (< md): wyszukiwarka, menu, konto, social --}}
    @include('partials.mobile-nav-panel', [
        'panelId' => 'office-mobile-menu', 'hideAt' => 'md',
        'showSearch' => (bool) $siteSettings->office_show_search,
        'showSupport' => $officeAccount !== '',
        'socials' => $officeSocials,
    ])
</div>
