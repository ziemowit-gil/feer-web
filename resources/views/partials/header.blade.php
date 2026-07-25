@php
    $headerLayout = $siteSettings->header_layout;
    $inlineOnBrand = $headerLayout === 'brand_bar_inline';
@endphp

<header class="  $inlineOnBrand ? 'bg-brand border-transparent' : 'bg-white' }}" x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">
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
