<header x-data="{ mobileOpen: false }" @keydown.escape="mobileOpen = false">

    {{-- Górna belka: logo + tagline | social + szukaj --}}
    <div class="border-b border-gray-100 bg-white">
        <div class="mx-auto flex max-w-[1400px] items-center gap-4 px-4 py-3">

            {{-- Logo + nazwa + tagline --}}
            <a href="{{ site_route('home') }}"
               class="flex flex-none items-center gap-3"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                        class="h-16 w-auto max-w-[96px] object-contain">
                @else
                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand text-xl font-bold text-white">
                        {{ mb_substr($siteSettings->site_name, 0, 1) }}
                    </span>
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-lg font-extrabold text-brand">{{ $siteSettings->site_name }}</span>
                        @if ($siteSettings->tagline)
                            <span class="block max-w-xs text-xs font-medium leading-snug text-muted">{{ $siteSettings->tagline }}</span>
                        @endif
                    </span>
                @endunless
            </a>

            {{-- Prawa strona: wyszukiwarka + social --}}
            <div class="ml-auto flex shrink-0 items-center gap-3">

                {{-- Wyszukiwarka --}}
                <form action="{{ route('search') }}" method="GET" role="search"
                    class="hidden items-center overflow-hidden rounded-full border border-gray-300 transition focus-within:border-brand focus-within:ring-1 focus-within:ring-brand sm:flex">
                    <label for="ngo-search" class="sr-only">Wyszukaj w serwisie</label>
                    <input id="ngo-search" type="search" name="q" value="{{ request('q') }}"
                        placeholder="Szukaj…" autocomplete="off"
                        class="w-36 border-none bg-transparent px-3 py-1.5 text-sm focus:outline-none lg:w-48">
                    <button type="submit"
                        class="flex h-8 w-8 flex-none items-center justify-center text-muted hover:text-brand"
                        aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                    </button>
                </form>

                {{-- Social icons --}}
                <div class="hidden items-center gap-2 sm:flex" role="list" aria-label="Media społecznościowe">
                    @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $key => $info)
                        @php $url = $siteSettings->{$key.'_url'} ?? null; @endphp
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-muted transition hover:text-brand"
                                aria-label="{{ $info['label'] }}" role="listitem">
                                <i class="{{ $info['icon'] }}" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Przycisk mobilny --}}
                <button type="button" @click="mobileOpen = !mobileOpen"
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-brand hover:text-brand md:hidden"
                    :aria-expanded="mobileOpen" aria-controls="ngo-mobile-menu"
                    aria-label="Menu">
                    <i class="fa-solid fa-bars text-sm" x-show="!mobileOpen" aria-hidden="true"></i>
                    <i class="fa-solid fa-xmark text-sm" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
                </button>
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

                {{-- CTA w prawym rogu paska nav --}}
                @php
                    $supportUrl2 = $siteSettings->support_quick_transfer_url
                        ?: $siteSettings->support_buycoffee_url
                        ?: null;
                @endphp
                <div class="ml-auto hidden items-center gap-3 py-1 md:flex">
                    @if ($supportUrl2)
                        <a href="{{ $supportUrl2 }}" target="_blank" rel="noopener"
                            class="flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 text-xs font-extrabold text-brand transition hover:bg-white/90"
                            aria-label="Wesprzyj nas — szybki przelew">
                            <i class="fa-solid fa-heart text-[#e53935]" aria-hidden="true"></i>
                            Wesprzyj
                        </a>
                    @endif
                </div>

                {{-- Przycisk mobilny (w nav) --}}
                <div class="ml-auto py-1 md:hidden">
                    <button type="button" @click="mobileOpen = !mobileOpen"
                        class="flex h-10 w-10 items-center justify-center {{ $siteSettings->navDarkText() ? 'text-gray-900' : 'text-white' }}"
                        :aria-expanded="mobileOpen"
                        aria-label="Menu">
                        <i class="fa-solid fa-bars text-lg" x-show="!mobileOpen" aria-hidden="true"></i>
                        <i class="fa-solid fa-xmark text-lg" x-show="mobileOpen" x-cloak aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- Menu mobilne (poza nav) --}}
    <div id="ngo-mobile-menu" x-show="mobileOpen" x-cloak
        class="border-b border-gray-200 bg-white shadow-lg"
        @click.outside="mobileOpen = false">
        <div class="mx-auto max-w-[1400px] px-4 py-3 space-y-3">
            @include('partials.main-nav-items', ['mobile' => true])

            {{-- Wyszukiwarka mobilna --}}
            <form action="{{ route('search') }}" method="GET" role="search">
                <label for="ngo-search-mobile" class="sr-only">Szukaj w serwisie</label>
                <div class="flex overflow-hidden rounded-full border border-gray-300 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                    <input id="ngo-search-mobile" type="search" name="q" value="{{ request('q') }}"
                        placeholder="Szukaj…"
                        class="flex-1 border-none bg-transparent px-3 py-2 text-sm focus:outline-none">
                    <button type="submit" class="flex h-10 w-10 items-center justify-center text-muted hover:text-brand"
                        aria-label="Szukaj">
                        <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

</header>
