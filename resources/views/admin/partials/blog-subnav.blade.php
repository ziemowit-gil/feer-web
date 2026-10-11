{{-- Podnawigacja ekranów bloga „Wiem FEER” w panelu (dołączana z admin.layout na trasach bloga). --}}
@php
    $blogSubnav = [
        ['label' => 'Pulpit',     'route' => 'admin.wiem.dashboard',       'active' => 'admin.wiem.dashboard'],
        ['label' => 'Wpisy',      'route' => 'admin.wiem-feer.index',      'active' => 'admin.wiem-feer.*'],
        ['label' => 'Kategorie',  'route' => 'admin.kategorie-bloga.index', 'active' => 'admin.kategorie-bloga.*'],
        ['label' => 'Autorzy',    'route' => 'admin.autorzy-bloga.index',  'active' => 'admin.autorzy-bloga.*'],
        ['label' => 'Komentarze', 'route' => 'admin.komentarze-bloga.index', 'active' => 'admin.komentarze-bloga.*'],
    ];
@endphp
<nav aria-label="Wiem FEER" class="mb-5 rounded-md bg-gray-50 px-4 py-2" data-blog-subnav>
    <ul role="list" class="flex flex-wrap items-center gap-x-1 gap-y-1 text-sm">
        @foreach ($blogSubnav as $i => $entry)
            @continue(! Route::has($entry['route']))
            @php $current = request()->routeIs($entry['active']); @endphp
            <li class="flex items-center gap-1">
                @if ($i > 0)<span class="text-muted" aria-hidden="true">·</span>@endif
                <a href="{{ route($entry['route']) }}"
                    @if ($current) aria-current="page" @endif
                    class="rounded px-2 py-1 text-ink hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $current ? 'font-extrabold underline' : 'font-medium' }}">{{ $entry['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
