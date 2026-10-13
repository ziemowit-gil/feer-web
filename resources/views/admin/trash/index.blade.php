@extends('admin.layout')

@section('title', 'Kosz')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-ink">Kosz</h1>
            <p class="text-sm text-muted">Usunięte treści. Możesz je przywrócić albo skasować trwale. Trwałego usunięcia nie da się cofnąć.</p>
        </div>
        @if ($items->isNotEmpty() && \Illuminate\Support\Facades\Route::has('admin.kosz.empty'))
            <form method="POST" action="{{ route('admin.kosz.empty') }}"
                onsubmit="return confirm(@js('Trwale usunąć wszystkie elementy z kosza ('.$items->count().')? Tego nie da się cofnąć.'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-red-800 px-4 text-sm font-bold text-white hover:bg-red-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-800 focus-visible:ring-offset-2">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń wszystkie ({{ $items->count() }})
                </button>
            </form>
        @endif
    </div>

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 text-left text-muted">
                <tr>
                    <th class="px-4 py-3 font-bold">Typ</th>
                    <th class="px-4 py-3 font-bold">Tytuł</th>
                    <th class="px-4 py-3 font-bold">Usunięto</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $item['label'] }}</td>
                        <td class="px-4 py-3 font-bold text-ink">{{ $item['title'] }}</td>
                        <td class="px-4 py-3 text-muted">{{ $item['deleted_at']?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.kosz.restore', ['type' => $item['type'], 'id' => $item['id']]) }}">
                                    @csrf
                                    <button type="submit" class="rounded bg-brand-dark px-3 py-1.5 text-xs font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.kosz.force', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                    onsubmit="return confirm('Trwale usunąć „{{ $item['title'] }}”? Tej operacji nie można cofnąć.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md px-3 py-1.5 text-xs font-bold text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 bg-gray-100 hover:bg-brand-light">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń trwale
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-muted">
                            <i class="fa-solid fa-trash-can mb-2 block text-2xl text-gray-300" aria-hidden="true"></i>
                            Kosz jest pusty.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
