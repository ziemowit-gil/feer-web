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
    $tab = fn (bool $active) => 'text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand '
        . ($active ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-gray-300 hover:text-ink');
    $tabStyle = 'display:inline-flex;align-items:center;gap:.375rem;min-height:2.75rem;padding:.625rem .875rem;margin-bottom:-1px;border-bottom-width:2px;border-bottom-style:solid;white-space:nowrap';
@endphp

<nav aria-label="Sekcje stron i menu" class="border-gray-200"
     style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.25rem 1rem;margin-bottom:1rem;border-bottom-width:1px;border-bottom-style:solid">
    <div style="display:flex;flex-wrap:wrap;gap:.125rem">
        @if ($user->canAccessModule('pages'))
            <a href="{{ route('admin.podstrony.index') }}" @if ($pagesTreeActive) aria-current="page" @endif class="{{ $tab($pagesTreeActive) }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-sitemap text-xs" aria-hidden="true"></i>Strony
            </a>
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}" @if ($pagesListMode) aria-current="page" @endif class="{{ $tab($pagesListMode) }}" style="{{ $tabStyle }}">
                <i class="fa-solid fa-list text-xs" aria-hidden="true"></i>Lista stron
            </a>
            <a href="{{ route('admin.osoby.index') }}" @if (request()->routeIs('admin.osoby.*')) aria-current="page" @endif class="{{ $tab(request()->routeIs('admin.osoby.*')) }}" style="{{ $tabStyle }}">Osoby</a>
        @endif
        @if ($user->isAdmin())
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'main']) }}" @if ($activeLocation === 'main') aria-current="page" @endif class="{{ $tab($activeLocation === 'main') }}" style="{{ $tabStyle }}">Menu główne</a>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'footer']) }}" @if ($activeLocation === 'footer') aria-current="page" @endif class="{{ $tab($activeLocation === 'footer') }}" style="{{ $tabStyle }}">Stopka</a>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'bip']) }}" @if ($activeLocation === 'bip') aria-current="page" @endif class="{{ $tab($activeLocation === 'bip') }}" style="{{ $tabStyle }}">Menu BIP</a>
        @endif
    </div>
</nav>

{{-- Pasek narzędzi strony: osobny rząd pod zakładkami, w dwóch grupach (widok | akcje). Widoki wypełniają go
     przez @push('content-tab-actions'). Style wbudowane, żeby układ nie zależał od przebudowy CSS. --}}
@hasstack('content-tab-actions')
    <div class="border-gray-200 bg-gray-50"
         style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem 1.5rem;margin-bottom:1.25rem;padding:.5rem .75rem;border-width:1px;border-style:solid;border-radius:.75rem">
        @stack('content-tab-actions')
    </div>
@endif
