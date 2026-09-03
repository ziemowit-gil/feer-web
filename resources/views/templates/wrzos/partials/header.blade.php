@php $mobile = false; @endphp
<header x-data="{ mobileOpen: false, searchOpen: false }"
    @keydown.escape="mobileOpen = false; searchOpen = false"
    class="sticky top-0 z-40 border-b border-gray-100 bg-white">
    <div class="mx-auto flex max-w-[1400px] items-center gap-6 px-4 py-3">

        {{-- Logo --}}
        <a href="{{ site_route('home') }}"
            class="flex flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
            aria-label="{{ $siteSettings->site_name }} — strona główna">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                    class="h-12 w-auto max-w-[210px] object-contain">
            @else
                <span class="grid h-11 w-11 flex-none place-items-center rounded-lg bg-brand text-lg font-black text-white" aria-hidden="true">
                    {{ mb_substr($siteSettings->site_name, 0, 1) }}
                </span>
                @unless ($siteSettings->showLogoOnly())
                    <span class="leading-tight">
                        <span class="block text-lg font-extrabold tracking-tight text-ink">{{ $siteSettings->site_name }}</span>
                        @if ($siteSettings->tagline)
                            <span class="block max-w-[16rem] text-[0.65rem] font-bold uppercase leading-snug tracking-wide text-muted">{{ $siteSettings->tagline }}</span>
                        @endif
                    </span>
                @endunless
            @endif
        </a>

        {{-- Nawigacja (z rozwijanymi podmenu — zarządzana w Ustawienia → Menu) --}}
        <nav class="ml-auto hidden lg:block" aria-label="Nawigacja główna">
            @include('partials.main-nav-items', ['navItems' => $navItems ?? collect(), 'mobile' => false])
        </nav>

        {{-- Prawa strona: szukajka, Facebook, mobile toggle --}}
        <div class="flex flex-none items-center gap-1.5 {{ ($navItems ?? collect())->isEmpty() ? '' : 'ml-2' }} lg:ml-4">
            <div class="relative">
                <button type="button" @click="searchOpen = !searchOpen" :aria-expanded="searchOpen" aria-controls="wrzos-search-panel"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-muted transition hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Szukaj w serwisie">
                    <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                </button>
                <div id="wrzos-search-panel" x-show="searchOpen" x-cloak x-transition @click.outside="searchOpen = false"
                    class="absolute right-0 top-full z-10 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                    <form action="{{ route('search') }}" method="GET" role="search">
                        <label for="wrzos-search-input" class="sr-only">Szukaj w serwisie</label>
                        <div class="flex overflow-hidden rounded-full border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                            <input id="wrzos-search-input" type="search" name="q" value="{{ request('q') }}" placeholder="szukaj …"
                                class="flex-1 border-none bg-transparent px-3 py-1.5 text-sm focus:outline-none">
                            <button type="submit" class="flex h-8 w-9 items-center justify-center text-muted hover:text-brand" aria-label="Szukaj">
                                <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($siteSettings->facebook_url)
                <a href="{{ $siteSettings->facebook_url }}" target="_blank" rel="noopener"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-muted transition hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f text-sm" aria-hidden="true"></i>
                </a>
            @endif

            <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="wrzos-mobile-menu"
                class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-muted transition hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:hidden"
                aria-label="Menu">
                <i class="fa-solid fa-bars text-sm" x-show="!mobileOpen" aria-hidden="true"></i>
                <i class="fa-solid fa-xmark text-sm" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- Menu mobilne --}}
    <div id="wrzos-mobile-menu" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false"
        class="border-t border-gray-100 bg-white px-4 py-3 shadow-lg lg:hidden">
        @include('partials.main-nav-items', ['navItems' => $navItems ?? collect(), 'mobile' => true])
    </div>
</header>
