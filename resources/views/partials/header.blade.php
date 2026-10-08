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
    $wmCta2Label = trim($siteSettings->wide_mission_cta2_label ?? '');
    $wmCta2Url   = trim($siteSettings->wide_mission_cta2_url ?? '');
    $wmHasCta2   = $wmCta2Label !== '' && $wmCta2Url !== '';
    // Szablon FEER: gdy drugi przycisk nie jest ustawiony, nagłówek pokazuje wyraźny przycisk do materiałów edukacyjnych.
    if (! $wmHasCta2 && ($siteSettings->site_template ?? 'default') === 'feer' && $siteSettings->isModuleEnabled('materials') && ! str_contains($wmCtaUrl, 'materialy')) {
        $wmCta2Label = 'Materiały edukacyjne';
        $wmCta2Url   = site_route('materials.index');
        $wmHasCta2   = true;
    }

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
    // Szablon FEER: nowocześniejsze menu — biały pasek z pigułkami (chyba że wybrano wariant z ikonami).
    $feerNav      = ($siteSettings->site_template ?? 'default') === 'feer';
    if ($feerNav && ! $wmIconsNav) {
        $wmPillsNav = true;
    }
    $wmDarkText   = $siteSettings->navDarkText();

    // Własne kolory dwóch dodatkowych przycisków (puste = kolor marki); tekst dobiera czerń/biel dla kontrastu.
    $wmCtaColor  = \App\Support\Color::isValid($siteSettings->wide_mission_cta_color ?? null) ? strtolower($siteSettings->wide_mission_cta_color) : null;
    $wmCta2Color = \App\Support\Color::isValid($siteSettings->wide_mission_cta2_color ?? null) ? strtolower($siteSettings->wide_mission_cta2_color) : null;
    $wmCtaPal  = $wmCtaColor ? \App\Support\ThemePalette::button($wmCtaColor) : null;
    $wmCta2Pal = $wmCta2Color ? \App\Support\ThemePalette::button($wmCta2Color) : null;
    $wmCtaStyle  = $wmCtaPal ? 'background-color: '.$wmCtaPal['bg'].'; color: '.$wmCtaPal['text'].';' : null;
    $wmCta2Style = $wmCta2Pal ? 'border-color: '.$wmCta2Pal['bg'].'; color: '.$siteSettings->contrastSafeColor($wmCta2Pal['bg']).';' : null;

    $ctaClass = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-brand px-5 text-sm font-bold text-white transition '
        . 'hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp

<div class="site-header-wide" x-data="siteMobileNav(1024)" @keydown.escape.window="closeMenu()">

    {{-- Układ „bar": numer konta i „Wesprzyj" w osobnym pasku nad belką --}}
    @if ($wmLayout === 'bar' && ! $feerNav)
        <div class="hidden border-b border-brand/15 bg-brand-light/50 sm:block">
            <div class="mx-auto flex max-w-6xl justify-end px-4 py-1">
                @include('partials.wide-support-line', ['onBar' => true])
            </div>
        </div>
    @endif

    {{-- Belka główna: logo · misja · social + CTA · hamburger --}}
    <div class="border-b border-gray-100 bg-white">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 {{ $feerNav ? 'py-2 sm:py-2.5' : 'py-3 sm:py-4' }} sm:gap-6">

            {{-- Logo + nazwa --}}
            <a href="{{ site_route('home') }}"
               class="flex min-w-0 flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                         class="w-auto rounded object-contain {{ $feerNav ? 'h-14 max-w-[13rem] sm:h-[4.5rem] sm:max-w-[16rem]' : 'h-14 max-w-[12rem] sm:h-16 sm:max-w-[14rem]' }}">
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
                @php $wmShowSocials = ! empty($wmSocials); @endphp
                @if ($wmShowSocials || $wmHasCta || $wmHasCta2)
                    <div class="flex items-center gap-2">
                        @if ($wmShowSocials)
                            <ul class="flex items-center" aria-label="Media społecznościowe">
                                @foreach ($wmSocials as [$socialUrl, $socialIcon, $socialLabel])
                                    <li>
                                        <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                                           class="flex h-11 w-11 items-center justify-center text-xl transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 {{ $feerNav ? 'rounded-md text-ink hover:bg-brand-light hover:text-brand-dark' : 'rounded-full text-muted hover:bg-gray-100 hover:text-brand' }}"
                                           aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                            <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($wmHasCta)
                            <a href="{{ $wmCtaUrl }}" class="{{ $ctaClass }}" @if ($wmCtaStyle) style="{{ $wmCtaStyle }}" @endif>{{ $wmCtaLabel }}</a>
                        @endif
                        @if ($wmHasCta2)
                            {{-- Drugi przycisk: wariant drugorzędny (obrys marki, tło białe) — wyraźny, ale nie konkuruje z pierwszym. --}}
                            <a href="{{ $wmCta2Url }}" class="{{ $ctaClass }} !border-2 {{ $wmCta2Style ? '!bg-white' : '!border-brand !bg-white !text-brand-dark hover:!bg-brand-light' }}" @if ($wmCta2Style) style="{{ $wmCta2Style }}" @endif>{{ $wmCta2Label }}</a>
                        @endif
                    </div>
                @endif
                @if ($wmLayout === 'right')
                    @include('partials.wide-support-line')
                @endif
            </div>

            {{-- Hamburger (< lg) --}}
            <span class="ml-auto lg:hidden">
                @include('partials.mobile-nav-toggle', ['panelId' => 'main-nav-panel', 'onBrand' => false, 'hideAt' => 'lg'])
            </span>
        </div>
    </div>

    {{-- Pasek nawigacji (≥ lg) --}}
    @if ($wmIconsNav || $wmPillsNav)
        {{-- Substyle na białym pasku: ikony nad etykietami albo zakładki (pigułki) --}}
        <nav aria-label="Menu główne" class="relative hidden bg-white lg:block {{ $feerNav ? 'border-b border-gray-200' : 'border-t-4 border-t-brand shadow-sm' }}">
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

    {{-- Panel mobilny (< lg): menu + CTA + wsparcie + social --}}
    @include('partials.mobile-nav-panel', [
        'panelId' => 'main-nav-panel', 'hideAt' => 'lg',
        'cta' => $wmHasCta ? ['label' => $wmCtaLabel, 'url' => $wmCtaUrl, 'style' => $wmCtaStyle] : null,
        'cta2' => $wmHasCta2 ? ['label' => $wmCta2Label, 'url' => $wmCta2Url, 'style' => $wmCta2Style] : null,
        'showSupport' => true, 'socials' => $socials,
    ])
