@php
    $headerLayout  = $siteSettings->headerLayoutValue();
    $inlineOnBrand = $headerLayout === 'brand_bar_inline';
    $wideMission   = $headerLayout === 'wide_mission';
    $wmLayout      = $siteSettings->wideMissionLayoutValue();
    $officeBar     = $headerLayout === 'office_bar';
@endphp

@if ($officeBar)
@include('partials.header-office')

@elseif ($wideMission)
{{-- ─── Layout: Szeroka belka (logo | misja | social + CTA) ───────────────
     Landmark <header> jest w layouts/site.blade.php (obejmuje też pasek górny).

     WCAG: hamburger ma ≥ 44 px i dynamiczną etykietę (2.5.8, 4.1.2), panel
     mobilny dostaje fokus po otwarciu, a Escape zamyka go i oddaje fokus
     przyciskowi (2.1.2, 2.4.3); wszystkie linki mają widoczny fokus (2.4.7);
     placeholder wyszukiwarki na tle marki ma kontrast ≥ 4.5:1 (1.4.3).
--}}
@php
    $socials   = $siteSettings->socialLinks();
    $wmSocials = $siteSettings->headerSocialLinks([
        'wide_mission_social_1', 'wide_mission_social_2', 'wide_mission_social_3',
    ]);
    $wmCtaLabel = trim($siteSettings->wide_mission_cta_label ?? '');
    $wmCtaUrl   = trim($siteSettings->wide_mission_cta_url ?? '');
    $wmHasCta   = $wmCtaLabel !== '' && $wmCtaUrl !== '';

    $wmMission = null;
    if ($siteSettings->wide_mission_show_mission) {
        $pageTtl   = $siteSettings->cacheEnabled('pages') ? $siteSettings->cacheTtl('page_item', 3600) : 0;
        $wmMission = $pageTtl > 0
            ? \Illuminate\Support\Facades\Cache::remember('page_about_motto', $pageTtl, fn () => \App\Models\Page::where('type', 'about')->value('about_motto'))
            : \App\Models\Page::where('type', 'about')->value('about_motto');
    }
    $wmMission = $wmMission ?: $siteSettings->tagline;

    $wmNavCenter  = ($siteSettings->wide_mission_nav_align ?? 'left') === 'center';
    $wmSearchNav  = (bool) ($siteSettings->wide_mission_search_in_nav ?? false);
    $wmNavStyle   = $siteSettings->wide_mission_nav_style ?? 'brand_bar';
    $wmIconsNav   = $wmNavStyle === 'icons_white';
    $wmPillsNav   = $wmNavStyle === 'pills';
    $wmDarkText   = $siteSettings->navDarkText();

    $ctaClass = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-brand px-5 text-sm font-bold text-white transition '
        . 'hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp

