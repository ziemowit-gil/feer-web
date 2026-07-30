@extends('admin.layout')

@section('title', 'Kalendarz redakcyjny')

@php
    $months = [1 => 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
    $weekdays = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nie'];
    $badge = [
        'sky' => 'bg-sky-100 text-sky-800',
        'amber' => 'bg-amber-100 text-amber-900',
        'violet' => 'bg-violet-100 text-violet-800',
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-ink">Kalendarz redakcyjny</h1>
            <p class="text-sm text-muted">Publikacje aktualności (w tym zaplanowane) i terminy wydarzeń.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.kalendarz.index', ['month' => $prevMonth]) }}"
                class="rounded border border-gray-300 px-3 py-2 text-sm font-bold text-muted hover:text-brand" aria-label="Poprzedni miesiąc">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </a>
            <span class="min-w-[10rem] text-center text-sm font-bold text-ink">{{ $months[$cursor->month] }} {{ $cursor->year }}</span>
            <a href="{{ route('admin.kalendarz.index', ['month' => $nextMonth]) }}"
                class="rounded border border-gray-300 px-3 py-2 text-sm font-bold text-muted hover:text-brand" aria-label="Następny miesiąc">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('admin.kalendarz.index') }}"
                class="ml-1 rounded border border-gray-300 px-3 py-2 text-sm font-bold text-muted hover:text-brand">Dziś</a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="min-w-[52rem]">
            <div class="grid grid-cols-7 gap-px rounded-t-lg bg-gray-200 text-center text-xs font-bold text-muted">
                @foreach ($weekdays as $wd)
                    <div class="bg-gray-50 py-2">{{ $wd }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-px rounded-b-lg bg-gray-200">
                @foreach ($days as $day)
                    <div class="min-h-[7rem] bg-white p-1.5 {{ $day['inMonth'] ? '' : 'bg-gray-50/60' }}">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold
                                {{ $day['isToday'] ? 'bg-brand text-white' : ($day['inMonth'] ? 'text-ink' : 'text-gray-400') }}">
                                {{ $day['date']->day }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            @foreach ($day['items'] as $item)
                                <a href="{{ $item['url'] }}" title="{{ $item['kind'] }}: {{ $item['title'] }}"
                                    class="block truncate rounded px-1.5 py-1 text-xs font-medium hover:opacity-80 {{ $badge[$item['color']] ?? 'bg-gray-100 text-gray-700' }}">
                                    <i class="fa-solid {{ $item['icon'] }} mr-1" aria-hidden="true"></i>{{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-4 text-xs text-muted">
        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-sky-100"></span> Aktualność</span>
        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-amber-100"></span> Zaplanowany news</span>
        <span><span class="mr-1 inline-block h-3 w-3 rounded bg-violet-100"></span> Wydarzenie</span>
    </div>
@endsection
