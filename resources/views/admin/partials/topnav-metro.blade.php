{{--
    Górne menu panelu w stylu „Metro” (szeroki ekran ≥ 1024 px; na telefonie zostaje szuflada z menu bocznego).
    Ciemny pasek aplikacji z grupami menu (App\Support\AdminMenu) — kliknięcie grupy otwiera pod paskiem mega menu na całą
    szerokość: tytuł grupy i bloki (WYGLĄD, STAN WITRYNY…) w kolumnach, pozycje jako kafle jak na ekranie Start Metro UI,
    podpozycje listą pod kaflem. Po prawej: Skróty (szukaj w menu — klawisz „/”, ulubione, ostatnio otwarte), witryna
    (sieć witryn, tylko admin) i konto. Wzorzec „disclosure”: przycisk z aria-expanded, panel zaraz za nim w kodzie
    (kolejność fokusu = wzrok), Escape zamyka i wraca do przycisku, kliknięcie obok zamyka. Grupa z bieżącą stroną ma
    białą kreskę u dołu i „tu jesteś” dla czytnika, bieżąca pozycja — kafel w kolorze akcentu i aria-current.
    Style: admin/partials/theme-metro (klasy mt-*). Dołączany w admin/layout tylko dla schematu „metro”.
--}}
@php
    $tnUser = auth()->user();
    $tnMenu = $adminMenu ?? \App\Support\AdminMenu::for($tnUser);
    ['entries' => $tnEntries, 'current' => $tnCurrent] = \App\Support\AdminMenu::entries($tnMenu);
    $tnQuickKey = 'admin-nav-quick:'.($tnUser?->id ?? 0).':'.($siteSettings->id ?? 0);
    // Zmienne partiali wspólnych z menu bocznym (lista witryn, menu konta).
    $authUser = $tnUser;
    $roleLabel = \App\Models\User::ROLES[$tnUser?->role] ?? 'Edytor';
    $adminSites = $tnUser?->isAdmin() ? \App\Models\SiteSetting::orderBy('parent_site_id')->orderBy('id')->get() : collect();
    $tnInitial = mb_strtoupper(mb_substr($tnUser?->name ?: $tnUser?->email, 0, 1));
