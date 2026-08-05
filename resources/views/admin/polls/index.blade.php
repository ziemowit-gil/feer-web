@extends('admin.layout')

@section('title', 'Ankiety')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.ankiety.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj ankietę
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Pytanie</th>
                    <th class="px-4 py-3">Opcje</th>
                    <th class="px-4 py-3">Głosy</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($polls as $poll)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $poll->question }}</td>
                        <td class="px-4 py-3 text-muted">{{ $poll->options->pluck('label')->implode(', ') }}</td>
                        <td class="px-4 py-3 text-muted">{{ $poll->totalVotes() }}</td>
                        <td class="px-4 py-3">
                            @if ($poll->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Aktywna</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Nieaktywna</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.ankiety.edit', $poll) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.ankiety.destroy', $poll) }}" onsubmit="return confirm('Usunąć ankietę &quot;{{ $poll->question }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak ankiet. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
