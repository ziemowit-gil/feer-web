@extends('admin.layout')

@section('title', 'Kondycja serwisu')

@section('content')
    @php
        $colors = [
            'ok'      => ['bg' => 'bg-green-50',  'border' => 'border-green-200', 'icon' => 'text-green-600', 'badge' => 'bg-green-100 text-green-700'],
            'warning' => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200', 'icon' => 'text-amber-500', 'badge' => 'bg-amber-100 text-amber-700'],
            'error'   => ['bg' => 'bg-red-50',    'border' => 'border-red-200',   'icon' => 'text-red-500',   'badge' => 'bg-red-100 text-red-700'],
        ];
        $labels = ['ok' => 'OK', 'warning' => 'Uwaga', 'error' => 'Błąd'];
        $statusIcons = ['ok' => 'fa-circle-check', 'warning' => 'fa-triangle-exclamation', 'error' => 'fa-circle-xmark'];
        $oc = $colors[$overall];
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold text-ink">Kondycja serwisu</h1>
            <p class="text-sm text-muted">Wersja CMS <strong class="text-ink" data-cms-version>{{ config('cms.version') }}</strong> · stan systemu · {{ now()->locale('pl')->isoFormat('D MMMM YYYY, HH:mm:ss') }}</p>
        </div>
        <a href="{{ route('admin.health.index') }}"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Odśwież
        </a>
    </div>

    {{-- Ogólny status --}}
    <div class="mb-6 flex items-center gap-3 rounded-xl border {{ $oc['border'] }} {{ $oc['bg'] }} px-5 py-4">
        <i class="fa-solid {{ $statusIcons[$overall] }} text-xl {{ $oc['icon'] }}" aria-hidden="true"></i>
        <div>
            <p class="font-bold text-ink">
                @if ($overall === 'ok') Wszystkie systemy działają poprawnie
                @elseif ($overall === 'warning') Wykryto ostrzeżenia — sprawdź szczegóły
                @else Wykryto błędy — wymagana interwencja
                @endif
            </p>
            <p class="text-sm text-muted">{{ $checks->count() + $deploy->count() + $sections->count() }} sprawdzonych obszarów</p>
        </div>
    </div>

    {{-- Pulpit: harmonogram, kolejka, ostatni deploy, kopie, baza, błędy z doby (App\Support\HealthDashboard).
         Kontrast: ink/muted na bieli, status słowem (nie tylko kolorem ikony), tabele z nagłówkami. --}}
    @php
        $sectionLabels = ['ok' => 'OK', 'warning' => 'Uwaga', 'error' => 'Problem'];
        $sectionText = ['ok' => 'text-ink', 'warning' => 'text-amber-900', 'error' => 'text-red-800'];
        $sectionBar = ['ok' => 'bg-gray-50', 'warning' => 'bg-amber-50', 'error' => 'bg-red-50'];
    @endphp
    <div class="mb-8 grid gap-4 lg:grid-cols-2">
        @foreach ($sections as $sec)
            <section aria-labelledby="sec-{{ $sec['key'] }}" class="flex flex-col rounded-xl bg-white {{ $sec['key'] === 'errors' || $sec['key'] === 'database' ? 'lg:col-span-2' : '' }}">
                <div class="flex flex-wrap items-center gap-3 rounded-t-xl px-5 py-3 {{ $sectionBar[$sec['status']] }}">
                    <i class="fa-solid {{ $sec['icon'] }} {{ $colors[$sec['status']]['icon'] }}" aria-hidden="true"></i>
                    <h2 id="sec-{{ $sec['key'] }}" class="text-base font-bold text-ink">{{ $sec['title'] }}</h2>
                    <span class="ml-auto text-sm font-bold {{ $sectionText[$sec['status']] }}">{{ $sectionLabels[$sec['status']] }}<span class="sr-only">:</span></span>
                </div>
                <div class="flex-1 px-5 py-4">
                    <p class="text-sm font-bold {{ $sectionText[$sec['status']] }}">{{ $sec['summary'] }}</p>
                    @if ($sec['rows'])
                        <dl class="mt-3 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-[12rem_1fr]">
                            @foreach ($sec['rows'] as $label => $value)
                                <dt class="text-muted">{{ $label }}</dt>
                                <dd class="break-words text-ink">{{ $value }}</dd>
                            @endforeach
                        </dl>
                    @endif
                    @if ($sec['table'])
                        <div class="mt-3 overflow-x-auto" tabindex="0" role="region" aria-label="{{ $sec['title'] }} — szczegóły">
                            <table class="w-full text-left text-sm">
                                <caption class="sr-only">{{ $sec['title'] }}</caption>
                                <thead class="bg-gray-50 text-xs text-ink">
                                    <tr>
                                        @foreach ($sec['table']['head'] as $i => $h)
                                            <th scope="col" class="px-3 py-2 {{ $i > 0 ? 'text-right whitespace-nowrap' : '' }}">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sec['table']['rows'] as $row)
                                        <tr class="align-top">
                                            @foreach ($row as $i => $cell)
                                                @if ($i === 0)
                                                    <th scope="row" class="break-words px-3 py-1.5 font-normal text-ink {{ $sec['key'] === 'errors' ? 'font-mono text-xs' : '' }}">{{ $cell }}</th>
                                                @else
                                                    <td class="whitespace-nowrap px-3 py-1.5 text-right text-ink">{{ $cell }}</td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @foreach ($sec['notes'] as $note)
                        <p class="mt-3 text-sm text-muted">{{ $note }}</p>
                    @endforeach
                    @if ($sec['link'])
                        <a href="{{ $sec['link']['url'] }}" class="mt-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $sec['link']['label'] }} <span aria-hidden="true">→</span></a>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <h2 class="mb-3 text-base font-bold text-ink">Usługi</h2>

    {{-- Lista sprawdzeń --}}
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($checks as $check)
            @php $c = $colors[$check['status']]; @endphp
            <div class="flex items-start gap-4 rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} px-5 py-4">
                <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/70">
                    <i class="fa-solid {{ $check['icon'] }} {{ $c['icon'] }}" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-bold text-ink">{{ $check['name'] }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $c['badge'] }}">
                            {{ $labels[$check['status']] }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-sm text-muted">{{ $check['detail'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Wdrożenie — te same kontrole co `artisan system:sprawdz` (App\Support\DeployHealth) --}}
    @php $deployLabels = ['ok' => 'OK', 'warning' => 'Uwaga', 'error' => 'Problem']; @endphp
    <section class="mt-8" aria-labelledby="health-deploy-heading">
        <h2 id="health-deploy-heading" class="text-base font-bold text-ink">Wdrożenie</h2>
        <p class="mb-3 text-sm text-muted">Assety, migracje obu baz, plik bazy bloga, link do plików i harmonogram. Z konsoli: <code class="text-ink">php84 artisan system:sprawdz</code></p>
        <dl class="divide-y divide-gray-200 border border-gray-200 bg-white">
            @foreach ($deploy as $row)
                @php
                    $rowClass = match ($row['status']) {
                        'error'   => 'bg-red-50 text-red-800',
                        'warning' => 'bg-gray-50 text-ink',
                        default   => 'text-ink',
                    };
                @endphp
                <div class="grid gap-1 px-4 py-3 text-sm sm:grid-cols-[14rem_6rem_1fr] sm:gap-4 {{ $rowClass }}">
                    <dt class="font-bold">{{ $row['name'] }}</dt>
                    <dd class="font-bold {{ $row['status'] === 'ok' ? 'text-brand-dark' : '' }}">{{ $deployLabels[$row['status']] }}</dd>
                    <dd class="break-words">{{ $row['detail'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endsection
