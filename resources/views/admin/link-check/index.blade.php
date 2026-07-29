@extends('admin.layout')

@section('title', 'Martwe linki')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-muted">Sprawdza linki w treści aktualności, stron i projektów.</p>
            @if ($scannedAt)
                <p class="mt-1 text-xs text-muted">Ostatnie skanowanie: {{ \Illuminate\Support\Carbon::parse($scannedAt)->format('Y-m-d H:i') }}</p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.martwe-linki.scan') }}"
            x-data @submit="$el.querySelector('button').disabled = true; $el.querySelector('[data-label]').textContent = 'Skanuję…'">
            @csrf
            <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> <span data-label>Skanuj teraz</span>
            </button>
        </form>
    </div>

    @if ($capped)
        <div role="status" class="mb-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Sprawdzono maksymalną liczbę linków zewnętrznych w jednym skanie — część mogła nie zostać zweryfikowana.
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Źródło</th>
                    <th class="px-4 py-3">Link</th>
                    <th class="px-4 py-3">Problem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($results as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ $row['edit_url'] }}" class="font-medium text-brand hover:text-brand-dark">{{ $row['label'] }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="break-all font-mono text-xs text-muted">{{ $row['url'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">{{ $row['error'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-muted">
                            @if ($scannedAt)
                                <i class="fa-solid fa-circle-check mb-2 block text-2xl text-green-600" aria-hidden="true"></i>
                                Nie znaleziono martwych linków.
                            @else
                                Kliknij „Skanuj teraz", aby sprawdzić linki.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
