@extends('admin.layout')

@section('title', 'Kosz')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-ink">Kosz</h1>
        <p class="text-sm text-muted">Usunięte treści. Możesz je przywrócić albo skasować trwale. Trwałego usunięcia nie da się cofnąć.</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
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
                                    <button type="submit" class="rounded bg-brand px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.kosz.force', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                    onsubmit="return confirm('Trwale usunąć „{{ $item['title'] }}”? Tej operacji nie można cofnąć.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-red-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
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
