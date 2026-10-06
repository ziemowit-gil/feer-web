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
    $tab = fn (bool $active) => 'relative -mb-px inline-flex min-h-11 items-center gap-1.5 border-b-2 px-3.5 py-2.5 text-sm font-bold transition-colors '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand '
        . ($active ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-gray-300 hover:text-ink');
@endphp

<nav aria-label="Sekcje stron i menu" class="mb-5 flex flex-wrap items-end justify-between gap-x-4 gap-y-1 border-b border-gray-200">
    <div class="flex flex-wrap">
        @if ($user->canAccessModule('pages'))
            <a href="{{ route('admin.podstrony.index') }}" @if ($pagesTreeActive) aria-current="page" @endif class="{{ $tab($pagesTreeActive) }}">
                <i class="fa-solid fa-sitemap text-xs" aria-hidden="true"></i>Strony
            </a>
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}" @if ($pagesListMode) aria-current="page" @endif class="{{ $tab($pagesListMode) }}">
                <i class="fa-solid fa-list text-xs" aria-hidden="true"></i>Lista stron
            </a>
            <a href="{{ route('admin.osoby.index') }}" @if (request()->routeIs('admin.osoby.*')) aria-current="page" @endif class="{{ $tab(request()->routeIs('admin.osoby.*')) }}">Osoby</a>
        @endif
        @if ($user->isAdmin())
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'main']) }}" @if ($activeLocation === 'main') aria-current="page" @endif class="{{ $tab($activeLocation === 'main') }}">Menu główne</a>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'footer']) }}" @if ($activeLocation === 'footer') aria-current="page" @endif class="{{ $tab($activeLocation === 'footer') }}">Stopka</a>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => 'bip']) }}" @if ($activeLocation === 'bip') aria-current="page" @endif class="{{ $tab($activeLocation === 'bip') }}">Menu BIP</a>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-1.5 pb-1.5">
        @stack('content-tab-actions')
    </div>
</nav>
