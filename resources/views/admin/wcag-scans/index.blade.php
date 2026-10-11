@extends('admin.layout')

@section('title', 'Skaner WCAG')

@section('content')
    <div class="mb-6 p-4 rounded-md bg-white">
        <h2 class="mb-3 text-sm font-semibold">Skanuj stronę pod kątem dostępności WCAG</h2>
        <form method="POST" action="{{ route('admin.wcag-scans.scan') }}" class="flex gap-2">
            @csrf
            <label for="scan-url" class="sr-only">Adres URL do przeskanowania</label>
            <input type="url" id="scan-url" name="url"
                   placeholder="https://feer.org.pl/przykladowa-strona"
                   value="{{ old('url') }}"
                   class="flex-1 rounded border border-gray-500 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('url') border-red-500 @enderror"
                   required>
            <button type="submit"
                    class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                <i class="fa-solid fa-magnifying-glass mr-1" aria-hidden="true"></i> Skanuj
            </button>
        </form>
        @error('url')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-2 text-xs text-muted">Skaner sprawdza: atrybuty alt obrazów, etykiety pól formularzy, teksty łączy, nagłówki, atrybut lang, znacznik title i inne.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if ($scans->isEmpty())
        <p class="px-6 py-8 text-center text-muted rounded-md bg-white">
            Brak wyników skanowania. Wpisz adres URL powyżej, aby rozpocząć.
        </p>
    @else
        <div class="overflow-hidden rounded-md bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="px-4 py-3">Strona</th>
                        <th class="px-4 py-3 text-center">Błędy</th>
                        <th class="px-4 py-3 text-center">Ostrzeżenia</th>
                        <th class="px-4 py-3">Data skanu</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($scans as $scan)
                        @php
                            $bySeverity = $scan->issuesBySeverity();
                            $errorCount = count($bySeverity['error']);
                            $warnCount  = count($bySeverity['warning']);
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $scan->page_title ?? '(brak tytułu)' }}</div>
                                <div class="text-xs text-muted break-all">{{ $scan->url }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($errorCount > 0)
                                    <span class="inline-flex items-center text-xs font-bold text-ink"><i class="fa-solid fa-circle-xmark mr-1 text-red-700" aria-hidden="true"></i>{{ $errorCount }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($warnCount > 0)
                                    <span class="inline-flex items-center text-xs font-bold text-ink"><i class="fa-solid fa-circle-exclamation mr-1 text-ink" aria-hidden="true"></i>{{ $warnCount }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-muted">{{ $scan->scanned_at->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.wcag-scans.show', $scan) }}"
                                       class="text-xs font-medium text-blue-600 hover:underline"
                                       aria-label="Szczegóły skanu: {{ $scan->page_title ?? $scan->url }}">
                                        Szczegóły
                                    </a>
                                    <form method="POST" action="{{ route('admin.wcag-scans.destroy', $scan) }}"
                                          onsubmit="return confirm('Usunąć wynik skanu dla tej strony?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-muted hover:text-red-600"
                                                aria-label="Usuń wynik skanu: {{ $scan->page_title ?? $scan->url }}">
                                            <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
