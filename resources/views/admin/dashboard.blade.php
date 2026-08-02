@extends('admin.layout')

@section('title', 'Pulpit')

@section('content')
    {{-- ── Baner powitalny ─────────────────────────────────────────── --}}
    <div class="mb-6 overflow-hidden rounded-2xl bg-linear-to-br from-brand to-brand-dark px-6 py-5 text-white shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-white/60">
                    {{ now()->locale('pl')->isoFormat('dddd, D MMMM YYYY') }}
                </p>
                <h1 class="mt-0.5 text-2xl font-bold">
                    Dzień dobry, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-white/75">{{ $siteSettings->site_name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('home') }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i>
                    Podgląd strony
                </a>
                <a href="{{ route('admin.ustawienia.edit') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                    <i class="fa-solid fa-gear text-[10px]" aria-hidden="true"></i>
                    Ustawienia
                </a>
            </div>
        </div>
    </div>

    {{-- ── Statystyki ───────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        @foreach ($stats as $stat)
            <a href="{{ $stat['route'] }}"
                class="group flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-light text-brand transition group-hover:bg-brand group-hover:text-white">
                        <i class="fa-solid {{ $stat['icon'] }} text-xs" aria-hidden="true"></i>
                    </span>
                    <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition group-hover:text-brand" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-xl font-bold leading-none text-ink">{{ $stat['count'] }}</p>
                    <p class="mt-0.5 text-xs font-bold text-ink">{{ $stat['label'] }}</p>
                    @if ($stat['sub'])
                        <p class="mt-0.5 text-[11px] text-muted">{{ $stat['sub'] }}</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

    {{-- ── Główna siatka ────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">

        {{-- Lewa kolumna: ostatnia aktywność ──────────────────────── --}}
        <div class="space-y-5">

            @php
                $can = fn (string $module) => $siteSettings->isModuleEnabled($module) && auth()->user()->canAccessModule($module);
            @endphp

            @if ($recentNews->isNotEmpty() || $recentPages->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                        <h2 class="text-sm font-bold text-ink">Ostatnio edytowane</h2>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        @foreach ($recentNews->take(4) as $item)
                            <li>
                                <a href="{{ route('admin.newsy.edit', $item) }}"
                                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50">
                                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-md bg-blue-50 text-blue-500">
                                        <i class="fa-solid fa-newspaper text-[11px]" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-ink">{{ $item->title }}</span>
                                        <span class="text-xs text-muted">Aktualność · {{ $item->created_at->diffForHumans() }}</span>
                                    </span>
                                    @if ($item->is_published)
                                        <span class="flex-none rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-bold text-green-700">live</span>
                                    @else
                                        <span class="flex-none rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-400">szkic</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach

                        @foreach ($recentPages->take(4) as $item)
                            <li>
                                <a href="{{ route('admin.podstrony.edit', $item) }}"
                                    class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50">
                                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-md bg-purple-50 text-purple-500">
                                        <i class="fa-solid fa-file-lines text-[11px]" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-ink">{{ $item->title }}</span>
                                        <span class="text-xs text-muted">Strona · {{ $item->created_at->diffForHumans() }}</span>
                                    </span>
                                    @if ($item->is_published)
                                        <span class="flex-none rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-bold text-green-700">live</span>
                                    @else
                                        <span class="flex-none rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-400">szkic</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex gap-3 border-t border-gray-100 px-5 py-3">
                        @if ($can('news'))
                            <a href="{{ route('admin.newsy.index') }}" class="text-xs font-bold text-brand hover:text-brand-dark">Wszystkie aktualności →</a>
                        @endif
                        @if ($can('pages'))
                            <a href="{{ route('admin.podstrony.index') }}" class="text-xs font-bold text-brand hover:text-brand-dark">Wszystkie strony →</a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Aktywna ankieta ──────────────────────────────────── --}}
            @if ($activePoll && $can('polls'))
                <div class="rounded-xl border border-brand/30 bg-brand-light/30 p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-brand"></span>
                            </span>
                            Aktywna ankieta
                        </span>
                        <a href="{{ route('admin.ankiety.edit', $activePoll) }}"
                            class="text-xs font-bold text-brand hover:text-brand-dark">Edytuj</a>
                    </div>
                    <p class="mb-4 text-sm font-bold text-ink">{{ $activePoll->question }}</p>
                    @php $total = $activePoll->totalVotes(); @endphp
                    <div class="space-y-2.5">
                        @foreach ($activePoll->options as $opt)
                            <div>
                                <div class="mb-1 flex justify-between text-xs">
                                    <span class="font-medium text-ink">{{ $opt->label }}</span>
                                    <span class="text-muted">{{ $opt->votes }} ({{ $opt->percent($total) }}%)</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-white/70">
                                    <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $opt->percent($total) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-muted">Łącznie głosów: {{ $total }}</p>
                </div>
            @endif
        </div>

        {{-- Prawa kolumna: szybkie akcje + info ───────────────────── --}}
        <div class="space-y-5">

            {{-- Szybkie akcje --}}
            @php
                $shortcuts = [];
                if ($can('news'))       $shortcuts[] = ['route' => route('admin.newsy.create'),              'label' => 'Nowy news',           'icon' => 'fa-newspaper',      'color' => 'blue'];
                if ($can('events'))     $shortcuts[] = ['route' => route('admin.wydarzenia.create'),         'label' => 'Nowe wydarzenie',     'icon' => 'fa-calendar-days',  'color' => 'green'];
                if ($can('pages'))      $shortcuts[] = ['route' => route('admin.podstrony.create'),          'label' => 'Nowa strona',         'icon' => 'fa-file-lines',     'color' => 'purple'];
                if ($can('landing'))    $shortcuts[] = ['route' => route('admin.lp.create'),                 'label' => 'Nowy landing page',   'icon' => 'fa-bullhorn',       'color' => 'orange'];
                if ($can('reports'))    $shortcuts[] = ['route' => route('admin.sprawozdania.create'),       'label' => 'Nowe sprawozdanie',   'icon' => 'fa-file-invoice',   'color' => 'slate'];
                if ($siteSettings->isModuleEnabled('blog'))
                                        $shortcuts[] = ['route' => route('admin.wiem-feer.create'),          'label' => 'Nowy wpis bloga',     'icon' => 'fa-feather-pointed','color' => 'rose'];
            @endphp

            @if ($shortcuts)
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-3.5">
                        <h2 class="text-sm font-bold text-ink">Utwórz nowe</h2>
                    </div>
                    <ul class="divide-y divide-gray-50 p-2">
                        @foreach ($shortcuts as $s)
                            @php
                                $colors = [
                                    'blue'   => 'bg-blue-50 text-blue-500',
                                    'green'  => 'bg-green-50 text-green-600',
                                    'purple' => 'bg-purple-50 text-purple-500',
                                    'orange' => 'bg-orange-50 text-orange-500',
                                    'slate'  => 'bg-slate-100 text-slate-500',
                                    'rose'   => 'bg-rose-50 text-rose-500',
                                ];
                            @endphp
                            <li>
                                <a href="{{ $s['route'] }}"
                                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink transition hover:bg-gray-50 hover:text-brand">
                                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-md {{ $colors[$s['color']] }}">
                                        <i class="fa-solid {{ $s['icon'] }} text-[11px]" aria-hidden="true"></i>
                                    </span>
                                    {{ $s['label'] }}
                                    <i class="fa-solid fa-plus ml-auto text-[10px] text-gray-300" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Zalecane wymiary grafik ──────────────────────────── --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between px-5 py-3.5 text-left">
                    <h2 class="text-sm font-bold text-ink">Zalecane wymiary grafik</h2>
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-cloak class="border-t border-gray-100 px-5 pb-4 pt-3">
                    <p class="mb-3 text-xs text-muted">Zdjęcia są przycinane, więc inne proporcje też zadziałają — poniższe dają najostrzejszy wygląd.</p>
                    <ul class="space-y-2 text-xs">
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo</span><span class="text-right text-muted">400×400 px, PNG/SVG</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Slajder hero</span><span class="text-right text-muted">1600×600 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">OG / udostępnianie</span><span class="text-right text-muted">1200×630 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Miniatury</span><span class="text-right text-muted">800×600 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Zdjęcie projektu</span><span class="text-right text-muted">1200×500 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Galeria</span><span class="text-right text-muted">min. 600×450 px</span></li>
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo partnera</span><span class="text-right text-muted">do 300 px, PNG</span></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
@endsection