@endphp
<nav id="mt-nav" class="mt-nav" aria-label="Menu panelu" x-data="megaNav()"
     @keydown.escape="close(true)" @click.outside="close(false)"
     @keydown.window="if ($event.key === '/' && $store.adminNav.topActive() && ! $event.ctrlKey && ! $event.metaKey && ! $event.altKey && ! /^(input|textarea|select)$/i.test($event.target.tagName) && ! $event.target.isContentEditable) { $event.preventDefault(); open = 'skroty'; $nextTick(() => document.getElementById('mt-search')?.focus()); }">
    <div class="mt-bar">
        <a href="{{ route('admin.dashboard') }}" class="mt-brand" title="Pulpit">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="" class="mt-brand-logo">
            @else
                <span class="mt-brand-logo mt-brand-initial" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="mt-brand-name"><span style="font-family:'Pacifico',cursive">We</span><span style="font-family:'Pacifico',cursive;font-weight:300">CMS</span><span class="sr-only"> — pulpit</span></span>
        </a>

        {{-- Aktualna witryna: zawsze widoczna zaraz za logo. Administrator przy kilku witrynach przełącza ją stąd (lista
             z admin/partials/site-switch-list), pozostali widzą samą nazwę. --}}
        <div class="mt-site-slot{{ $adminSites->count() > 1 ? ' mt-rel' : '' }}">
            @if ($adminSites->count() > 1)
                <button type="button" id="mt-btn-witryna" class="mt-tool mt-site" :class="{ 'is-open': open === 'witryna' }" @click="toggle('witryna')"
                        aria-expanded="false" :aria-expanded="(open === 'witryna').toString()" aria-controls="mt-panel-witryna" title="Aktualna witryna: {{ $siteSettings->site_name }} — zmień witrynę">
                    <i class="fa-solid {{ $siteSettings->parent_site_id ? 'fa-sitemap' : 'fa-house' }}" aria-hidden="true"></i>
                    <span class="mt-site-label"><span class="mt-site-kicker">Witryna</span><span class="mt-site-name">{{ $siteSettings->site_name }}</span></span>
                    <i class="fa-solid fa-chevron-down mt-chev" aria-hidden="true"></i>
                </button>
                <div id="mt-panel-witryna" class="mt-drop mt-drop--left" x-show="open === 'witryna'" x-cloak role="region" aria-label="Wybierz witrynę">
                    @include('admin.partials.site-switch-list')
                </div>
            @else
                <p class="mt-site mt-site--static" title="Aktualna witryna: {{ $siteSettings->site_name }}">
                    <i class="fa-solid {{ $siteSettings->parent_site_id ? 'fa-sitemap' : 'fa-house' }}" aria-hidden="true"></i>
                    <span class="mt-site-label"><span class="mt-site-kicker">Witryna</span><span class="mt-site-name">{{ $siteSettings->site_name }}</span></span>
                </p>
            @endif
        </div>

        <ul class="mt-groups" role="list">
            @foreach ($tnMenu as $group)
                @php $tnKey = $group['key']; @endphp
                <li class="mt-item">
                    <button type="button" id="mt-btn-{{ $tnKey }}" class="mt-group{{ $group['active'] ? ' is-current' : '' }}{{ ($group['premium'] ?? false) ? ' tm-premium' : '' }}"
                            :class="{ 'is-open': open === @js($tnKey) }" @click="toggle(@js($tnKey))"
                            aria-expanded="false" :aria-expanded="(open === @js($tnKey)).toString()" aria-controls="mt-panel-{{ $tnKey }}">
                        <i class="fa-solid {{ $group['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $group['label'] }}@if ($group['premium'] ?? false)<span class="sr-only"> (moduł premium)</span>@endif @if ($group['active'])<span class="sr-only"> (tu jesteś)</span>@endif</span>
                        @if ($group['badge'])
                            <span class="mt-badge" aria-label="{{ $group['badge'] }} do obsłużenia">{{ $group['badge'] > 99 ? '99+' : $group['badge'] }}</span>
                        @endif
                        <i class="fa-solid fa-chevron-down mt-chev" aria-hidden="true"></i>
                    </button>

                    <div id="mt-panel-{{ $tnKey }}" class="mt-mega" x-show="open === @js($tnKey)" x-cloak role="region" aria-labelledby="mt-btn-{{ $tnKey }}">
                        <div class="mt-mega-inner">
                            <p class="mt-mega-title" aria-hidden="true">{{ $group['label'] }}</p>
                            <div class="mt-blocks">
                                @foreach ($group['blocks'] as $block)
                                    @php $tnBlockId = 'mt-b-'.$tnKey.'-'.$loop->index; @endphp
                                    <div class="mt-block">
                                        @if ($block['heading'])<p class="mt-block-title" id="{{ $tnBlockId }}">{{ $block['heading'] }}</p>@endif
                                        <ul class="mt-tiles" role="list" @if ($block['heading']) aria-labelledby="{{ $tnBlockId }}" @endif>
                                            @foreach ($block['items'] as $item)
                                                <li class="mt-cell">
                                                    <a href="{{ $item['url'] }}" class="mt-tile{{ $item['active'] ? ' is-current' : '' }}{{ ($item['premium'] ?? false) ? ' tm-premium' : '' }}"
                                                       @if ($item['active'] && $item['children'] === []) aria-current="page" @endif>
                                                        <i class="mt-tile-icon fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                                        <span class="mt-tile-label">{{ $item['label'] }}@if ($item['premium'] ?? false)<span class="sr-only"> (moduł premium)</span>@endif</span>
                                                        @if ($item['badge'])
                                                            <span class="mt-tile-badge{{ $item['badge_tone'] === 'muted' ? ' is-muted' : '' }}" aria-label="{{ $item['badge'] }} do obsłużenia">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                                        @endif
                                                    </a>
                                                    @if ($item['children'] !== [])
                                                        <ul class="mt-children" role="list" aria-label="{{ $item['label'] }}: podpozycje">
                                                            @foreach ($item['children'] as $child)
                                                                <li><a href="{{ $child['url'] }}" @class(['is-current' => $child['active']]) @if ($child['active']) aria-current="page" @endif>{{ $child['label'] }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-tools">
            {{-- Skróty: szukaj w menu, ulubione, ostatnio otwarte (te same dane w localStorage co w menu bocznym). --}}
            <div class="mt-item mt-rel" x-data="navQuick(@js($tnQuickKey), @js($tnCurrent))">
                <button type="button" id="mt-btn-skroty" class="mt-tool" :class="{ 'is-open': open === 'skroty' }" @click="toggle('skroty'); if (open === 'skroty') $nextTick(() => document.getElementById('mt-search')?.focus())"
                        aria-expanded="false" :aria-expanded="(open === 'skroty').toString()" aria-controls="mt-panel-skroty">
                    <i class="fa-solid fa-star" aria-hidden="true"></i><span>Skróty</span>
                </button>
                <div id="mt-panel-skroty" class="mt-drop" x-show="open === 'skroty'" x-cloak role="region" aria-labelledby="mt-btn-skroty"
                     x-data="{
                        q: '',
                        entries: @js($tnEntries),
                        norm(s) { return (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l'); },
                        get results() { const n = this.norm(this.q).trim(); return n === '' ? [] : this.entries.filter(e => this.norm(e.label + ' ' + e.path).includes(n)).slice(0, 20); },
                     }">
                    <label for="mt-search" class="mt-drop-title">Szukaj w menu <span class="mt-kbd">/</span></label>
                    <input id="mt-search" type="search" x-model="q" autocomplete="off" aria-controls="mt-search-results" placeholder="np. kolory, kontakt"
                           @keydown.enter.prevent="results[0] && (window.location.href = results[0].url)" class="mt-search">
                    <div id="mt-search-results" x-show="q.trim() !== ''" role="region" aria-label="Wyniki wyszukiwania w menu">
                        <p class="sr-only" aria-live="polite" x-text="results.length + ' wyników'"></p>
                        <ul class="mt-list" role="list">
                            <template x-for="r in results" :key="r.url + r.label + r.path">
                                <li><a :href="r.url"><span class="mt-list-label" x-text="r.label"></span><span class="mt-list-path" x-text="r.path"></span></a></li>
                            </template>
                        </ul>
                        <p x-show="results.length === 0" class="mt-empty">Brak pozycji menu dla „<span x-text="q"></span>”.</p>
                    </div>
                    <div x-show="q.trim() === ''">
                        <template x-if="pins.length > 0">
                            <div>
                                <p class="mt-drop-title" id="mt-pins-h">Ulubione</p>
                                <ul class="mt-list" role="list" aria-labelledby="mt-pins-h">
                                    <template x-for="p in pins" :key="p.url">
                                        <li class="mt-list-row">
                                            <a :href="p.url"><i class="fa-solid fa-star" aria-hidden="true"></i> <span x-text="p.label"></span></a>
                                            <button type="button" @click="unpin(p.url)" class="mt-unpin"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="sr-only" x-text="'Usuń z ulubionych: ' + p.label"></span></button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <template x-if="visibleRecents.length > 0">
                            <div>
                                <p class="mt-drop-title" id="mt-recent-h">Ostatnio otwarte</p>
                                <ul class="mt-list" role="list" aria-labelledby="mt-recent-h">
                                    <template x-for="r in visibleRecents" :key="r.url">
                                        <li><a :href="r.url"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <span x-text="r.label"></span></a></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <button type="button" x-show="current" @click="togglePin()" :aria-pressed="isPinned.toString()" class="mt-pin">
                            <i class="fa-star" :class="isPinned ? 'fa-solid' : 'fa-regular'" aria-hidden="true"></i>
                            <span x-text="isPinned ? 'Usuń tę stronę z ulubionych' : 'Dodaj tę stronę do ulubionych'"></span>
                        </button>
                    </div>
                    <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>
                </div>
            </div>

            <div class="mt-item mt-rel">
                <button type="button" id="mt-btn-konto" class="mt-tool mt-user" :class="{ 'is-open': open === 'konto' }" @click="toggle('konto')"
                        aria-expanded="false" :aria-expanded="(open === 'konto').toString()" aria-controls="mt-panel-konto" title="{{ $tnUser?->name ?: $tnUser?->email }}">
                    <span class="mt-avatar" aria-hidden="true">{{ $tnInitial }}</span>
                    <span class="sr-only">Menu użytkownika: {{ $tnUser?->name ?: $tnUser?->email }}</span>
                </button>
                <div id="mt-panel-konto" class="mt-drop mt-drop--menu" x-show="open === 'konto'" x-cloak role="menu" aria-label="Menu użytkownika">
                    @include('admin.partials.user-menu-items')
                </div>
            </div>
        </div>
    </div>
</nav>
<script>
    document.addEventListener('alpine:init', () => {
        // Górne menu Metro: otwarty jest jeden panel naraz (grupa, Skróty, witryna albo konto).
        Alpine.data('megaNav', () => ({
            open: '',
            toggle(key) { this.open = this.open === key ? '' : key; },
            close(focusButton) {
                const key = this.open;
                this.open = '';
                if (focusButton && key) document.getElementById('mt-btn-' + key)?.focus();
            },
        }));
    });
</script>
