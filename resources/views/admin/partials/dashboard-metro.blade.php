{{--
    Pulpit w stylu Metro (schemat „Metro”, User::ADMIN_SCHEMES): powitanie, potem grupy kafli — jak ekran startowy.
    Dane i uprawnienia te same co w klasycznym pulpicie (admin/dashboard.blade.php): $attention, $shortcuts, $tileMap, $stats.
    Style `metro-*` i kolory kafli: admin/partials/theme-metro (+ App\Support\AdminMetro, kontrast ≥ 4,5:1 w każdym stanie).
    Kafle to zwykłe linki (kolejność fokusu = kolejność na ekranie), ikony dekoracyjne mają aria-hidden, a sens niosą etykiety.
--}}
@php
    // Grupy kafli „Panelu sterowania”: tytuł, ton koloru (AdminMetro::tones), trasy z $tileMap (brak uprawnień lub trasy = kafla nie ma).
    $metroGroups = [
        ['title' => 'Treści', 'tone' => 'sky', 'routes' => ['admin.newsy.create', 'admin.newsy.index', 'admin.podstrony.index', 'admin.projekty.index', 'admin.wydarzenia.index', 'admin.multimedia.index', 'admin.newsletter.dashboard']],
        ['title' => 'Struktura i wygląd', 'tone' => 'sand', 'routes' => ['admin.pozycje-menu.index', 'admin.szablony.manage']],
        ['title' => 'Administracja', 'tone' => 'mist', 'routes' => ['admin.uzytkownicy.index', 'admin.ustawienia.edit', 'admin.kopie.index', 'admin.health.index']],
    ];
@endphp

<header class="metro-hero">
    <div>
        <p class="metro-kicker">{{ now()->locale('pl')->isoFormat('dddd, D MMMM YYYY') }}</p>
        <p class="metro-display">{{ $greeting }}, {{ $firstName }}</p>
        <p class="metro-lead">{{ $siteSettings->site_name }}</p>
    </div>
    <a href="{{ admin_site_home() }}" target="_blank" rel="noopener" class="metro-btn">
        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Podgląd strony<span class="sr-only"> (nowa karta)</span>
    </a>
</header>

{{-- Na dziś: sprawy czekające na decyzję (tylko niezerowe) --}}
<section aria-labelledby="mt-today" class="metro-group">
    <h2 id="mt-today" class="metro-group-title">Na dziś</h2>
    <ul role="list" class="metro-grid">
        @forelse ($attention as $item)
            <li class="metro-cell metro-cell--wide">
                <a href="{{ $item['url'] }}" class="metro-tile metro-tile--count" data-tone="amber">
                    <span class="metro-tile-count">{{ $item['count'] }}</span>
                    <i class="fa-solid {{ $item['icon'] }} metro-tile-icon" aria-hidden="true"></i>
                    <span class="metro-tile-label">{{ $item['label'] }}</span>
                </a>
            </li>
        @empty
            <li class="metro-cell metro-cell--wide">
                <p class="metro-tile" data-tone="paper" role="status">
                    <i class="fa-solid fa-circle-check metro-tile-icon" aria-hidden="true"></i>
                    <span class="metro-tile-label">Wszystko załatwione — nic nie czeka na Twoją decyzję.</span>
                </p>
            </li>
        @endforelse
    </ul>
</section>

{{-- Utwórz: skróty do dodawania treści --}}
@if ($shortcuts)
    <section aria-labelledby="mt-create" class="metro-group">
        <h2 id="mt-create" class="metro-group-title">Utwórz</h2>
        <ul role="list" class="metro-grid">
            @foreach ($shortcuts as $s)
                <li class="metro-cell">
                    <a href="{{ $s['route'] }}" class="metro-tile" data-tone="brand">
                        <i class="fa-solid {{ $s['icon'] }} metro-tile-icon" aria-hidden="true"></i>
                        <span class="metro-tile-badge" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                        <span class="metro-tile-label"><span class="sr-only">Utwórz: </span>{{ $s['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

{{-- Panel sterowania: najczęstsze działania w grupach --}}
@foreach ($metroGroups as $group)
    @php $groupTiles = array_values(array_filter(array_map(fn ($route) => $tileMap[$route] ?? null, $group['routes']))); @endphp
    @if ($groupTiles)
        <section aria-labelledby="mt-group-{{ $loop->index }}" class="metro-group">
            <h2 id="mt-group-{{ $loop->index }}" class="metro-group-title">{{ $group['title'] }}</h2>
            <ul role="list" class="metro-grid">
                @foreach ($groupTiles as $t)
                    <li class="metro-cell">
                        <a href="{{ $t['url'] }}" class="metro-tile" data-tone="{{ $group['tone'] }}">
                            <i class="fa-solid {{ $t['icon'] }} metro-tile-icon" aria-hidden="true"></i>
                            <span class="metro-tile-label">{{ $t['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endforeach

{{-- Zawartość serwisu: liczniki jako kafle --}}
@if (count($stats))
    <section aria-labelledby="mt-stats" class="metro-group">
        <h2 id="mt-stats" class="metro-group-title">Zawartość serwisu</h2>
        <ul role="list" class="metro-grid">
            @foreach ($stats as $stat)
                <li class="metro-cell">
                    <a href="{{ $stat['route'] }}" class="metro-tile metro-tile--count" data-tone="paper">
                        <span class="metro-tile-count">{{ $stat['count'] }}</span>
                        <i class="fa-solid {{ $stat['icon'] }} metro-tile-icon" aria-hidden="true"></i>
                        <span class="metro-tile-label">{{ $stat['label'] }}</span>
                        @if ($stat['sub'])<span class="metro-tile-sub">{{ $stat['sub'] }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
