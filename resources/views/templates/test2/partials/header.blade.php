{{--
    Szablon „test2” (wzór: strona urzędu miejskiego jednostki — biały nagłówek, pasek wyszukiwania i przycisk,
    menu w jednym rzędzie z pionowymi kreskami i niebieskim rozwijanym panelem).
    Style są w tym pliku (z prefiksem z2-), bo wdrożenie nie buduje CSS Tailwinda.
--}}
@php
    $navItems ??= collect();
    $bipHref = $siteSettings->bipHref();
    $z2Socials = $siteSettings->socialLinks(3);
    // Pozycje z flagą „mega” → panel mega menu w kolumnach; pozostałe z podpozycjami → pionowa lista.
    $z2Items = \App\Support\HeaderNav::items($navItems);
@endphp

@include('templates.test2.partials.inner')

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<style>
    .z2-header { --z2-blue: #0b4f9e; --z2-blue-dark: #083a75; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.06); position: relative; z-index: 30; }
    .z2-wrap { max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    .z2-top { display: flex; align-items: center; gap: 1.25rem; padding: .9rem 0; }
    .z2-brand { display: flex; align-items: center; gap: 1rem; text-decoration: none; color: var(--z2-blue); min-width: 0; }
    .z2-brand img { height: 3.5rem; width: auto; max-width: 5rem; object-fit: contain; }
    .z2-brand-name { font-size: clamp(1.1rem, 2.4vw, 1.9rem); font-weight: 800; text-transform: uppercase; line-height: 1.1; letter-spacing: .01em; }
    .z2-tools { margin-left: auto; display: flex; align-items: center; gap: .85rem; flex: none; }
    .z2-search { display: flex; border: 1px solid #8a94a3; background: #fff; }
    .z2-search:focus-within { outline: 2px solid var(--z2-blue); outline-offset: 1px; }
    .z2-search input { border: 0; background: transparent; padding: .5rem .75rem; font-size: .875rem; width: 10.5rem; color: #1a1a1a; }
    .z2-search input:focus { outline: none; }
    .z2-search button { border: 0; background: transparent; padding: 0 .75rem; color: var(--z2-blue); cursor: pointer; }
    .z2-btn { display: inline-flex; align-items: center; justify-content: center; background: var(--z2-blue); color: #fff; font-weight: 700; font-size: .875rem; padding: .7rem 2.2rem; text-decoration: none; }
    .z2-btn:hover { background: var(--z2-blue-dark); }
    .z2-icons { display: flex; align-items: center; gap: .35rem; }
    .z2-icons a { display: flex; height: 2.75rem; min-width: 2.75rem; align-items: center; justify-content: center; color: var(--z2-blue); font-size: 1.35rem; }
    .z2-icons a:hover { color: var(--z2-blue-dark); }
    .z2-nav, .z2-nav .z2-link, .z2-nav .z2-panel a, .z2-nav .z2-mega-panel, .z2-nav .z2-mega-panel a { font-family: Roboto, Arial, sans-serif; }
    .z2-nav { border-top: 1px solid #eef0f3; }
    .z2-nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
    .z2-nav > .z2-wrap > ul > li { position: relative; flex: 1 1 0; text-align: center; }
    .z2-nav > .z2-wrap > ul > li { background: linear-gradient(var(--z2-blue), var(--z2-blue)) left center / 1px 1.1rem no-repeat; }
    .z2-nav > .z2-wrap > ul > li:last-child { background: linear-gradient(var(--z2-blue), var(--z2-blue)) left center / 1px 1.1rem no-repeat, linear-gradient(var(--z2-blue), var(--z2-blue)) right center / 1px 1.1rem no-repeat; }
    .z2-nav .z2-wrap { position: relative; }
    .z2-nav > .z2-wrap > ul > li.z2-mega { position: static; }
    .z2-nav .z2-link { display: block; width: 100%; padding: 1rem .5rem; font-size: .9rem; color: var(--z2-blue); background: transparent; border: 0; cursor: pointer; text-decoration: none; font-family: inherit; }
    .z2-nav .z2-link:hover, .z2-nav .z2-link[aria-expanded="true"], .z2-nav .z2-link[aria-current="page"] { background: var(--z2-blue); color: #fff; font-weight: 700; }
    .z2-panel { position: absolute; left: 0; top: 100%; z-index: 40; width: 18rem; background: var(--z2-blue); text-align: left; flex-direction: column; }
    .z2-mega-panel { position: absolute; left: 0; right: 0; top: 100%; z-index: 40; background: var(--z2-blue); color: #fff; text-align: left; padding: 1.5rem 1.5rem 1rem; }
    .z2-mega-grid { display: grid; grid-template-columns: repeat(var(--z2-cols, 3), minmax(0, 1fr)); gap: 1.5rem 2rem; }
    .z2-mega-grid ul { flex-direction: column; gap: 0; }
    .z2-mega-h { display: block; margin: 0 0 .5rem; padding-bottom: .4rem; border-bottom: 2px solid #fff; color: #fff; font-weight: 700; font-size: .95rem; text-decoration: none; }
    .z2-mega-panel a { display: block; padding: .5rem .25rem; color: #fff; font-size: .85rem; font-weight: 500; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.4); }
    .z2-mega-panel a small { display: block; font-weight: 400; font-size: .75rem; opacity: 1; margin-top: .15rem; }
    .z2-mega-panel a:hover, .z2-mega-panel a:focus-visible, .z2-mega-panel a[aria-current="page"] { background: #fff; color: var(--z2-blue); }
    .z2-mega-panel .z2-mega-all { margin-top: 1rem; border: 0; font-weight: 700; text-align: right; }
    .z2-panel a { display: block; padding: .6rem 1rem; color: #fff; font-size: .85rem; font-weight: 700; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.55); margin: 0 .5rem; }
    .z2-panel a:hover, .z2-panel a:focus-visible, .z2-panel a[aria-current="page"] { background: #fff; color: var(--z2-blue); }
    .z2-burger { display: none; margin-left: auto; height: 2.75rem; width: 2.75rem; border: 1px solid var(--z2-blue); background: #fff; color: var(--z2-blue); font-size: 1.25rem; cursor: pointer; }
    .z2-mobile-only { display: none; }
    @media (max-width: 900px) {
        .z2-tools .z2-search, .z2-tools .z2-btn { display: none; }
        .z2-burger { display: block; }
        .z2-nav { display: none; }
        .z2-nav.is-open { display: block; }
        .z2-nav ul { flex-direction: column; }
        .z2-nav > .z2-wrap > ul > li { text-align: left; }
        .z2-nav > .z2-wrap > ul > li, .z2-nav > .z2-wrap > ul > li:last-child { background: none; }
        .z2-nav .z2-link { text-align: left; padding: .85rem .25rem; }
        .z2-panel, .z2-mega-panel { position: static; width: auto; padding-inline: .75rem; }
        .z2-mega-grid { grid-template-columns: 1fr; }
        .z2-mobile-only { display: block; padding: .75rem 0 1rem; }
        .z2-mobile-only .z2-search input { flex: 1; width: auto; }
    }
</style>

<header class="z2-header" x-data="{ menu: false }" @keydown.escape="menu = false">
    <div class="z2-wrap">
        <div class="z2-top">
            <a class="z2-brand" href="{{ site_home_url() }}" aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="z2-brand-name">{{ $siteSettings->site_name }}</span>
                @endunless
            </a>
            <div class="z2-tools">
                <form class="z2-search" action="{{ route('search') }}" method="GET" role="search">
                    <label for="z2-search" class="sr-only">Wyszukaj</label>
                    <input id="z2-search" type="search" name="q" value="{{ request('q') }}" placeholder="Wyszukaj" autocomplete="off">
                    <button type="submit" aria-label="Szukaj"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </form>
                <a class="z2-btn" href="{{ site_route('contact.show') }}">Kontakt</a>
                <div class="z2-icons">
                    @if (filled($bipHref))
                        <a href="{{ $bipHref }}" @if ($siteSettings->bipIsExternal()) target="_blank" rel="noopener" @endif aria-label="Biuletyn Informacji Publicznej"><span style="font-size:.8rem;font-weight:900">BIP</span></a>
                    @endif
                    @foreach ($z2Socials as [$url, $icon, $label])
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }} — otwiera się w nowej karcie"><i class="{{ $icon }}" aria-hidden="true"></i></a>
                    @endforeach
                </div>
            </div>
            <button type="button" class="z2-burger" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="z2-nav" aria-label="Menu">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <nav id="z2-nav" class="z2-nav" :class="menu ? 'is-open' : ''" aria-label="Nawigacja główna">
        <div class="z2-wrap">
            <ul>
                @foreach ($z2Items as $item)
                    @if ($item->flat !== [])
                        <li @class(['z2-has-sub', 'z2-mega' => $item->mega]) x-data="{ open: false }" @mouseenter="open = window.innerWidth > 900" @mouseleave="open = false"
                            @focusout="if (! $el.contains($event.relatedTarget)) open = false" @keydown.escape.stop="open = false; $refs.t.focus()">
                            <button type="button" x-ref="t" class="z2-link" @click="open = !open" :aria-expanded="open.toString()">
                                {{ $item->label }} <i class="fa-solid fa-chevron-down" style="font-size:.6rem" aria-hidden="true"></i>
                            </button>
                            @if ($item->mega)
                                {{-- Mega menu: panel na szerokość paska, kolumny = sekcje z nagłówkami (albo równa lista). --}}
                                <div class="z2-mega-panel" x-show="open" x-cloak>
                                    <div class="z2-mega-grid" style="--z2-cols: {{ $item->cols }}">
                                        @if ($item->grouped)
                                            @foreach ($item->sections as $sec)
                                                <div>
                                                    @if ($sec['heading'])
                                                        @if ($sec['heading']['url'])
                                                            <a class="z2-mega-h" href="{{ $sec['heading']['url'] }}" @if ($sec['heading']['current']) aria-current="page" @endif>{{ $sec['heading']['label'] }}</a>
                                                        @else
                                                            <p class="z2-mega-h">{{ $sec['heading']['label'] }}</p>
                                                        @endif
                                                    @endif
                                                    <ul>
                                                        @foreach ($sec['links'] as $l)
                                                            <li><a href="{{ $l['url'] }}" @if ($l['current']) aria-current="page" @endif>{{ $l['label'] }}@if ($l['description'])<small>{{ $l['description'] }}</small>@endif</a></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        @else
                                            @foreach (collect($item->flat)->chunk((int) ceil(count($item->flat) / $item->cols)) as $chunk)
                                                <ul>
                                                    @foreach ($chunk as $l)
                                                        <li><a href="{{ $l['url'] }}" @if ($l['current']) aria-current="page" @endif>{{ $l['label'] }}@if ($l['description'])<small>{{ $l['description'] }}</small>@endif</a></li>
                                                    @endforeach
                                                </ul>
                                            @endforeach
                                        @endif
                                    </div>
                                    <a class="z2-mega-all" href="{{ $item->url }}">Zobacz wszystko: {{ $item->label }} <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                                </div>
                            @else
                                {{-- Zwykłe podmenu: pionowa lista w jednej kolumnie. --}}
                                <ul class="z2-panel" x-show="open" x-cloak>
                                    @foreach ($item->flat as $l)
                                        <li><a href="{{ $l['url'] ?? $item->url }}" @if ($l['current']) aria-current="page" @endif>{{ $l['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @else
                        <li><a class="z2-link" href="{{ $item->url }}" @if ($item->current) aria-current="page" @endif>{{ $item->label }}</a></li>
                    @endif
                @endforeach
            </ul>
            <div class="z2-mobile-only">
                <form class="z2-search" action="{{ route('search') }}" method="GET" role="search">
                    <label for="z2-search-m" class="sr-only">Wyszukaj</label>
                    <input id="z2-search-m" type="search" name="q" placeholder="Wyszukaj" autocomplete="off">
                    <button type="submit" aria-label="Szukaj"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </form>
            </div>
        </div>
    </nav>
</header>
