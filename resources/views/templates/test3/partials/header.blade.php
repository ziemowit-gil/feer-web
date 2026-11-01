{{--
    Szablon „test3” (wzór: portal miejski): szary pasek górny (szybkie linki, tekst A+, kontrast, social, BIP, deklaracja
    dostępności, panel ułatwień), biały rząd z logo, szybkimi linkami i wyszukiwarką, pod nim kolorowe belki kategorii z
    rozwijanym podmenu. Pozycje menu głównego z podmenu to belki kategorii (kolor z „Koloru pozycji”, domyślnie czerwony,
    niebieski, zielony, pomarańczowy), pozycje bez podmenu to szybkie linki obok logo. Style: partials/base-css (prefiks z3-).
--}}
@php
    $navItems ??= collect();
    $z3Items = \App\Support\HeaderNav::items($navItems);
    $z3Cats = $z3Items->filter(fn ($i) => $i->flat !== [])->values();
    $z3Links = $z3Items->filter(fn ($i) => $i->flat === [])->values();
    $z3Palette = ['#d32f2f', '#1976d2', '#2e7d32', '#ef6c00'];
    $z3Bip = $siteSettings->bipHref();
    $z3Socials = $siteSettings->socialLinks(4);

    // Szybkie linki na pasku górnym: do trzech pierwszych włączonych modułów, które mają stronę publiczną.
    $z3Quick = [];
    foreach ([['jobs', 'Praca', 'praca.index'], ['events', 'Wydarzenia', 'events.index'], ['faq', 'Pytania i odpowiedzi', 'faq.index'], ['news', 'Aktualności', 'news.index']] as [$module, $label, $routeName]) {
        if ($siteSettings->isModuleEnabled($module) && \Illuminate\Support\Facades\Route::has($routeName)) {
            $z3Quick[] = [$label, site_route($routeName)];
        }
    }
    $z3Quick = array_slice($z3Quick, 0, 3);
@endphp

@include('templates.test3.partials.base-css')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>

<header class="z3 z3-header">
    <div class="z3-util"
         x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
         x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
         @keydown.escape.window="if (open) { open = false; $refs.a11yToggle.focus() }"
         @a11y-open.window="open = true; $nextTick(() => $refs.a11yToggle.focus())">
        <div class="z3-wrap z3-util-in">
            @if ($z3Quick)
                <ul class="z3-util-links" aria-label="Skróty">
                    @foreach ($z3Quick as [$label, $url])<li><a href="{{ $url }}">{{ $label }}</a></li>@endforeach
                </ul>
            @else
                <span></span>
            @endif

            <div class="z3-util-tools">
                <button type="button" class="z3-tool" data-a11y-font="up" aria-label="Powiększ tekst">A+</button>
                <button type="button" class="z3-tool" data-a11y-contrast="contrast" aria-pressed="false" aria-label="Wysoki kontrast"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
                @if ($z3Socials)
                    <span class="z3-util-sep" aria-hidden="true"></span>
                    @foreach ($z3Socials as [$url, $icon, $label])
                        <a class="z3-tool" href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }} — otwiera się w nowej karcie"><i class="{{ $icon }}" aria-hidden="true"></i></a>
                    @endforeach
                @endif
                <span class="z3-util-sep" aria-hidden="true"></span>
                @if (filled($z3Bip))
                    <a class="z3-tool" href="{{ $z3Bip }}" @if ($siteSettings->bipIsExternal()) target="_blank" rel="noopener" @endif><span>BIP</span><span class="sr-only"> — biuletyn informacji publicznej{{ $siteSettings->bipIsExternal() ? ' (otwiera się w nowej karcie)' : '' }}</span></a>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('accessibility.show'))
                    <a class="z3-tool" href="{{ route('accessibility.show') }}" aria-label="Deklaracja dostępności"><i class="fa-solid fa-universal-access" aria-hidden="true"></i></a>
                @endif
                <button type="button" class="z3-tool" x-ref="a11yToggle" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="a11y-panel"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Dostępność</span></button>
            </div>
        </div>
        @include('partials.a11y-panel')
    </div>

    <div class="z3-head">
        <div class="z3-wrap z3-head-in">
            <a class="z3-brand" href="{{ site_home_url() }}" aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">
                @endif
                @unless ($siteSettings->showLogoOnly())
                    <span class="z3-brand-name">{{ $siteSettings->site_name }}</span>
                @endunless
            </a>

            @if ($z3Links->isNotEmpty())
                <nav aria-label="Szybkie linki" style="margin-left:auto">
                    <ul class="z3-links" style="margin:0">
                        @foreach ($z3Links as $link)
                            <li><a href="{{ $link->url }}" @if ($link->current) aria-current="page" @endif>{{ $link->label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <form class="z3-search" action="{{ route('search') }}" method="GET" role="search" @if ($z3Links->isEmpty()) style="margin-left:auto" @endif>
                <label for="z3-search" class="sr-only">Wpisz szukaną frazę</label>
                <input id="z3-search" type="search" name="q" value="{{ request('q') }}" placeholder="wpisz szukaną frazę" autocomplete="off">
                <button type="submit" aria-label="Szukaj"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    @if ($z3Cats->isNotEmpty())
        <nav class="z3-cats" aria-label="Nawigacja główna">
            <ul class="z3-cats-in" style="--z3-n: {{ min($z3Cats->count(), 4) }}">
                @foreach ($z3Cats as $i => $cat)
                    @php
                        $accent = $cat->accent ?: $z3Palette[$i % count($z3Palette)];
                        $ink = $siteSettings->contrastSafeColor($accent);
                    @endphp
                    <li class="z3-cat" style="--z3-c: {{ $accent }}; --z3-ink: {{ $ink }}" x-data="{ open: false }"
                        @mouseenter="open = window.innerWidth > 900" @mouseleave="open = false"
                        @focusout="if (! $el.contains($event.relatedTarget)) open = false" @keydown.escape.stop="open = false; $refs.t.focus()">
                        <div class="z3-cat-row">
                            <i class="fa-solid fa-bars z3-bars" aria-hidden="true"></i>
                            @if ($cat->hasUrl)
                                <a class="z3-cat-label" href="{{ $cat->url }}" @if ($cat->current) aria-current="page" @endif>{{ $cat->label }}</a>
                            @else
                                <span class="z3-cat-label">{{ $cat->label }}</span>
                            @endif
                            <button type="button" x-ref="t" class="z3-cat-btn" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="z3-panel-{{ $i }}" aria-label="Podmenu: {{ $cat->label }}"><i class="fa-solid fa-circle-chevron-down" aria-hidden="true"></i></button>
                        </div>
                        <ul id="z3-panel-{{ $i }}" class="z3-panel" x-show="open" x-cloak>
                            @foreach ($cat->flat as $l)
                                <li><a href="{{ $l['url'] ?? $cat->url }}" @if ($l['current']) aria-current="page" @endif>{{ $l['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</header>
