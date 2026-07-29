@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($stats as $stat)
            <a href="{{ $stat['route'] }}" class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 hover:border-brand">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand-light text-lg text-brand">
                    <i class="fa-solid {{ $stat['icon'] }}"></i>
                </span>
                <div>
                    <p class="text-2xl font-bold text-ink">{{ $stat['count'] }}</p>
                    <p class="text-sm font-bold text-ink">{{ $stat['label'] }}</p>
                    <p class="text-xs text-muted">{{ $stat['sub'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    @php
        $can = fn (string $module) => $siteSettings->isModuleEnabled($module) && auth()->user()->canAccessModule($module);
    @endphp

    @php
        // Pasek szybkich skrótów „utwórz…" — tylko moduły dostępne dla użytkownika.
        $shortcuts = [];
        if ($can('news')) $shortcuts[] = ['route' => route('admin.newsy.create'), 'label' => 'Nowy news', 'icon' => 'fa-newspaper'];
        if ($can('events')) $shortcuts[] = ['route' => route('admin.wydarzenia.create'), 'label' => 'Nowe wydarzenie', 'icon' => 'fa-calendar-days'];
        if ($can('pages')) $shortcuts[] = ['route' => route('admin.podstrony.create'), 'label' => 'Nowa strona', 'icon' => 'fa-file-lines'];
        if ($can('landing')) $shortcuts[] = ['route' => route('admin.lp.create'), 'label' => 'Nowy landing page', 'icon' => 'fa-bullhorn'];
        if ($can('reports')) $shortcuts[] = ['route' => route('admin.sprawozdania.create'), 'label' => 'Nowe sprawozdanie', 'icon' => 'fa-file-invoice'];
        if ($siteSettings->isModuleEnabled('blog')) $shortcuts[] = ['route' => route('admin.wiem-feer.create'), 'label' => 'Nowy wpis bloga', 'icon' => 'fa-feather-pointed'];
    @endphp

    @if ($shortcuts)
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4">
            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-muted">Szybkie skróty</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($shortcuts as $s)
                    <a href="{{ $s['route'] }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold text-ink transition-colors hover:border-brand hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid {{ $s['icon'] }} text-brand" aria-hidden="true"></i> {{ $s['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($can('news') || $can('pages'))
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            @if ($can('news'))
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Ostatnie newsy</h2>
                        <a href="{{ route('admin.newsy.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark">Zobacz wszystkie</a>
                    </div>

                    @forelse ($recentNews as $news)
                        <a href="{{ route('admin.newsy.edit', $news) }}" class="flex items-center justify-between border-b border-gray-100 py-2.5 text-sm last:border-0 hover:text-brand">
                            <span class="truncate font-medium">{{ $news->title }}</span>
                            <span class="ml-3 flex-none text-xs text-muted">{{ $news->published_at?->format('d.m.Y') }}</span>
                        </a>
                    @empty
                        <p class="py-2 text-sm text-muted">Brak newsów.</p>
                    @endforelse

                    <a href="{{ route('admin.newsy.create') }}" class="mt-4 inline-block text-sm font-bold text-brand hover:text-brand-dark">
                        <i class="fa-solid fa-plus"></i> Dodaj news
                    </a>
                </div>
            @endif

            @if ($can('pages'))
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Ostatnie strony</h2>
                        <a href="{{ route('admin.podstrony.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark">Zobacz wszystkie</a>
                    </div>

                    @forelse ($recentPages as $page)
                        <a href="{{ route('admin.podstrony.edit', $page) }}" class="flex items-center justify-between border-b border-gray-100 py-2.5 text-sm last:border-0 hover:text-brand">
                            <span class="truncate font-medium">{{ $page->title }}</span>
                            <span class="ml-3 flex-none">
                                @if ($page->is_published)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowana</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                                @endif
                            </span>
                        </a>
                    @empty
                        <p class="py-2 text-sm text-muted">Brak stron.</p>
                    @endforelse

                    <a href="{{ route('admin.podstrony.create') }}" class="mt-4 inline-block text-sm font-bold text-brand hover:text-brand-dark">
                        <i class="fa-solid fa-plus"></i> Dodaj stronę
                    </a>
                </div>
            @endif
        </div>
    @endif

    @if ($activePoll && $can('polls'))
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Aktywna ankieta</h2>
                <a href="{{ route('admin.ankiety.edit', $activePoll) }}" class="text-sm font-bold text-brand hover:text-brand-dark">Edytuj</a>
            </div>
            <p class="mb-3 text-sm font-bold text-ink">{{ $activePoll->question }}</p>
            @php $total = $activePoll->totalVotes(); @endphp
            <div class="space-y-2">
                @foreach ($activePoll->options as $option)
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-muted">
                            <span>{{ $option->label }}</span>
                            <span>{{ $option->votes }} ({{ $option->percent($total) }}%)</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-brand" style="width: {{ $option->percent($total) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="mb-1 text-sm font-bold uppercase tracking-wide text-muted">Zalecane wymiary grafik</h2>
        <p class="mb-4 text-xs text-muted">Wszystkie zdjęcia są przycinane do miejsca docelowego, więc inne proporcje też zadziałają — poniższe wymiary dają najostrzejszy, nieprzycięty wygląd.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="py-2 pr-4">Miejsce</th>
                        <th class="py-2">Zalecany rozmiar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="py-2 pr-4 font-medium">Logo (nagłówek)</td><td class="py-2 text-muted">~400×400 px, kwadrat (PNG z przezroczystością lub SVG)</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Slajder hero</td><td class="py-2 text-muted">1600×600 px (szerokie, format ok. 8:3)</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Obrazek OG (udostępnianie)</td><td class="py-2 text-muted">1200×630 px</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Miniatury aktualności / projektów</td><td class="py-2 text-muted">800×600 px (4:3) lub 1200×800 px</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Zdjęcie na stronie projektu</td><td class="py-2 text-muted">1200×500 px (szerokie)</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Galeria na stronie głównej</td><td class="py-2 text-muted">min. 600×450 px</td></tr>
                    <tr><td class="py-2 pr-4 font-medium">Logo partnera</td><td class="py-2 text-muted">do 300 px szerokości, PNG z przezroczystością</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
