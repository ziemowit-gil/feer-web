@php
    $headerLayout = $siteSettings->header_layout;
    $inlineOnBrand = $headerLayout === 'brand_bar_inline';
    $wideMission   = $headerLayout === 'wide_mission';
@endphp

@if ($wideMission)
{{-- ─── Layout: Szeroka belka (logo | misja | social) ───────────────────── --}}
<header x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">

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
            @if ($siteSettings->tagline)
                <p class="hidden flex-1 text-center text-sm font-medium leading-snug text-muted md:block">
                    {{ $siteSettings->tagline }}
                </p>
            @else
                <span class="flex-1" aria-hidden="true"></span>
            @endif

            {{-- Social media --}}
            @php
                $socials = array_filter([
                    'facebook'  => [$siteSettings->facebook_url,  'bi bi-facebook',  'Facebook'],
                    'instagram' => [$siteSettings->instagram_url, 'bi bi-instagram', 'Instagram'],
                    'youtube'   => [$siteSettings->youtube_url,   'bi bi-youtube',   'YouTube'],
                    'linkedin'  => [$siteSettings->linkedin_url,  'bi bi-linkedin',  'LinkedIn'],
                    'twitter'   => [$siteSettings->twitter_url,   'bi bi-twitter-x', 'Twitter / X'],
                    'substack'  => [$siteSettings->substack_url,  'bi bi-substack',  'Substack'],
                ], fn ($s) => !empty($s[0]));
            @endphp

            {{-- Prawa kolumna: social + CTA (góra), konto + wesprzyj (dół) --}}
            {{-- WCAG jest w osobnym pasku na samej górze — tu go nie powtarzamy --}}
            @php $navBtn = ($navItems ?? collect())->first(fn ($i) => $i->is_button); @endphp
            <div class="hidden flex-none flex-col items-end gap-1.5 sm:flex">

                {{-- Wiersz 1: social media + separator + przycisk CTA --}}
                <div class="flex items-center gap-4">
                    @if ($socials)
                        <nav aria-label="Media społecznościowe" class="flex items-center gap-2">
                            @foreach ($socials as [$url, $icon, $label])
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    class="flex min-h-10 min-w-10 items-center justify-center rounded-full border border-gray-200 text-lg text-muted transition hover:border-brand hover:text-brand"
                                    aria-label="{{ $label }}">
                                    <i class="{{ $icon }}" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </nav>
                    @endif

                    @if ($navBtn)
                        @if ($socials)
                            <div class="h-8 w-px bg-gray-200" aria-hidden="true"></div>
                        @endif
                        <a href="{{ $navBtn->url }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <i class="fa-solid fa-hands-holding-heart text-xs" aria-hidden="true"></i>
                            {{ $navBtn->label }}
                        </a>
                    @endif
                </div>

                {{-- Wiersz 2: numer konta + link Wesprzyj --}}
                @if ($siteSettings->bank_account_number || \Illuminate\Support\Facades\Route::has('support.show'))
                    <div class="flex items-center gap-4 text-xs text-muted">
                        @if ($siteSettings->bank_account_number)
                            <span>
                                <span class="font-medium text-ink">Nr konta:</span>
                                <span class="font-mono tracking-wide">{{ $siteSettings->bank_account_number }}</span>
                            </span>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('support.show'))
                            <a href="{{ route('support.show') }}"
                                class="flex items-center gap-1 font-bold text-brand hover:text-brand-dark">
                                <i class="fa-solid fa-heart text-[10px]" aria-hidden="true"></i>
                                Wesprzyj naszą działalność
                            </a>
                        @endif
                    </div>
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

    {{-- Pasek nawigacji — ramka u dołu oddziela menu od treści strony --}}
    <nav aria-label="Menu główne" class="hidden border-b-4 border-brand-dark bg-brand lg:block">
        <div class="mx-auto flex max-w-6xl justify-center px-4">
            @include('partials.main-nav-items', ['onBrand' => true])
        </div>
    </nav>

    {{-- Mobile: misja + social + menu --}}
    <div x-show="mobileOpen" x-cloak id="main-nav-panel" class="border-t border-gray-200 bg-white lg:hidden">
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
                <button type="button" data-a11y-contrast
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand"
                    aria-pressed="false" aria-label="Wersja kontrastowa">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
                </button>
                <button type="button" data-a11y-animations
                    class="flex min-h-9 min-w-9 items-center justify-center rounded border border-gray-200 text-muted hover:border-brand hover:text-brand"
                    aria-pressed="false" aria-label="Wyłącz animacje">
                    <i class="fa-solid fa-film" aria-hidden="true"></i>
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
    </div>
</header>

@else
{{-- ─── Dotychczasowe layouty (classic / brand_bar / brand_bar_inline) ──── --}}
<header class="{{ $inlineOnBrand ? 'bg-brand border-transparent' : 'bg-white' }}" x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}" class="h-12 w-auto max-w-[16rem] flex-none rounded object-contain {{ $inlineOnBrand ? 'bg-white p-1' : '' }}">
            @else
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded text-xl font-bold {{ $inlineOnBrand ? 'bg-white text-brand' : 'bg-brand text-white' }}">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
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
                @include('partials.main-nav-items', ['onBrand' => $inlineOnBrand])
            </nav>
        @endunless
    </div>

    @if ($headerLayout === 'brand_bar')
        <nav aria-label="Menu główne" class="hidden bg-brand lg:block">
            <div class="mx-auto flex max-w-6xl justify-center px-4">
                @include('partials.main-nav-items', ['onBrand' => true])
            </div>
        </nav>
    @endif

    <nav aria-label="Menu główne (mobilne)" id="main-nav-panel" x-show="mobileOpen" x-cloak
        class="border-t border-gray-200 px-4 pb-4 lg:hidden">
        @include('partials.main-nav-items', ['mobile' => true])
    </nav>
</header>
@endif
