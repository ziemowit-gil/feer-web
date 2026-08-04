@extends('admin.layout')

@section('title', 'Statystyki wydarzeń')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-lg font-bold text-ink">Statystyki wydarzeń</h1>
        <p class="text-sm text-muted">Zestawienie zbiorcze wszystkich szkoleń i wydarzeń w systemie.</p>
    </div>
    <a href="{{ route('admin.wydarzenia.index') }}"
        class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-3 py-2 text-sm font-bold text-muted hover:bg-gray-100">
        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> Lista wydarzeń
    </a>
</div>

{{-- ── Kafelki ogólne ─────────────────────────────────────────── --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @php
    $tiles = [
        ['label' => 'Łącznie w systemie',  'value' => $total,     'icon' => 'fa-calendar-days',     'color' => 'text-brand',   'bg' => 'bg-brand/10'],
        ['label' => 'Aktywnych',            'value' => $active,    'icon' => 'fa-calendar-check',    'color' => 'text-green-600','bg' => 'bg-green-50'],
        ['label' => 'Nadchodzących',        'value' => $upcoming,  'icon' => 'fa-hourglass-half',    'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
        ['label' => 'Zakończonych',         'value' => $past,      'icon' => 'fa-clock-rotate-left', 'color' => 'text-gray-500', 'bg' => 'bg-gray-100'],
    ];
    @endphp
    @foreach ($tiles as $tile)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg {{ $tile['bg'] }}">
                    <i class="fa-solid {{ $tile['icon'] }} {{ $tile['color'] }}" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-2xl font-bold text-ink" aria-label="{{ $tile['label'] }}: {{ $tile['value'] }}">{{ $tile['value'] }}</p>
                    <p class="text-xs text-muted">{{ $tile['label'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Siatka dwukolumnowa: typ i tryb ────────────────────────── --}}
<div class="mb-6 grid gap-4 lg:grid-cols-2">

    {{-- Rodzaj wydarzenia --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
            <i class="fa-solid fa-shapes text-brand" aria-hidden="true"></i> Po rodzaju
        </h2>
        @php
        $typeRows = [
            'szkolenie' => ['label' => 'Szkolenie',    'icon' => 'fa-chalkboard-user', 'color' => 'bg-indigo-400'],
            'warsztat'  => ['label' => 'Warsztat',     'icon' => 'fa-screwdriver-wrench','color' => 'bg-amber-400'],
            'webinar'   => ['label' => 'Webinar',      'icon' => 'fa-video',           'color' => 'bg-blue-400'],
            'wydarzenie'=> ['label' => 'Wydarzenie',   'icon' => 'fa-calendar-star',   'color' => 'bg-green-400'],
        ];
        $typeMax = $byType->max() ?: 1;
        @endphp
        <ul class="space-y-3" role="list">
            @foreach ($typeRows as $key => $row)
                @php $cnt = (int) $byType->get($key, 0); @endphp
                <li class="flex items-center gap-3">
                    <span class="flex w-5 items-center justify-center text-gray-400">
                        <i class="fa-solid {{ $row['icon'] }} text-sm" aria-hidden="true"></i>
                    </span>
                    <span class="w-24 text-sm text-ink">{{ $row['label'] }}</span>
                    <div class="flex-1 overflow-hidden rounded-full bg-gray-100" role="img" aria-label="{{ $cnt }} {{ $row['label'] }}">
                        <div class="{{ $row['color'] }} h-2 rounded-full transition-all"
                            style="width: {{ $typeMax > 0 ? round($cnt / $typeMax * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-6 text-right text-sm font-bold text-ink" aria-hidden="true">{{ $cnt }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Tryb --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
            <i class="fa-solid fa-location-dot text-brand" aria-hidden="true"></i> Po trybie
        </h2>
        @php
        $modeRows = [
            'stacjonarnie' => ['label' => 'Stacjonarnie', 'icon' => 'fa-building',  'color' => 'bg-teal-400'],
            'zdalnie'      => ['label' => 'Zdalnie',      'icon' => 'fa-wifi',      'color' => 'bg-purple-400'],
            'hybrydowo'    => ['label' => 'Hybrydowo',    'icon' => 'fa-split',     'color' => 'bg-orange-400'],
        ];
        $modeMax = $byMode->max() ?: 1;
        @endphp
        <ul class="space-y-3" role="list">
            @foreach ($modeRows as $key => $row)
                @php $cnt = (int) $byMode->get($key, 0); @endphp
                <li class="flex items-center gap-3">
                    <span class="flex w-5 items-center justify-center text-gray-400">
                        <i class="fa-solid {{ $row['icon'] }} text-sm" aria-hidden="true"></i>
                    </span>
                    <span class="w-28 text-sm text-ink">{{ $row['label'] }}</span>
                    <div class="flex-1 overflow-hidden rounded-full bg-gray-100" role="img" aria-label="{{ $cnt }} {{ $row['label'] }}">
                        <div class="{{ $row['color'] }} h-2 rounded-full transition-all"
                            style="width: {{ $modeMax > 0 ? round($cnt / $modeMax * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-6 text-right text-sm font-bold text-ink" aria-hidden="true">{{ $cnt }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- ── Wykres miesięczny ───────────────────────────────────────── --}}
<div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="mb-1 flex items-center gap-2 text-sm font-bold text-ink">
        <i class="fa-solid fa-chart-bar text-brand" aria-hidden="true"></i>
        Wydarzenia w {{ $currentYear }} roku
    </h2>
    <p class="mb-4 text-xs text-muted">Liczba aktywnych wydarzeń z datą startu w danym miesiącu.</p>

    @php
    $months = ['Sty','Lut','Mar','Kwi','Maj','Cze','Lip','Sie','Wrz','Paź','Lis','Gru'];
    $maxMonth = $byMonth->max() ?: 1;
    @endphp

    <div class="flex items-end gap-1.5 overflow-x-auto pb-1" role="img" aria-label="Wykres słupkowy wydarzeń w {{ $currentYear }} roku">
        @foreach ($byMonth as $i => $cnt)
            <div class="flex flex-1 min-w-[2.5rem] flex-col items-center gap-1">
                <span class="text-xs font-bold text-ink {{ $cnt > 0 ? '' : 'invisible' }}" aria-hidden="true">{{ $cnt }}</span>
                <div class="w-full rounded-t-md bg-brand transition-all"
                    style="height: {{ max(4, round($cnt / $maxMonth * 120)) }}px"
                    title="{{ $months[$i] }}: {{ $cnt }}"></div>
                <span class="text-[10px] text-muted">{{ $months[$i] }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- ── Dolna siatka: rejestracja, publikacja, serie ─────────────── --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

    {{-- Publikacja --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
            <i class="fa-solid fa-eye text-brand" aria-hidden="true"></i> Status publikacji
        </h2>
        <ul class="space-y-3" role="list">
            @php
            $pubRows = [
                ['label' => 'Opublikowane',  'value' => $published, 'color' => 'bg-green-500'],
                ['label' => 'Szkice',         'value' => $drafts,    'color' => 'bg-gray-300'],
                ['label' => 'Zarchiwizowane','value' => $archived,  'color' => 'bg-amber-400'],
            ];
            $pubMax = collect($pubRows)->max('value') ?: 1;
            @endphp
            @foreach ($pubRows as $row)
                <li class="flex items-center gap-3">
                    <span class="w-32 text-sm text-ink">{{ $row['label'] }}</span>
                    <div class="flex-1 overflow-hidden rounded-full bg-gray-100" role="img" aria-label="{{ $row['value'] }} {{ $row['label'] }}">
                        <div class="{{ $row['color'] }} h-2 rounded-full"
                            style="width: {{ round($row['value'] / $pubMax * 100) }}%"></div>
                    </div>
                    <span class="w-6 text-right text-sm font-bold text-ink" aria-hidden="true">{{ $row['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Rejestracja --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
            <i class="fa-solid fa-paper-plane text-brand" aria-hidden="true"></i> Sposób zapisu
        </h2>
        <ul class="space-y-3" role="list">
            @php
            $regRows = [
                ['label' => 'URL formularza', 'value' => $withUrl,        'color' => 'bg-blue-500'],
                ['label' => 'Tylko e-mail',   'value' => $emailOnly,      'color' => 'bg-purple-400'],
                ['label' => 'Bez zapisu',     'value' => $noRegistration, 'color' => 'bg-gray-300'],
            ];
            $regMax = collect($regRows)->max('value') ?: 1;
            @endphp
            @foreach ($regRows as $row)
                <li class="flex items-center gap-3">
                    <span class="w-32 text-sm text-ink">{{ $row['label'] }}</span>
                    <div class="flex-1 overflow-hidden rounded-full bg-gray-100" role="img" aria-label="{{ $row['value'] }} {{ $row['label'] }}">
                        <div class="{{ $row['color'] }} h-2 rounded-full"
                            style="width: {{ round($row['value'] / $regMax * 100) }}%"></div>
                    </div>
                    <span class="w-6 text-right text-sm font-bold text-ink" aria-hidden="true">{{ $row['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Prowadzący i serie --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
            <i class="fa-solid fa-circle-info text-brand" aria-hidden="true"></i> Dodatkowe informacje
        </h2>
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <dt class="flex items-center gap-2 text-ink">
                    <i class="fa-solid fa-chalkboard-user w-4 text-center text-muted" aria-hidden="true"></i>
                    Z prowadzącym
                </dt>
                <dd class="font-bold text-ink">{{ $withFacilitator }}
                    <span class="text-xs font-normal text-muted">/ {{ $active }}</span>
                </dd>
            </div>
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <dt class="flex items-center gap-2 text-ink">
                    <i class="fa-solid fa-rotate w-4 text-center text-muted" aria-hidden="true"></i>
                    Serie cykliczne
                </dt>
                <dd class="font-bold text-ink">{{ $seriesCount }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="flex items-center gap-2 text-ink">
                    <i class="fa-solid fa-copy w-4 text-center text-muted" aria-hidden="true"></i>
                    Instancje serii
                </dt>
                <dd class="font-bold text-ink">{{ $instancesCount }}</dd>
            </div>
        </dl>
    </div>

</div>
@endsection
