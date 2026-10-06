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

<nav aria-label="Sekcje stron i menu" class="mb-4 flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 text-sm font-bold">
    @if ($user->canAccessModule('pages'))
        <a href="{{ route('admin.podstrony.index') }}" @if ($pagesTreeActive) aria-current="page" @endif
            class="rounded px-3 py-1.5 {{ $pagesTreeActive ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            <i class="fa-solid fa-sitemap mr-1 text-xs" aria-hidden="true"></i>Strony
        </a>
        <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}" @if ($pagesListMode) aria-current="page" @endif
            class="rounded px-3 py-1.5 {{ $pagesListMode ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            <i class="fa-solid fa-list mr-1 text-xs" aria-hidden="true"></i>Lista stron
        </a>
        <a href="{{ route('admin.osoby.index') }}"
            class="rounded px-3 py-1.5 {{ request()->routeIs('admin.osoby.*') ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            Osoby
        </a>
    @endif
    @if ($user->isAdmin())
        <a href="{{ route('admin.pozycje-menu.index', ['location' => 'main']) }}"
            class="rounded px-3 py-1.5 {{ $activeLocation === 'main' ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            Menu główne
        </a>
        <a href="{{ route('admin.pozycje-menu.index', ['location' => 'footer']) }}"
            class="rounded px-3 py-1.5 {{ $activeLocation === 'footer' ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            Stopka
        </a>
        <a href="{{ route('admin.pozycje-menu.index', ['location' => 'bip']) }}"
            class="rounded px-3 py-1.5 {{ $activeLocation === 'bip' ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            Menu BIP
        </a>
    @endif
</nav>
