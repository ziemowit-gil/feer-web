{{--
    Szablon „test2” (wzór: strona urzędu miejskiego jednostki — biały nagłówek, pasek wyszukiwania i przycisk,
    menu w jednym rzędzie z pionowymi kreskami i niebieskim rozwijanym panelem).
    Style są w tym pliku (z prefiksem z2-), bo wdrożenie nie buduje CSS Tailwinda.
--}}
@php
    $navItems ??= collect();
    $bipHref = $siteSettings->bipHref();
    $z2Socials = $siteSettings->socialLinks(3);
    $z2Items = $navItems->map(function ($item) {
        $children = $item->children;
        if ($children->isEmpty() && ($page = $item->linkedPage())) {
            $children = $page->publishedChildren->map(fn ($p) => (object) ['label' => $p->title, 'url' => $p->publicUrl(), 'current' => false]);
        } else {
            $children = $children->map(fn ($c) => (object) ['label' => $c->label, 'url' => site_href($c->url), 'current' => $c->isCurrent()]);
        }

        return (object) ['label' => $item->label, 'url' => site_href($item->url), 'current' => $item->isCurrent(), 'children' => $children->values()];
    });
@endphp

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
    .z2-nav { border-top: 1px solid #eef0f3; }
    .z2-nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
    .z2-nav > .z2-wrap > ul > li { position: relative; flex: 1 1 0; text-align: center; }
    .z2-nav > .z2-wrap > ul > li::before, .z2-nav > .z2-wrap > ul > li:last-child::after { content: ""; position: absolute; top: 50%; height: 1.1rem; width: 1px; margin-top: -.55rem; background: var(--z2-blue); }
    .z2-nav > .z2-wrap > ul > li::before { left: 0; }
    .z2-nav > .z2-wrap > ul > li:last-child::after { right: 0; }
    .z2-nav .z2-link { display: block; width: 100%; padding: 1rem .5rem; font-size: .9rem; color: var(--z2-blue); background: transparent; border: 0; cursor: pointer; text-decoration: none; font-family: inherit; }
    .z2-nav .z2-link:hover, .z2-nav .z2-link[aria-expanded="true"], .z2-nav .z2-link[aria-current="page"] { background: var(--z2-blue); color: #fff; font-weight: 700; }
    .z2-panel { position: absolute; left: 0; top: 100%; z-index: 40; min-width: 18rem; max-width: 34rem; background: var(--z2-blue); text-align: left; columns: 2; column-gap: 0; }
    .z2-panel li { break-inside: avoid; }
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
        .z2-nav > .z2-wrap > ul > li::before, .z2-nav > .z2-wrap > ul > li:last-child::after { display: none; }
        .z2-nav .z2-link { text-align: left; padding: .85rem .25rem; }
        .z2-panel { position: static; columns: 1; max-width: none; }
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
                    @if ($item->children->isNotEmpty())
                        <li x-data="{ open: false }" @mouseenter="open = window.innerWidth > 900" @mouseleave="open = false"
                            @focusout="if (! $el.contains($event.relatedTarget)) open = false" @keydown.escape.stop="open = false; $refs.t.focus()">
                            <button type="button" x-ref="t" class="z2-link" @click="open = !open" :aria-expanded="open.toString()">
                                {{ $item->label }} <i class="fa-solid fa-chevron-down" style="font-size:.6rem" aria-hidden="true"></i>
                            </button>
                            <ul class="z2-panel" x-show="open" x-cloak>
                                <li><a href="{{ $item->url }}">{{ $item->label }} — wszystko</a></li>
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->url }}" @if ($child->current) aria-current="page" @endif>{{ $child->label }}</a></li>
                                @endforeach
                            </ul>
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
