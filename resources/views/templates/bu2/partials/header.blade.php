{{--
    Nagłówek szablonu „bu2”: logo i nazwa witryny, „Wyszukaj” (rozwija pole; bez JS prowadzi do strony wyszukiwania),
    „A⁺⁺” (otwiera pasek dostępności: rozmiar tekstu, kontrast…), pod linią menu główne z Menu nawigacji.
    Pozycja z podpozycjami to przycisk otwierający panel na całą szerokość menu (mega menu): klik/Enter, najechanie myszą
    (z opóźnieniem przy zamykaniu), Esc zamyka i wraca na przycisk, klik obok zamyka. Na wąskim ekranie przycisk „Menu”
    rozwija to samo drzewo co menu boczne. Drzewo: App\Support\Bu2Menu (wspólne z menu bocznym i okruszkami).
--}}
@php
    $bu2Tree = \App\Support\Bu2Menu::tree();
    $bu2Logo = $siteSettings->logoUrl();
    $bu2SearchOn = \Illuminate\Support\Facades\Route::has('search');
@endphp

@include('templates.bu2.partials.base-css')

<header class="bu2 bu2-header"
        x-data="{
            open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })(),
            search: false,
            mnav: false,
        }"
        x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()"
        @keydown.escape="if (open) { open = false; $refs.a11yToggle.focus() } else if (search) { search = false; $refs.searchToggle.focus() } else if (mnav) { mnav = false; $refs.mnavToggle.focus() }">
    <div class="bu2-wrap bu2-top">
        <a class="bu2-brand" href="{{ site_home_url() }}">
            @if ($bu2Logo)
                <img src="{{ $bu2Logo }}" alt="{{ $siteSettings->showLogoOnly() ? $siteSettings->site_name.' — strona główna' : '' }}">
            @endif
            @unless ($siteSettings->showLogoOnly() && $bu2Logo)
                <span class="bu2-brand-name">{{ $siteSettings->site_name }}<span class="bu2-sr"> — strona główna</span></span>
            @endunless
        </a>

        <div class="bu2-tools">
            @if ($bu2SearchOn)
                <a href="{{ route('search') }}" class="bu2-tool" x-ref="searchToggle"
                   @click.prevent="search = ! search; if (search) $nextTick(() => $refs.searchInput.focus())"
                   @keydown.space.prevent="$el.click()"
                   :aria-expanded="search.toString()" aria-controls="bu2-search" :role="'button'">
                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.32 12.9l5.39 5.4-1.41 1.4-5.4-5.38A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg>
                    <span class="bu2-tool-label">Wyszukaj</span>
                </a>
            @endif
            <button type="button" class="bu2-tool bu2-tool-a11y" x-ref="a11yToggle" @click="open = ! open"
                    aria-expanded="false" :aria-expanded="open.toString()" aria-controls="a11y-panel">
                A<sup aria-hidden="true">++</sup><span class="bu2-sr"> Ustawienia dostępności: rozmiar tekstu i kontrast</span>
                <span class="bu2-chev" :class="open && 'is-up'" aria-hidden="true"></span>
            </button>
            <button type="button" class="bu2-burger" x-ref="mnavToggle" @click="mnav = ! mnav"
                    aria-expanded="false" :aria-expanded="mnav.toString()" aria-controls="bu2-mnav">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
                Menu
            </button>
        </div>
    </div>

    @if ($bu2SearchOn)
        <div id="bu2-search" class="bu2-search" x-show="search" x-cloak>
            <form class="bu2-wrap" action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu">
                <label for="bu2-search-q" class="bu2-sr">Szukaj w serwisie</label>
                <input id="bu2-search-q" x-ref="searchInput" type="search" name="q" value="{{ request('q') }}" placeholder="Szukaj w serwisie" autocomplete="off">
                <button type="submit" class="bu2-btn">Szukaj</button>
            </form>
        </div>
    @endif

    @include('partials.a11y-panel')

    @if ($bu2Tree !== [])
        <nav class="bu2-nav" aria-label="Menu główne"
             x-data="{
                openId: null, byHover: false, timer: null,
                hasHover: window.matchMedia('(hover: hover)').matches,
                enter(id) { if (! this.hasHover) return; clearTimeout(this.timer); if (this.openId !== id) { this.openId = id; this.byHover = true } },
                leave() { if (! this.hasHover) return; clearTimeout(this.timer); this.timer = setTimeout(() => { if (this.byHover) this.openId = null }, 300) },
                toggle(id) { clearTimeout(this.timer); if (this.openId === id && ! this.byHover) { this.openId = null } else { this.openId = id } this.byHover = false },
                close(focus) { const id = this.openId; this.openId = null; if (focus && id && this.$refs['b' + id]) this.$refs['b' + id].focus() },
             }"
             @keydown.escape="if (openId) { $event.stopPropagation(); close(true) }"
             @click.outside="openId = null" @focusout="$nextTick(() => { if (! $el.contains(document.activeElement)) openId = null })">
            <div class="bu2-wrap">
                <div class="bu2-nav-in">
                    <ul class="bu2-nav-list">
                        @foreach ($bu2Tree as $node)
                            <li class="bu2-nav-item" @if ($node['children'] !== []) @mouseenter="enter('{{ $node['id'] }}')" @mouseleave="leave()" @endif>
                                @if ($node['children'] !== [])
                                    <button type="button" class="bu2-nav-link @if ($node['open']) is-here @endif" x-ref="b{{ $node['id'] }}"
                                            @click="toggle('{{ $node['id'] }}')" aria-expanded="false"
                                            :aria-expanded="(openId === '{{ $node['id'] }}').toString()" aria-controls="bu2-mega-{{ $node['id'] }}"
                                            @if ($node['open']) aria-current="true" @endif>
                                        {{ $node['label'] }}<span class="bu2-chev" aria-hidden="true"></span>
                                    </button>
                                    <div id="bu2-mega-{{ $node['id'] }}" class="bu2-mega" x-show="openId === '{{ $node['id'] }}'" x-cloak>
                                        <ul>
                                            @foreach ($node['children'] as $child)
                                                <li>
                                                    @if ($child['url'])
                                                        <a href="{{ $child['url'] }}" @if ($child['current']) aria-current="page" @endif>{{ $child['label'] }}</a>
                                                    @else
                                                        <span>{{ $child['label'] }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($node['url'])
                                            <p class="bu2-mega-all"><a href="{{ $node['url'] }}" @if ($node['current']) aria-current="page" @endif>Przejdź do strony „{{ $node['label'] }}”</a></p>
                                        @endif
                                    </div>
                                @elseif ($node['url'])
                                    <a href="{{ $node['url'] }}" class="bu2-nav-link @if ($node['open']) is-here @endif" @if ($node['current']) aria-current="page" @endif>{{ $node['label'] }}</a>
                                @else
                                    <span class="bu2-nav-link">{{ $node['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </nav>

        <div id="bu2-mnav" class="bu2-mnav" x-show="mnav" x-cloak>
            <nav class="bu2-wrap" aria-label="Menu główne (wersja mobilna)">
                @include('templates.bu2.partials.side-tree', ['nodes' => $bu2Tree, 'depth' => 1, 'prefix' => 'm'])
            </nav>
        </div>
    @endif
</header>
