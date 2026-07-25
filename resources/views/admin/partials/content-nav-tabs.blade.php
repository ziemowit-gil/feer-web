@php
    $user = auth()->user();
    $activeLocation = request()->routeIs('admin.pozycje-menu.*') ? request('location', 'main') : null;
@endphp

<nav aria-label="Sekcje stron i menu" class="mb-4 flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 text-sm font-bold">
    @if ($user->canAccessModule('pages'))
        <a href="{{ route('admin.podstrony.index') }}"
            class="rounded px-3 py-1.5 {{ request()->routeIs('admin.podstrony.*') ? 'bg-brand text-white' : 'text-muted hover:bg-gray-100' }}">
            Strony
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
    @endif
</nav>
