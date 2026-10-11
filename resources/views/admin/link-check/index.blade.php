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
            <button type="submit" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> <span data-label>Skanuj teraz</span>
            </button>
        </form>
    </div>

    @if ($capped)
        <div role="status" class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Sprawdzono maksymalną liczbę linków zewnętrznych w jednym skanie — część mogła nie zostać zweryfikowana.
        </div>
    @endif

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
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
                            <a href="{{ $row['edit_url'] }}" class="font-medium text-brand-dark hover:text-ink">{{ $row['label'] }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="break-all font-mono text-xs text-muted">{{ $row['url'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-xmark mr-1 text-red-700" aria-hidden="true"></i>{{ $row['error'] }}</span>
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