<div class="site-header-wide"
     x-data="{
        mobileOpen: false,
        openMenu()  { this.mobileOpen = true; setTimeout(() => this.$refs.mobilePanel?.querySelector('a, button')?.focus(), 60); },
        closeMenu(returnFocus = true) { if (! this.mobileOpen) return; this.mobileOpen = false; if (returnFocus) this.$refs.menuToggle?.focus(); },
     }"
     @keydown.escape.window="closeMenu()"
     @resize.window.debounce.150ms="if (window.innerWidth >= 1024) closeMenu(false)">

    {{-- Układ „bar": numer konta i „Wesprzyj" w osobnym pasku nad belką --}}
    @if ($wmLayout === 'bar')
        <div class="hidden border-b border-brand/15 bg-brand-light/50 sm:block">
            <div class="mx-auto flex max-w-6xl justify-end px-4 py-1">
                @include('partials.wide-support-line', ['onBar' => true])
            </div>
        </div>
    @endif

    {{-- Belka główna: logo · misja · social + CTA · hamburger --}}
    <div class="border-b border-gray-100 bg-white">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3 sm:gap-6 sm:py-4">

            {{-- Logo + nazwa --}}
            <a href="{{ site_route('home') }}"
               class="flex min-w-0 flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                         class="h-14 w-auto max-w-[12rem] rounded object-contain sm:h-16 sm:max-w-[14rem]">
                @else
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-brand text-xl font-bold text-white sm:h-14 sm:w-14" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-lg font-bold text-ink sm:text-xl">{{ $siteSettings->site_name }}</span>
                        @if ($siteSettings->tagline && ! $siteSettings->wide_mission_show_mission)
                            <span class="hidden text-xs font-medium text-muted sm:block">{{ $siteSettings->tagline }}</span>
                        @endif
                    </span>
                @endunless
            </a>

            {{-- Misja — środek belki --}}
            @if ($wmMission && $siteSettings->wide_mission_show_mission)
                <p class="hidden flex-1 text-center text-sm font-medium leading-snug text-muted md:block">{{ $wmMission }}</p>
            @else
                <span class="flex-1" aria-hidden="true"></span>
            @endif

            {{-- Prawa kolumna (≥ md): wybrane social + CTA, a w układzie „right" pod nimi konto i „Wesprzyj" --}}
            <div class="hidden flex-none flex-col items-end gap-1 md:flex">
                @if ($wmSocials || $wmHasCta)
                    <div class="flex items-center gap-2">
                        @if ($wmSocials)
                            <ul class="flex items-center" aria-label="Media społecznościowe">
                                @foreach ($wmSocials as [$socialUrl, $socialIcon, $socialLabel])
                                    <li>
                                        <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                                           class="flex h-11 w-11 items-center justify-center rounded-full text-xl text-muted transition hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                                           aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                            <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($wmHasCta)
                            <a href="{{ $wmCtaUrl }}" class="{{ $ctaClass }}">{{ $wmCtaLabel }}</a>
                        @endif
                    </div>
                @endif
                @if ($wmLayout === 'right')
                    @include('partials.wide-support-line')
                @endif
            </div>

            {{-- Hamburger (< lg) --}}
            <button type="button" x-ref="menuToggle"
                    @click="mobileOpen ? closeMenu(false) : openMenu()"
                    class="ml-auto flex h-11 w-11 flex-none items-center justify-center rounded-lg border border-gray-200 text-xl text-ink transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 lg:hidden"
                    aria-controls="main-nav-panel"
                    :aria-expanded="mobileOpen.toString()"
                    :aria-label="mobileOpen ? 'Zamknij menu' : 'Otwórz menu'">
                <i class="fa-solid" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- Pasek nawigacji (≥ lg) --}}
    @if ($wmIconsNav || $wmPillsNav)
        {{-- Substyle na białym pasku: ikony nad etykietami albo zakładki (pigułki) --}}
        <nav aria-label="Menu główne" class="relative hidden border-t-4 border-t-brand bg-white shadow-sm lg:block">
            <div @class(['mx-auto flex max-w-6xl items-stretch px-4', 'py-1.5' => $wmPillsNav, 'justify-center' => $wmNavCenter])>
                @include('partials.main-nav-items', ['onBrand' => false, 'navStyle' => $wmPillsNav ? 'pills' : 'icons'])
                @if ($wmSearchNav)
                    <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu" class="ml-auto flex shrink-0 items-center py-1.5">
                        <label for="nav-search" class="sr-only">Wyszukaj w serwisie</label>
                        <input id="nav-search" type="search" name="q" value="{{ request('q') }}" placeholder="Szukaj…" autocomplete="off"
                               class="min-h-10 w-40 rounded-l-md border border-gray-300 px-3 text-sm placeholder:text-gray-600 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand">
                        <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-r-md border border-l-0 border-gray-300 bg-white text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand" aria-label="Szukaj">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif
            </div>
        </nav>
    @else
        {{-- Substyl domyślny: pasek w kolorze marki --}}
        <nav aria-label="Menu główne" class="relative hidden bg-brand shadow-sm lg:block">
            <div @class(['mx-auto flex max-w-6xl items-center px-4', 'justify-center' => $wmNavCenter])>
                @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $wmDarkText])
                @if ($wmSearchNav)
                    <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu" class="ml-auto flex shrink-0 items-center py-1.5">
                        <label for="nav-search" class="sr-only">Wyszukaj w serwisie</label>
                        <input id="nav-search" type="search" name="q" value="{{ request('q') }}" placeholder="Szukaj…" autocomplete="off"
                               class="min-h-10 w-40 rounded-l-md border-0 bg-white/15 px-3 text-sm {{ $wmDarkText ? 'text-gray-900 placeholder:text-gray-800' : 'text-white placeholder:text-white/85' }} focus:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white">
                        <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-r-md bg-white/15 {{ $wmDarkText ? 'text-gray-900' : 'text-white' }} hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white" aria-label="Szukaj">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif
            </div>
        </nav>
    @endif

    {{-- Panel mobilny (< lg): menu + wsparcie + CTA + social --}}
    <nav id="main-nav-panel" x-ref="mobilePanel" x-show="mobileOpen" x-cloak
         aria-label="Menu główne"
         @click.outside="if (! $refs.menuToggle.contains($event.target)) closeMenu(false)"
         class="border-t border-gray-200 bg-white shadow-lg lg:hidden">
        <div class="px-4 pb-4">
            @include('partials.main-nav-items', ['mobile' => true])
        </div>

        @if ($wmHasCta || filled($siteSettings->bank_account_number) || \Illuminate\Support\Facades\Route::has('support.show'))
            <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4">
                @if ($wmHasCta)
                    <a href="{{ $wmCtaUrl }}" class="{{ $ctaClass }} w-full">{{ $wmCtaLabel }}</a>
                @endif
                @include('partials.wide-support-line')
            </div>
        @endif

        @if ($socials)
            <div class="border-t border-gray-100 px-4 py-3">
                <ul class="flex flex-wrap gap-1" aria-label="Media społecznościowe">
                    @foreach ($socials as [$socialUrl, $socialIcon, $socialLabel])
                        <li>
                            <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                               class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 text-lg text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                               aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </nav>
