@php
    $user = auth()->user();
    $activeLocation = request()->routeIs('admin.pozycje-menu.*') ? request('location', 'main') : null;

    // „Strony” = drzewo (domyślny sposób pracy), „Lista stron” = tabela z filtrami i operacjami
    // zbiorczymi. Lista włącza się wprost (?widok=lista) albo przez filtry serwerowe / kosz.
    $onPagesIndex = request()->routeIs('admin.podstrony.index');
    $pagesListMode = $onPagesIndex && (request('widok') === 'lista'
        || (request('widok') !== 'drzewo' && (request()->filled('q') || request()->filled('status') || (request()->filled('sort') && request('sort') !== 'default'))));
    $pagesTreeActive = request()->routeIs('admin.podstrony.*') && ! $pagesListMode;
@endphp

@php
    // Zakładka: podkreślenie w kolorze marki zamiast wypełnionej „pigułki” — spokojniejszy pasek,
    // w którym po prawej mieszczą się akcje strony (@push('content-tab-actions')).
    // Odstępy, wysokość i grubość podkreślenia są wbudowane (inline), nie klasami: pasek wygląda
    // poprawnie także wtedy, gdy po wdrożeniu nie przebudowano CSS (wcześniej zakładki zlewały się w jeden ciąg).
    $tab = fn (bool $active) => 'text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark '
        . ($active ? 'border-brand-dark text-brand-dark' : 'border-transparent text-muted hover:border-gray-300 hover:text-ink');
    $tabStyle = 'display:inline-flex;align-items:center;gap:.375rem;min-height:2.75rem;padding:.625rem .875rem;margin-bottom:-1px;border-bottom-width:2px;border-bottom-style:solid;white-space:nowrap';
@endphp

@php
    $onMenu = request()->routeIs('admin.pozycje-menu.*');
    $onBip = $activeLocation === 'bip' || request()->routeIs('admin.bip-dokumenty.*');
@endphp
{{-- Nagłówek sekcji: jeden blok z dwoma rzędami. Górny: zakładki po lewej, główna akcja widoku po prawej
     (@push('content-tab-primary'), np. „Dodaj stronę”). Dolny, doklejony do górnego: pasek narzędzi
     (@push('content-tab-actions'): widok i filtry | akcje poboczne). Style wbudowane, bez zależności od buildu CSS. --}}
<div class="content-head" style="margin-bottom:1.25rem">
<div class="content-head-row border-gray-200"
     style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:.5rem 1rem;border-bottom-width:1px;border-bottom-style:solid">
<nav aria-label="{{ $onBip ? 'Sekcje BIP' : ($onMenu ? 'Sekcje menu' : 'Sekcje stron') }}" class="content-nav">
    <div class="content-tabs" style="display:flex;flex-wrap:wrap;gap:.25rem">
        @if ($onBip)
            {{-- BIP jako osobna „witryna”: menu boczne BIP i dokumenty --}}
            @if ($user->isAdmin())
                <a href="{{ route('admin.pozycje-menu.index', ['location' => 'bip']) }}" @if ($activeLocation === 'bip') aria-current="page" @endif class="{{ $tab($activeLocation === 'bip') }}" style="{{ $tabStyle }}">
                    <i class="fa-solid fa-bars text-xs" aria-hidden="true"></i>Menu BIP
                </a>
            @endif
            @if (Route::has('admin.bip-dokumenty.index') && $user->canAccessModule('bip'))
                <a href="{{ route('admin.bip-dokumenty.index') }}" @if (request()->routeIs('admin.bip-dokumenty.*')) aria-current="page" @endif class="{{ $tab(request()->routeIs('admin.bip-dokumenty.*')) }}" style="{{ $tabStyle }}">
                    <i class="fa-solid fa-landmark text-xs" aria-hidden="true"></i>Dokumenty BIP
                </a>
            @endif
        @elseif ($onMenu)
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'main']) }}" @if ($activeLocation === 'main') aria-current="page" @endif class="{{ $tab($activeLocation === 'main') }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-window-maximize text-xs" aria-hidden="true"></i>Menu główne
            </a>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'footer']) }}" @if ($activeLocation === 'footer') aria-current="page" @endif class="{{ $tab($activeLocation === 'footer') }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-grip-lines text-xs" aria-hidden="true"></i>Stopka
            </a>
        @elseif ($user->canAccessModule('pages'))
            <a href="{{ route('admin.podstrony.index') }}" @if ($pagesTreeActive) aria-current="page" @endif class="{{ $tab($pagesTreeActive) }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-sitemap text-xs" aria-hidden="true"></i>Strony
            </a>
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}" @if ($pagesListMode) aria-current="page" @endif class="{{ $tab($pagesListMode) }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-list text-xs" aria-hidden="true"></i>Lista stron
            </a>
            <a href="{{ route('admin.osoby.index') }}" @if (request()->routeIs('admin.osoby.*')) aria-current="page" @endif class="{{ $tab(request()->routeIs('admin.osoby.*')) }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-address-card text-xs" aria-hidden="true"></i>Osoby
            </a>
        @endif
    </div>
</nav>
@hasstack('content-tab-primary')
    <div class="content-head-primary" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;padding-bottom:.375rem">
        @stack('content-tab-primary')
    </div>
@endif
</div>

@hasstack('content-tab-actions')
    <div class="content-toolbar border-gray-200 bg-gray-50"
         style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem 1.5rem;padding:.5rem .75rem;border-left-width:1px;border-right-width:1px;border-bottom-width:1px;border-style:solid;border-top-width:0;border-radius:0 0 .75rem .75rem">
        @stack('content-tab-actions')
    </div>
@endif
</div>
