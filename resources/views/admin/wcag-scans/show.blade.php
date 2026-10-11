@extends('admin.layout')

@section('title', 'Wynik skanu WCAG')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.wcag-scans.index') }}"
           class="inline-flex items-center gap-1 text-sm text-muted hover:text-gray-900">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy skanów
        </a>
        <form method="POST" action="{{ route('admin.wcag-scans.scan') }}">
            @csrf
            <input type="hidden" name="url" value="{{ $scan->url }}">
            <button type="submit"
                    class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white hover:bg-brand-light">
                <i class="fa-solid fa-rotate" aria-hidden="true"></i> Skanuj ponownie
            </button>
        </form>
    </div>

    <div class="mb-4 overflow-hidden p-4 rounded-md bg-white">
        <h2 class="font-semibold">{{ $scan->page_title ?? '(brak tytułu)' }}</h2>
        <a href="{{ $scan->url }}" target="_blank" rel="noopener noreferrer"
           class="mt-0.5 break-all text-sm text-blue-600 hover:underline">
            {{ $scan->url }} <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
        </a>
        <p class="mt-2 text-xs text-muted">Ostatni skan: {{ $scan->scanned_at->format('d.m.Y H:i') }}</p>
    </div>

    @php
        $bySeverity = $scan->issuesBySeverity();
        $errors   = $bySeverity['error'];
        $warnings = $bySeverity['warning'];
    @endphp

    @if ($scan->issue_count === 0)
        <div class="rounded-md bg-brand-light px-6 py-8 text-center">
            <p class="font-medium text-green-800">Nie znaleziono problemów z dostępnością.</p>
            <p class="mt-1 text-sm text-green-700">Automatyczny skaner nie wykrył żadnych naruszeń WCAG na tej stronie. Zalecamy też ręczne testy z czytnikiem ekranu.</p>
        </div>
    @else
        <div class="mb-4 flex gap-3">
            @if (count($errors) > 0)
                <div class="rounded-md bg-red-50 px-4 py-3 text-center">
                    <div class="text-2xl font-bold text-red-700">{{ count($errors) }}</div>
                    <div class="text-xs font-medium text-red-600">{{ count($errors) === 1 ? 'Błąd' : 'Błędy' }}</div>
                </div>
            @endif
            @if (count($warnings) > 0)
                <div class="rounded-md bg-yellow-50 px-4 py-3 text-center">
                    <div class="text-2xl font-bold text-yellow-700">{{ count($warnings) }}</div>
                    <div class="text-xs font-medium text-yellow-600">{{ count($warnings) === 1 ? 'Ostrzeżenie' : 'Ostrzeżenia' }}</div>
                </div>
            @endif
        </div>

        @if (count($errors) > 0)
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-red-600">Błędy (wymagają poprawy)</h2>
            <div class="mb-6 space-y-2">
                @foreach ($errors as $issue)
                    <div class="rounded-lg border border-red-100 bg-white p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $issue['message'] }}</p>
                                @if (!empty($issue['context']))
                                    <code class="mt-1 block rounded bg-gray-50 px-2 py-1 text-xs text-muted">{{ $issue['context'] }}</code>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs font-mono text-ink font-bold">{{ $issue['code'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($warnings) > 0)
            <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-yellow-600">Ostrzeżenia (zalecane sprawdzenie)</h2>
            <div class="space-y-2">
                @foreach ($warnings as $issue)
                    <div class="rounded-lg border border-yellow-100 bg-white p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $issue['message'] }}</p>
                                @if (!empty($issue['context']))
                                    <code class="mt-1 block rounded bg-gray-50 px-2 py-1 text-xs text-muted">{{ $issue['context'] }}</code>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs font-mono text-ink font-bold">{{ $issue['code'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
@endsection
