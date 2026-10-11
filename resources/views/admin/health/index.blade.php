@extends('admin.layout')

@section('title', 'Health Check')

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
            <h1 class="text-lg font-bold text-ink">Health Check</h1>
            <p class="text-sm text-muted">Stan systemu · {{ now()->locale('pl')->isoFormat('D MMMM YYYY, HH:mm:ss') }}</p>
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
            <p class="text-sm text-muted">{{ $checks->count() + $deploy->count() }} sprawdzonych komponentów</p>
        </div>
    </div>

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
