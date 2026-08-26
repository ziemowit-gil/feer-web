@php
    $headerLayout = $siteSettings->headerLayoutValue();
    $inlineOnBrand = $headerLayout === 'brand_bar_inline';
    $wideMission   = $headerLayout === 'wide_mission';
    $wmLayout      = $siteSettings->wideMissionLayoutValue();
    $officeBar     = $headerLayout === 'office_bar';
@endphp

@if ($officeBar)
@include('partials.header-office')

@elseif ($wideMission)
{{-- ─── Layout: Szeroka belka (logo | misja | social) ───────────────────── --}}
<header x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">

    {{-- Układ „bar": numer konta i „Wesprzyj" w osobnym pasku nad belką --}}
    @if ($wmLayout === 'bar')
        <div class="hidden border-b border-brand/15 bg-brand-light/50 sm:block">
            <div class="mx-auto flex max-w-6xl justify-end px-4 py-1.5">
                @include('partials.wide-support-line', ['onBar' => true])
            </div>
        </div>
    @endif

    {{-- Górna belka: logo · misja · social --}}
    <div class="border-b border-gray-100 bg-white">
        <div class="mx-auto flex max-w-6xl items-center gap-6 px-4 py-4">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex flex-none items-center gap-3" aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                        class="h-14 w-auto max-w-[12rem] rounded object-contain">
                @else
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded bg-brand text-xl font-bold text-white">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="hidden leading-tight sm:block">
                        <span class="block font-bold text-ink">{{ $siteSettings->site_name }}</span>
                    </span>
                @endunless
            </a>

            {{-- Misja — centralna część --}}
            @php
                $wmMission = null;
                if ($siteSettings->wide_mission_show_mission) {
                    $pageTtl   = $siteSettings->cacheEnabled('pages') ? $siteSettings->cacheTtl('page_item', 3600) : 0;
                    $wmMission = $pageTtl > 0
                        ? \Illuminate\Support\Facades\Cache::remember('page_about_motto', $pageTtl, fn () => \App\Models\Page::where('type', 'about')->value('about_motto'))
                        : \App\Models\Page::where('type', 'about')->value('about_motto');
                }
                $wmMission = $wmMission ?: $siteSettings->tagline;
            @endphp
            @if ($wmMission)
                <p class="hidden flex-1 text-center text-sm font-medium leading-snug text-muted md:block">
                    {{ $wmMission }}
                </p>
            @else
                <span class="flex-1" aria-hidden="true"></span>
            @endif

            {{-- Social media (wszystkie — na mobile) + wybrane 3 dla desktop --}}
            @php
                $socials = $siteSettings->socialLinks();
                $wmSocials = $siteSettings->chosenSocialLinks([
                    'wide_mission_social_1', 'wide_mission_social_2', 'wide_mission_social_3',
                ]);
                $wmCtaLabel = trim($siteSettings->wide_mission_cta_label ?? '');
                $wmCtaUrl   = trim($siteSettings->wide_mission_cta_url ?? '');
            @endphp

            {{-- Prawa kolumna: wybrane social + CTA, a w układzie „right" pod nimi
                 numer konta i link „Wesprzyj" (w układzie „bar" stoją wyżej). --}}
            <div class="hidden flex-none flex-col items-end gap-1.5 sm:flex {{ $wmLayout === 'bar' ? 'justify-center' : '' }}">

                {{-- Wiersz 1: max 3 wybrane social media + przycisk CTA --}}
                @if ($wmSocials || ($wmCtaLabel && $wmCtaUrl))
                    <div class="flex items-center gap-2.5">
                        @include('partials.social-icons', ['socialIcons' => $wmSocials])
                        @if ($wmCtaLabel && $wmCtaUrl)
                            <a href="{{ $wmCtaUrl }}"
                                class="flex items-center gap-1.5 rounded-full bg-brand px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                {{ $wmCtaLabel }}
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Wiersz 2 (tylko układ „right"): numer konta + link Wesprzyj --}}
                @if ($wmLayout === 'right')
                    @include('partials.wide-support-line')
                @endif
            </div>

            {{-- Hamburger (mobile) --}}
            <button type="button"
                class="flex min-h-11 min-w-11 items-center justify-center rounded text-xl text-ink hover:text-brand lg:hidden"
                @click="mobileOpen = !mobileOpen"
                aria-controls="main-nav-panel"
                :aria-expanded="mobileOpen.toString()"
                aria-label="Otwórz/zamknij menu">
                <i class="fa-solid" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- Pasek nawigacji --}}
    @if (($siteSettings->wide_mission_nav_style ?? 'brand_bar') === 'icons_white')
    {{-- Substyl: biały pasek z ikonami nad etykietami --}}
    <nav aria-label="Menu główne" class="hidden border-t-4 border-t-brand bg-white shadow-sm lg:block">
        <div @class(['mx-auto max-w-6xl px-4 flex items-stretch', 'justify-center' => ($siteSettings->wide_mission_nav_align ?? 'left') === 'center'])>
            @include('partials.main-nav-items', ['onBrand' => false, 'iconsNav' => true])
            @if ($siteSettings->wide_mission_search_in_nav ?? false)
                <form action="{{ route('search') }}" method="GET" class="ml-auto flex shrink-0 items-center py-1" role="search">
                    <label for="nav-search" class="sr-only">Wyszukaj w serwisie</label>
                    <input id="nav-search" type="search" name="q" value="{{ request('q') }}" placeholder="Szukaj…" autocomplete="off"
                        class="w-36 rounded-l border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand">
                    <button type="submit" class="flex min-h-8 min-w-8 items-center justify-center rounded-r border border-l-0 border-gray-300 bg-white text-ink hover:text-brand focus-visible:outline-2 focus-visible:outline-brand" aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                    </button>
                </form>
            @endif
        </div>
    </nav>
    @else
    {{-- Substyl domyślny: pasek koloru marki --}}
    <nav aria-label="Menu główne" class="hidden border-b border-white/25 bg-brand shadow-sm lg:block">
        <div @class(['mx-auto max-w-6xl px-4 flex items-center', 'justify-center' => ($siteSettings->wide_mission_nav_align ?? 'left') === 'center'])>
            @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
            @if ($siteSettings->wide_mission_search_in_nav ?? false)
                <form action="{{ route('search') }}" method="GET" class="ml-auto flex shrink-0 items-center py-1" role="search">
                    <label for="nav-search" class="sr-only">Wyszukaj w serwisie</label>
                    <input id="nav-search" type="search" name="q" value="{{ request('q') }}" placeholder="Szukaj…" autocomplete="off"
                        class="w-36 rounded-l border-0 bg-white/15 px-3 py-1.5 text-sm text-white placeholder:text-white/60 focus:bg-white/25 focus:outline-none focus:ring-1 focus:ring-white/50">
                    <button type="submit" class="flex min-h-8 min-w-8 items-center justify-center rounded-r bg-white/15 text-white hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-white" aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                    </button>
                </form>
            @endif
        </div>
    </nav>
    @endif

    {{-- Mobile: misja + social + menu --}}
    <nav x-show="mobileOpen" x-cloak id="main-nav-panel" aria-label="Menu mobilne" class="border-t border-gray-200 bg-white lg:hidden">
        @if ($siteSettings->tagline)
            <p class="border-b border-gray-100 px-4 py-3 text-sm text-muted">{{ $siteSettings->tagline }}</p>
        @endif
        <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-4 py-3">
            @if ($socials)
                <nav aria-label="Media społecznościowe" class="flex flex-wrap gap-2">
                    @foreach ($socials as [$url, $icon, $label])
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                            class="flex min-h-10 min-w-10 items-center justify-center rounded-full border border-gray-200 text-lg text-muted hover:border-brand hover:text-brand"
                            aria-label="{{ $label }}">
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </nav>
            @endif

            <div role="group" aria-label="Ustawienia dostępności" class="flex items-center gap-2">
                <div role="group" aria-label="Rozmiar czcionki" class="flex items-center">
                    <button type="button" data-a11y-font="down"
                        class="flex min-h-9 min-w-9 items-center justify-center rounded-l border border-gray-200 text-sm text-muted hover:border-brand hover:text-brand"
                        aria-label="Zmniejsz czcionkę">A-</button>
                    <button type="button" data-a11y-font="reset"
                        class="flex min-h-9 min-w-9 items-center justify-center border-y border-gray-200 text-sm text-muted hover:border-brand hover:text-brand"
                        aria-label="Domyślny rozmiar czcionki">A</button>
                    <button type="button" data-a11y-font="up"
                        class="flex min-h-9 min-w-9 items-center justify-center rounded-r border border-gray-200 text-sm text-muted hover:border-brand hover:text-brand"
                        aria-label="Zwiększ czcionkę">A+</button>
                </div>
                <button type="button" data-a11y-contrast="contrast"
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Kontrast klasyczny">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
                </button>
                <button type="button" data-a11y-contrast="contrast-bw"
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Kontrast czarno-żółty">
                    <span class="text-xs font-black leading-none" aria-hidden="true" style="background:#000;color:#ff0;padding:1px 3px;border-radius:2px">A</span>
                </button>
                <button type="button" data-a11y-contrast="contrast-gray"
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Tryb szary">
                    <span class="text-xs font-black leading-none" aria-hidden="true" style="background:#888;color:#fff;padding:1px 3px;border-radius:2px">A</span>
                </button>
                <button type="button" data-a11y-animations
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand"
                    aria-pressed="false" aria-label="Wyłącz animacje">
                    <i class="fa-solid fa-film" aria-hidden="true"></i>
                </button>
                <button type="button" data-a11y-ls
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Zwiększ odstęp liter">
                    <i class="fa-solid fa-text-width" aria-hidden="true"></i>
                </button>
                <button type="button" data-a11y-sans
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Czcionka bezszeryfowa">
                    <i class="fa-solid fa-font" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- Numer konta + Wesprzyj (mobile) --}}
        @if ($siteSettings->bank_account_number || \Illuminate\Support\Facades\Route::has('support.show'))
            <div class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-4 py-3 text-sm">
                @if ($siteSettings->bank_account_number)
                    <span class="text-muted">
                        <span class="font-medium text-ink">Nr konta:</span>
                        <span class="font-mono">{{ $siteSettings->bank_account_number }}</span>
                    </span>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('support.show'))
                    <a href="{{ route('support.show') }}"
                        class="flex items-center gap-1.5 font-bold text-brand hover:text-brand-dark">
                        <i class="fa-solid fa-heart text-xs" aria-hidden="true"></i>
                        Wesprzyj naszą działalność
                    </a>
                @endif
            </div>
        @endif

        <div class="px-4 pb-4">
            @include('partials.main-nav-items', ['mobile' => true])
        </div>
    </nav>
</header>

@else
{{-- ─── Dotychczasowe layouty (classic / brand_bar / brand_bar_inline) ──── --}}
<header class="{{ $inlineOnBrand ? 'bg-brand border-transparent' : 'bg-white' }}" x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $siteSettings->site_name }} — strona główna">
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
        <nav aria-label="Menu główne" class="hidden bg-brand lg:block">
            <div class="mx-auto flex max-w-6xl justify-center px-4">
                @include('partials.main-nav-items', ['onBrand' => true, 'navDarkText' => $siteSettings->navDarkText()])
            </div>
        </nav>
    @endif

    <nav aria-label="Menu główne (mobilne)" id="main-nav-panel" x-show="mobileOpen" x-cloak
        class="border-t border-gray-200 px-4 pb-4 lg:hidden">
        @include('partials.main-nav-items', ['mobile' => true])
    </nav>
</header>
@endif
