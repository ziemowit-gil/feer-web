{{--
    Górna część pulpitu w stylu Metro (schemat „Metro”, User::ADMIN_SCHEMES): powitanie, „Na dziś” i „Utwórz” jako kafle.
    Te same sekcje i dane co w klasycznym pulpicie (admin/dashboard.blade.php): $attention, $shortcuts, $greeting, $firstName;
    dolne listy (szkice, aktywność, wydarzenia) są wspólne i rysuje je dashboard.blade.php. Liczniki treści i skróty do modułów
    są w menu bocznym — pulpit zostaje przeglądem.
    Style `metro-*` i kolory kafli: admin/partials/theme-metro (+ App\Support\AdminMetro, kontrast ≥ 4,5:1 w każdym stanie).
    Kafle to zwykłe linki (kolejność fokusu = kolejność na ekranie), ikony dekoracyjne mają aria-hidden, a sens niosą etykiety.
--}}
@php
    // Kafli „Utwórz” jest najwyżej osiem (od najczęstszych); pełna lista skrótów jest w klasycznym pulpicie i w menu bocznym.
    $createTiles = array_slice($shortcuts, 0, 8);
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

{{-- Utwórz: skróty do dodawania treści dozwolone dla roli --}}
@if ($createTiles)
    <section aria-labelledby="mt-create" class="metro-group">
        <h2 id="mt-create" class="metro-group-title">Utwórz</h2>
        <ul role="list" class="metro-grid">
            @foreach ($createTiles as $s)
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
