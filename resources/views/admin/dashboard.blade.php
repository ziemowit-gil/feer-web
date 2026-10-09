@extends('admin.layout')

@section('title', 'Pulpit')

@section('content')
    @php
        $user = auth()->user();
        $can = fn (string $module) => $siteSettings->isModuleEnabled($module) && $user->canAccessModule($module);
        $hour = (int) now()->format('G');
        $greeting = $hour < 5 || $hour >= 18 ? 'Dobry wieczór' : ($hour < 12 ? 'Dzień dobry' : 'Miłego dnia');
        $firstName = \Illuminate\Support\Str::before(trim($user->name), ' ');
        $tones = [
            'blue'   => 'bg-blue-50 text-blue-800',
            'purple' => 'bg-purple-50 text-purple-800',
            'green'  => 'bg-green-50 text-green-800',
            'amber'  => 'bg-amber-50 text-amber-900',
        ];
        $shortcuts = [];
        if ($can('news'))     $shortcuts[] = ['route' => route('admin.newsy.create'),        'label' => 'Aktualność',     'icon' => 'fa-newspaper'];
        if ($can('pages'))    $shortcuts[] = ['route' => route('admin.podstrony.create'),    'label' => 'Strona',         'icon' => 'fa-file-lines'];
        if ($can('projects')) $shortcuts[] = ['route' => route('admin.projekty.create'),     'label' => 'Działanie',        'icon' => 'fa-diagram-project'];
        if ($can('events'))   $shortcuts[] = ['route' => route('admin.wydarzenia.create'),   'label' => 'Wydarzenie',     'icon' => 'fa-calendar-days'];
        if ($can('landing'))  $shortcuts[] = ['route' => route('admin.lp.create'),           'label' => 'Landing page',   'icon' => 'fa-bullhorn'];
        if ($can('reports'))  $shortcuts[] = ['route' => route('admin.sprawozdania.create'), 'label' => 'Sprawozdanie',   'icon' => 'fa-file-invoice'];
        if (app(\App\Modules\ModuleManager::class)->isActive('blog') && $user->canAccessModule('blog'))
                              $shortcuts[] = ['route' => route('admin.wiem-feer.create'),    'label' => 'Wpis bloga',     'icon' => 'fa-feather-pointed'];
        $attentionTotal = $attention->sum('count');
    @endphp

    {{-- ── Nagłówek ───────────────────────────────────────────────── --}}
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ now()->locale('pl')->isoFormat('dddd, D MMMM YYYY') }}</p>
            <h1 class="mt-1 text-2xl font-extrabold text-ink sm:text-3xl">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $siteSettings->site_name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" target="_blank" rel="noopener"
                class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i> Podgląd strony<span class="sr-only"> (nowa karta)</span>
            </a>
            @if ($user->isAdmin())
                <a href="{{ route('admin.ustawienia.edit') }}"
                    class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-gear text-xs" aria-hidden="true"></i> Ustawienia
                </a>
            @endif
        </div>
    </header>

    {{-- ── Wymaga uwagi ───────────────────────────────────────────── --}}
    <section aria-labelledby="dash-attention" class="mb-8">
        <h2 id="dash-attention" class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Wymaga uwagi</h2>
        @if ($attention->isEmpty() && empty($draftCounts))
            <p class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-900">
                <i class="fa-solid fa-circle-check text-lg" aria-hidden="true"></i> Wszystko załatwione — nic nie czeka na Twoją decyzję.
            </p>
        @else
            <ul role="list" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($attention as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="group flex h-full items-center gap-4 rounded-xl border-2 border-brand bg-white p-4 transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <span class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-brand text-lg text-white" aria-hidden="true"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                            <span class="min-w-0">
                                <span class="block text-2xl font-extrabold leading-none text-ink">{{ $item['count'] }}</span>
                                <span class="mt-1 block text-sm font-bold text-ink">{{ $item['label'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
                @if (! empty($draftCounts))
                    <li class="rounded-xl border border-gray-200 bg-white p-4">
                        <p class="text-sm font-bold text-ink"><i class="fa-solid fa-pen-ruler mr-1.5 text-gray-500" aria-hidden="true"></i>Szkice do dokończenia</p>
                        <ul role="list" class="mt-2 space-y-1 text-sm text-ink">
                            @foreach ($draftCounts as $d)
                                <li class="flex justify-between gap-2"><span>{{ $d['label'] }}</span><span class="font-bold">{{ $d['count'] }}</span></li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            </ul>
        @endif
    </section>

    {{-- ── Liczniki modułów ───────────────────────────────────────── --}}
    <section aria-labelledby="dash-stats" class="mb-8">
        <h2 id="dash-stats" class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Zawartość serwisu</h2>
        <ul role="list" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <li>
                    <a href="{{ $stat['route'] }}" class="flex h-full items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand hover:shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-lg bg-gray-100 text-base text-gray-700" aria-hidden="true"><i class="fa-solid {{ $stat['icon'] }}"></i></span>
                        <span class="min-w-0">
                            <span class="block text-2xl font-extrabold leading-none text-ink">{{ $stat['count'] }}</span>
                            <span class="mt-1 block truncate text-sm font-bold text-ink">{{ $stat['label'] }}</span>
                            @if ($stat['sub'])<span class="block truncate text-xs text-muted">{{ $stat['sub'] }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ── Główna siatka: aktywność + panel boczny ────────────────── --}}
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">

        {{-- Aktywność ---------------------------------------------------------- --}}
        <section aria-labelledby="dash-activity" class="rounded-xl border border-gray-200 bg-white shadow-sm"
            x-data="{ f: 'all' }">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h2 id="dash-activity" class="text-base font-bold text-ink">Ostatnia aktywność</h2>
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filtr aktywności">
                    @php $filters = ['all' => 'Wszystko', 'news' => 'Aktualności', 'pages' => 'Strony', 'projects' => 'Działania', 'events' => 'Wydarzenia']; @endphp
                    @foreach ($filters as $key => $label)
                        @if ($key === 'all' || $activity->contains('type', $key))
                            <button type="button" @click="f = '{{ $key }}'" :aria-pressed="(f === '{{ $key }}').toString()"
                                class="rounded-full border px-3 py-1 text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                                :class="f === '{{ $key }}' ? 'border-ink bg-ink text-white' : 'border-gray-300 bg-white text-ink hover:bg-gray-50'">{{ $label }}</button>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($activity->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">Brak treści do pokazania.</p>
            @else
                <ol class="divide-y divide-gray-100" role="list">
                    @foreach ($activity as $a)
                        <li x-show="f === 'all' || f === '{{ $a['type'] }}'">
                            <a href="{{ $a['url'] }}" class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg text-sm {{ $tones[$a['tone']] ?? $tones['blue'] }}" aria-hidden="true"><i class="fa-solid {{ $a['icon'] }}"></i></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-ink">{{ $a['title'] }}</span>
                                    <span class="block truncate text-xs text-muted">
                                        {{ $a['label'] }} · {{ $a['at']->diffForHumans() }}@if ($a['author']) · {{ $a['author'] }}@endif
                                    </span>
                                </span>
                                @if ($a['published'])
                                    <span class="flex-none rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-900">Opublikowane</span>
                                @else
                                    <span class="flex-none rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-900">Szkic</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
                <div class="flex flex-wrap gap-4 border-t border-gray-100 px-5 py-3 text-sm">
                    @if ($can('news'))<a href="{{ route('admin.newsy.index') }}" class="font-bold text-brand hover:text-brand-dark">Wszystkie aktualności →</a>@endif
                    @if ($can('pages'))<a href="{{ route('admin.podstrony.index') }}" class="font-bold text-brand hover:text-brand-dark">Wszystkie strony →</a>@endif
                </div>
            @endif
        </section>

        {{-- Panel boczny ------------------------------------------------------- --}}
        <div class="space-y-6">

            @if ($shortcuts)
                <section aria-labelledby="dash-create" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 id="dash-create" class="mb-3 text-base font-bold text-ink">Utwórz nowe</h2>
                    <ul role="list" class="grid grid-cols-2 gap-2">
                        @foreach ($shortcuts as $s)
                            <li>
                                <a href="{{ $s['route'] }}" class="flex min-h-12 items-center gap-2.5 rounded-lg border-2 border-gray-200 bg-white px-3 text-sm font-bold text-ink transition hover:border-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <i class="fa-solid {{ $s['icon'] }} w-4 flex-none text-center text-brand" aria-hidden="true"></i>{{ $s['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($upcoming->isNotEmpty())
                <section aria-labelledby="dash-events" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 id="dash-events" class="mb-3 text-base font-bold text-ink">Najbliższe wydarzenia</h2>
                    <ul role="list" class="space-y-3">
                        @foreach ($upcoming as $ev)
                            <li>
                                <a href="{{ route('admin.wydarzenia.edit', $ev) }}" class="group flex gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <span class="flex h-12 w-12 flex-none flex-col items-center justify-center rounded-lg bg-brand-light text-brand">
                                        <span class="text-lg font-extrabold leading-none">{{ $ev->starts_at->format('j') }}</span>
                                        <span class="text-[10px] font-bold uppercase">{{ $ev->starts_at->locale('pl')->isoFormat('MMM') }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold leading-snug text-ink group-hover:text-brand">{{ $ev->title }}</span>
                                        <span class="block text-xs text-muted">{{ $ev->starts_at->format('H:i') }}@if ($ev->location) · {{ $ev->location }}@endif</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('admin.wydarzenia.index') }}" class="mt-3 inline-block text-sm font-bold text-brand hover:text-brand-dark">Wszystkie wydarzenia →</a>
                </section>
            @endif

            @if ($activePoll && $can('polls'))
                <section aria-labelledby="dash-poll" class="rounded-xl border border-brand/30 bg-brand-light/40 p-5 shadow-sm">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h2 id="dash-poll" class="text-xs font-bold uppercase tracking-wider text-brand">Aktywna ankieta</h2>
                        <a href="{{ route('admin.ankiety.edit', $activePoll) }}" class="text-xs font-bold text-brand hover:text-brand-dark">Edytuj</a>
                    </div>
                    <p class="mb-3 text-sm font-bold text-ink">{{ $activePoll->question }}</p>
                    @php $total = $activePoll->totalVotes(); @endphp
                    <ul role="list" class="space-y-2.5">
                        @foreach ($activePoll->options as $opt)
                            <li>
                                <div class="mb-1 flex justify-between gap-2 text-xs"><span class="font-medium text-ink">{{ $opt->label }}</span><span class="text-ink">{{ $opt->votes }} ({{ $opt->percent($total) }}%)</span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-white" aria-hidden="true"><div class="h-full rounded-full bg-brand" style="width: {{ $opt->percent($total) }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-muted">Łącznie głosów: {{ $total }}</p>
                </section>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm" x-data="{ open: false }">
                <h2>
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="dash-dims"
                        class="flex w-full items-center justify-between px-5 py-4 text-left text-base font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                        Zalecane wymiary grafik
                        <i class="fa-solid fa-chevron-down text-xs text-muted transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>
                </h2>
                <div id="dash-dims" x-show="open" x-cloak class="border-t border-gray-100 px-5 pb-4 pt-3">
                    <p class="mb-3 text-xs text-muted">Zdjęcia są przycinane, więc inne proporcje też zadziałają — poniższe dają najostrzejszy wygląd.</p>
                    <ul role="list" class="space-y-2 text-sm">
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo</span><span class="text-right text-muted">400×400 px, PNG/SVG</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Slajder hero</span><span class="text-right text-muted">1600×600 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">OG / udostępnianie</span><span class="text-right text-muted">1200×630 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Miniatury</span><span class="text-right text-muted">800×600 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Zdjęcie działania</span><span class="text-right text-muted">1200×500 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Galeria</span><span class="text-right text-muted">min. 600×450 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo partnera</span><span class="text-right text-muted">do 300 px, PNG</span></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
@endsection