</div>

@else
{{-- ─── Układy classic / brand_bar / brand_bar_inline ─────────────────────
     Landmark <header> jest w layouts/site.blade.php. Menu mobilne: wspólny
     komponent siteMobileNav + partials/mobile-nav-toggle i mobile-nav-panel
     (fokus, Escape, kliknięcie obok, etykieta Otwórz/Zamknij) — jak w wide_mission.
--}}
<div class="{{ $inlineOnBrand ? 'bg-brand border-transparent' : 'bg-white' }}" x-data="siteMobileNav(1024)" @keydown.escape.window="closeMenu()">
    <div class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 lg:gap-12">
        <a href="{{ site_route('home') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $inlineOnBrand ? 'focus-visible:ring-white focus-visible:ring-offset-brand' : 'focus-visible:ring-brand' }}" aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}" class="h-12 w-auto max-w-[16rem] flex-none rounded object-contain {{ $inlineOnBrand ? 'bg-white p-1' : '' }}">
            @else
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded text-xl font-bold {{ $inlineOnBrand ? 'bg-white text-brand' : 'bg-brand text-white' }}" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            @unless ($siteSettings->showLogoOnly())
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-lg font-bold {{ $inlineOnBrand ? 'text-white' : 'text-ink' }}">{{ $siteSettings->site_name }}</span>
                    @if ($siteSettings->tagline)
                        <span class="block truncate text-xs {{ $inlineOnBrand ? 'text-white/90' : 'text-muted' }}">{{ $siteSettings->tagline }}</span>
                    @endif
                </span>
            @endunless
        </a>

        @include('partials.mobile-nav-toggle', ['panelId' => 'main-nav-panel', 'onBrand' => $inlineOnBrand, 'hideAt' => 'lg'])

        @php
            $feerMaterials = ($siteSettings->site_template ?? 'default') === 'feer' && $siteSettings->isModuleEnabled('materials');
            // Szablon FEER: dwa rzędy — u góry logo i przycisk, pod spodem menu na pełną szerokość (więcej miejsca na pozycje).
            $feerTwoRows = ($siteSettings->site_template ?? 'default') === 'feer' && $headerLayout !== 'brand_bar';
        @endphp
        @if ($feerTwoRows)
            @php $feerSocials = $siteSettings->socialLinks(5); @endphp
            @if ($feerMaterials || $feerSocials)
                <div class="hidden items-center gap-3 lg:flex">
                    @if ($feerSocials)
                        <ul class="flex items-center gap-1" role="list" aria-label="Media społecznościowe">
                            @foreach ($feerSocials as [$socialUrl, $socialIcon, $socialLabel])
                                <li>
                                    <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                                       class="flex h-11 w-11 items-center justify-center rounded-md text-xl text-ink transition hover:bg-brand-light hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                                       aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                                        <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($feerMaterials)
                        <a href="{{ site_route('materials.index') }}"
                           class="inline-flex min-h-11 flex-none items-center gap-2 rounded-md bg-ink px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                            Materiały edukacyjne
                        </a>
                    @endif
                </div>
            @endif
        @else
        @unless ($headerLayout === 'brand_bar')
            <div class="hidden items-center gap-6 lg:flex xl:gap-8">
                <nav aria-label="Menu główne">
                    @include('partials.main-nav-items', ['onBrand' => $inlineOnBrand, 'navDarkText' => $siteSettings->navDarkText()])
                </nav>
                @if ($feerMaterials)
                    <a href="{{ site_route('materials.index') }}"
                       class="inline-flex min-h-11 flex-none items-center gap-2 rounded-md bg-ink px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                        Materiały edukacyjne
                    </a>
                @endif
            </div>
        @endunless
        @endif
    </div>

    @if ($feerTwoRows)
        <nav aria-label="Menu główne" class="relative hidden border-y border-gray-100 bg-white lg:block">
            <div class="mx-auto max-w-6xl px-4">
                @include('partials.main-nav-items', ['onBrand' => false, 'navDarkText' => false])
            </div>
        </nav>
    @endif

    @if ($headerLayout === 'brand_bar')
        <nav aria-label="Menu główne" class="relative hidden bg-brand lg:block">
            <div class="mx-auto flex max-w-6xl justify-center px-4">
                @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
            </div>
        </nav>
    @endif

    @include('partials.mobile-nav-panel', ['panelId' => 'main-nav-panel', 'hideAt' => 'lg', 'showSupport' => true, 'socials' => $siteSettings->socialLinks(), 'cta' => $feerMaterials ? ['label' => 'Materiały edukacyjne', 'url' => site_route('materials.index')] : null])
</div>
@endif