</div>

@else
{{-- ─── Dotychczasowe layouty (classic / brand_bar / brand_bar_inline) ──── --}}
<div class="{{ $inlineOnBrand ? 'bg-brand border-transparent' : 'bg-white' }}" x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">
    <div class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
        <a href="{{ site_route('home') }}" class="flex items-center gap-3" aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}" class="h-12 w-auto max-w-[16rem] flex-none rounded object-contain {{ $inlineOnBrand ? 'bg-white p-1' : '' }}">
            @else
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded text-xl font-bold {{ $inlineOnBrand ? 'bg-white text-brand' : 'bg-brand text-white' }}" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            @unless ($siteSettings->showLogoOnly())
                <span class="leading-tight">
                    <span class="block text-lg font-bold {{ $inlineOnBrand ? 'text-white' : 'text-ink' }}">{{ $siteSettings->site_name }}</span>
                    @if ($siteSettings->tagline)
                        <span class="block text-xs {{ $inlineOnBrand ? 'text-white/80' : 'text-muted' }}">{{ $siteSettings->tagline }}</span>
                    @endif
                </span>
            @endunless
        </a>

        <button type="button" class="flex min-h-11 min-w-11 items-center justify-center rounded text-xl lg:hidden {{ $inlineOnBrand ? 'text-white hover:text-white/80' : 'text-ink hover:text-brand' }}"
            @click="mobileOpen = !mobileOpen" aria-controls="main-nav-panel" :aria-expanded="mobileOpen.toString()" aria-label="Otwórz/zamknij menu">
            <i class="fa-solid" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
        </button>

        @unless ($headerLayout === 'brand_bar')
            <nav aria-label="Menu główne" class="hidden lg:block">
                @include('partials.main-nav-items', ['onBrand' => $inlineOnBrand, 'navDarkText' => $siteSettings->navDarkText()])
            </nav>
        @endunless
    </div>

    @if ($headerLayout === 'brand_bar')
        <nav aria-label="Menu główne" class="relative hidden bg-brand lg:block">
            <div class="mx-auto flex max-w-6xl justify-center px-4">
                @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
            </div>
        </nav>
    @endif

    <nav aria-label="Menu główne (mobilne)" id="main-nav-panel" x-show="mobileOpen" x-cloak
        class="border-t border-gray-200 px-4 pb-4 lg:hidden">
        @include('partials.main-nav-items', ['mobile' => true])
    </nav>
</div>
@endif
