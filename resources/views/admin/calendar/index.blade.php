@extends('admin.layout')

@section('title', 'Kalendarz redakcyjny')

@php
    $months = [1 => 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
    $weekdays = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nie'];
    // Rodzaj wpisu rozpoznajesz po ikonie i podpisie (legenda niżej), nie po samym kolorze.
    $badge = [
        'sky' => 'bg-gray-100 text-ink',
        'amber' => 'bg-brand-light text-ink',
        'violet' => 'bg-gray-100 text-ink',
    ];
    $badgeIcon = ['amber' => 'fa-clock'];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-ink">Kalendarz redakcyjny</h1>
            <p class="text-sm text-muted">Publikacje aktualności (w tym zaplanowane) i terminy wydarzeń.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.kalendarz.index', ['month' => $prevMonth]) }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Poprzedni miesiąc">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </a>
            <span class="min-w-[10rem] text-center text-sm font-bold text-ink">{{ $months[$cursor->month] }} {{ $cursor->year }}</span>
            <a href="{{ route('admin.kalendarz.index', ['month' => $nextMonth]) }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Następny miesiąc">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('admin.kalendarz.index') }}"
                class="ml-1 rounded-md bg-white px-3 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Dziś</a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="min-w-[52rem]">
            <div class="grid grid-cols-7 gap-px rounded-t-md bg-gray-200 text-center text-xs font-bold text-ink">
                @foreach ($weekdays as $wd)
                    <div class="bg-gray-100 py-2">{{ $wd }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-px rounded-b-md bg-gray-200">
                @foreach ($days as $day)
                    <div class="min-w-0 min-h-[7rem] bg-white p-1.5 {{ $day['inMonth'] ? '' : 'bg-gray-50/60' }}">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold
                                {{ $day['isToday'] ? 'bg-brand-dark text-white' : ($day['inMonth'] ? 'text-ink' : 'text-muted') }}">
                                {{ $day['date']->day }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            @foreach ($day['items'] as $item)
                                <a href="{{ $item['url'] }}" title="{{ $item['kind'] }}: {{ $item['title'] }}"
                                    class="block truncate rounded px-1.5 py-1 text-xs font-medium hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $badge[$item['color']] ?? 'bg-gray-100 text-ink' }}">
                                    <i class="fa-solid {{ $badgeIcon[$item['color']] ?? $item['icon'] }} mr-1" aria-hidden="true"></i><span class="sr-only">{{ $item['kind'] }}: </span>{{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-4 text-xs text-ink">
        <span><i class="fa-solid fa-newspaper mr-1" aria-hidden="true"></i> Aktualność</span>
        <span><i class="fa-solid fa-clock mr-1" aria-hidden="true"></i> Zaplanowany news <span class="text-muted">(jasnoniebieskie tło)</span></span>
        <span><i class="fa-solid fa-calendar-day mr-1" aria-hidden="true"></i> Wydarzenie</span>
    </div>
@endsection
