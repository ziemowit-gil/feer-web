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

        // Panel sterowania (motyw Joomla): kafle najczęstszych działań — tylko trasy, które istnieją i do których jest dostęp.
        $isAdmin = $user->isAdmin();
        $tile = fn (string $route, string $label, string $icon, bool $allowed = true) => $allowed && \Illuminate\Support\Facades\Route::has($route)
            ? ['url' => route($route), 'label' => $label, 'icon' => $icon] : null;
        $tiles = array_values(array_filter([
            $tile('admin.newsy.create', 'Nowa aktualność', 'fa-file-circle-plus', $can('news')),
            $tile('admin.newsy.index', 'Aktualności', 'fa-newspaper', $can('news')),
            $tile('admin.podstrony.index', 'Strony', 'fa-file-lines', $can('pages')),
            $tile('admin.projekty.index', 'Działania', 'fa-diagram-project', $can('projects')),
            $tile('admin.wydarzenia.index', 'Wydarzenia', 'fa-calendar-days', $can('events')),
            $tile('admin.multimedia.index', 'Multimedia', 'fa-photo-film'),
            $tile('admin.pozycje-menu.index', 'Menu', 'fa-bars', $isAdmin),
            $tile('admin.newsletter.dashboard', 'Newsletter', 'fa-envelope-open-text', $user->canAccessModule('newsletter')),
            $tile('admin.uzytkownicy.index', 'Użytkownicy', 'fa-users', $isAdmin),
            $tile('admin.ustawienia.edit', 'Ustawienia', 'fa-sliders', $isAdmin),
            $tile('admin.szablony.manage', 'Szablony', 'fa-clone', $isAdmin),
            $tile('admin.kopie.index', 'Kopie zapasowe', 'fa-box-archive', $isAdmin),
            $tile('admin.health.index', 'Kondycja serwisu', 'fa-heart-pulse', $isAdmin),
        ]));
    @endphp

    {{-- ── Nagłówek: powitanie + „Utwórz” ───────────────────────────── --}}
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ now()->locale('pl')->isoFormat('dddd, D MMMM YYYY') }}</p>
            <h1 class="mt-1 text-2xl font-extrabold text-ink sm:text-3xl">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $siteSettings->site_name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($shortcuts)
                <div class="relative" x-data="{ open: false }" @keydown.escape.stop="open = false; $refs.btn.focus()" @click.outside="open = false">
                    <button type="button" x-ref="btn" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true" aria-controls="dash-create-menu"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand-dark px-4 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Utwórz
                        <i class="fa-solid fa-chevron-down text-[0.6rem]" :class="open && 'rotate-180'" aria-hidden="true"></i>
                    </button>
                    <ul id="dash-create-menu" x-show="open" x-cloak role="list" class="absolute right-0 top-full z-20 mt-1 w-56 rounded-md bg-white py-1 shadow-xl ring-1 ring-gray-200">
                        @foreach ($shortcuts as $s)
                            <li><a href="{{ $s['route'] }}" class="flex items-center gap-3 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none">
                                <i class="fa-solid {{ $s['icon'] }} w-4 text-center text-brand-dark" aria-hidden="true"></i>{{ $s['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <a href="{{ route('home') }}" target="_blank" rel="noopener"
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-white px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i> Podgląd strony<span class="sr-only"> (nowa karta)</span>
            </a>
        </div>
    </header>

    {{-- ── Na dziś: sprawy czekające na decyzję (tylko niezerowe) ────── --}}
    <section aria-labelledby="dash-attention" class="mb-6">
        <h2 id="dash-attention" class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">Na dziś</h2>
        @if ($attention->isEmpty())
            <p class="flex items-center gap-3 rounded-md bg-brand-light px-5 py-3 text-sm font-medium text-ink">
                <i class="fa-solid fa-circle-check text-lg text-brand-dark" aria-hidden="true"></i> Wszystko załatwione — nic nie czeka na Twoją decyzję.
            </p>
        @else
            <ul role="list" class="flex flex-wrap gap-2">
                @foreach ($attention as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="inline-flex min-h-11 items-center gap-3 rounded-md bg-white py-2 pl-2 pr-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            <span class="flex h-8 min-w-8 items-center justify-center rounded-md bg-brand-dark px-2 text-sm font-extrabold text-white">{{ $item['count'] }}</span>
                            <i class="fa-solid {{ $item['icon'] }} text-brand-dark" aria-hidden="true"></i>{{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ── Panel sterowania: kafle jak „Control Panel” w Joomla ──────── --}}
    @if ($tiles)
        <section aria-labelledby="dash-tiles" class="mb-6">
            <h2 id="dash-tiles" class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">Panel sterowania</h2>
            <ul role="list" class="j-tiles">
                @foreach ($tiles as $t)
                    <li class="flex"><a href="{{ $t['url'] }}" class="j-tile w-full"><i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i><span>{{ $t['label'] }}</span></a></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ── Główna siatka ─────────────────────────────────────────────── --}}
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            {{-- Szkice do dokończenia: konkretne treści, nie same liczby --}}
            @if ($drafts->isNotEmpty())
                <section aria-labelledby="dash-drafts" class="rounded-md bg-white">
                    <div class="j-panel-head flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4">
                        <h2 id="dash-drafts" class="text-base font-bold text-ink">Do dokończenia</h2>
                        <p class="text-xs text-ink">Szkice: {{ collect($draftCounts)->map(fn ($d) => $d['label'].' '.$d['count'])->implode(' · ') }}</p>
                    </div>
                    <ul role="list" class="divide-y divide-gray-100">
                        @foreach ($drafts as $d)
                            <li>
                                <a href="{{ $d['url'] }}" class="group flex items-center gap-4 px-5 py-3 hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
                                    <i class="fa-solid {{ $d['icon'] }} w-4 flex-none text-center text-gray-500" aria-hidden="true"></i>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-ink">{{ $d['title'] }}</span>
                                        <span class="block text-xs text-muted">{{ $d['label'] }} · zmieniono {{ $d['at']->diffForHumans() }}</span>
                                    </span>
                                    <span class="flex-none text-sm font-bold text-brand-dark group-hover:underline">Dokończ<span class="sr-only">: {{ $d['title'] }}</span> →</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Aktywność ---------------------------------------------------------- --}}
            <section aria-labelledby="dash-activity" class="rounded-md bg-white"
            x-data="{ f: 'all' }">
            <div class="j-panel-head flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h2 id="dash-activity" class="text-base font-bold text-ink">Ostatnia aktywność</h2>
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filtr aktywności">
                    @php $filters = ['all' => 'Wszystko', 'news' => 'Aktualności', 'pages' => 'Strony', 'projects' => 'Działania', 'events' => 'Wydarzenia']; @endphp
                    @foreach ($filters as $key => $label)
                        @if ($key === 'all' || $activity->contains('type', $key))
                            <button type="button" @click="f = '{{ $key }}'" :aria-pressed="(f === '{{ $key }}').toString()"
                                class="rounded-full border px-3 py-1 text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"
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
                            <a href="{{ $a['url'] }}" class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg text-sm {{ $tones[$a['tone']] ?? $tones['blue'] }}" aria-hidden="true"><i class="fa-solid {{ $a['icon'] }}"></i></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-ink">{{ $a['title'] }}</span>
                                    <span class="block truncate text-xs text-muted">
                                        {{ $a['label'] }} · {{ $a['at']->diffForHumans() }}@if ($a['author']) · {{ $a['author'] }}@endif
                                    </span>
                                </span>
                                @if ($a['published'])
                                    <span class="flex-none text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Opublikowane</span>
                                @else
                                    <span class="flex-none text-xs font-bold text-ink"><i class="fa-solid fa-pen-to-square mr-1 text-muted" aria-hidden="true"></i>Szkic</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
                <div class="flex flex-wrap gap-4 border-t border-gray-100 px-5 py-3 text-sm">
                    @if ($can('news'))<a href="{{ route('admin.newsy.index') }}" class="font-bold text-brand-dark hover:text-ink">Wszystkie aktualności →</a>@endif
                    @if ($can('pages'))<a href="{{ route('admin.podstrony.index') }}" class="font-bold text-brand-dark hover:text-ink">Wszystkie strony →</a>@endif
                </div>
            @endif
        </section>

        </div>

        {{-- Panel boczny ------------------------------------------------------- --}}
        <div class="space-y-6">
            {{-- Zawartość serwisu: zwarta lista zamiast dużych kafli --}}
            <section aria-labelledby="dash-stats" class="rounded-md bg-white">
                <h2 id="dash-stats" class="j-panel-head border-b border-gray-100 px-5 py-4 text-base font-bold">Zawartość serwisu</h2>
                <ul role="list" class="divide-y divide-gray-100">
                    @foreach ($stats as $stat)
                        <li>
                            <a href="{{ $stat['route'] }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
                                <i class="fa-solid {{ $stat['icon'] }} w-4 flex-none text-center text-gray-500" aria-hidden="true"></i>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold text-ink">{{ $stat['label'] }}</span>
                                    @if ($stat['sub'])<span class="block truncate text-xs text-muted">{{ $stat['sub'] }}</span>@endif
                                </span>
                                <span class="flex-none text-base font-extrabold text-ink">{{ $stat['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            @if ($upcoming->isNotEmpty())
                <section aria-labelledby="dash-events" class="p-5 rounded-md bg-white">
                    <h2 id="dash-events" class="mb-3 text-base font-bold text-ink">Najbliższe wydarzenia</h2>
                    <ul role="list" class="space-y-3">
                        @foreach ($upcoming as $ev)
                            <li>
                                <a href="{{ route('admin.wydarzenia.edit', $ev) }}" class="group flex gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                    <span class="flex h-12 w-12 flex-none flex-col items-center justify-center rounded-lg bg-brand-light text-brand-dark">
                                        <span class="text-lg font-extrabold leading-none">{{ $ev->starts_at->format('j') }}</span>
                                        <span class="text-[10px] font-bold uppercase">{{ $ev->starts_at->locale('pl')->isoFormat('MMM') }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $ev->title }}</span>
                                        <span class="block text-xs text-muted">{{ $ev->starts_at->format('H:i') }}@if ($ev->location) · {{ $ev->location }}@endif</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('admin.wydarzenia.index') }}" class="mt-3 inline-block text-sm font-bold text-brand-dark hover:text-ink">Wszystkie wydarzenia →</a>
                </section>
            @endif

            @if ($activePoll && $can('polls'))
                <section aria-labelledby="dash-poll" class="rounded-md bg-brand-light p-5">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h2 id="dash-poll" class="text-xs font-bold uppercase tracking-wider text-brand-dark">Aktywna ankieta</h2>
                        <a href="{{ route('admin.ankiety.edit', $activePoll) }}" class="text-xs font-bold text-brand-dark hover:text-ink">Edytuj</a>
                    </div>
                    <p class="mb-3 text-sm font-bold text-ink">{{ $activePoll->question }}</p>
                    @php $total = $activePoll->totalVotes(); @endphp
                    <ul role="list" class="space-y-2.5">
                        @foreach ($activePoll->options as $opt)
                            <li>
                                <div class="mb-1 flex justify-between gap-2 text-xs"><span class="font-medium text-ink">{{ $opt->label }}</span><span class="text-ink">{{ $opt->votes }} ({{ $opt->percent($total) }}%)</span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-white" aria-hidden="true"><div class="h-full rounded-full bg-brand-dark" style="width: {{ $opt->percent($total) }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-muted">Łącznie głosów: {{ $total }}</p>
                </section>
            @endif

            <section class="rounded-md bg-white" x-data="{ open: false }">
                <h2>
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="dash-dims"
                        class="flex w-full items-center justify-between px-5 py-4 text-left text-base font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
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
