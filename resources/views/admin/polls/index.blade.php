@extends('admin.layout')

@section('title', 'Ankiety')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.ankiety.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj ankietę
        </a>
    </div>

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
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
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Aktywna</span>
                            @else
                                <span class="text-xs font-bold text-muted">Nieaktywna</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.ankiety.edit', $poll) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                @if ($poll->totalVotes() > 0)
                                    <form method="POST" action="{{ route('admin.ankiety.reset-votes', $poll) }}"
                                        onsubmit="return confirm('Wyzerować wszystkie głosy ankiety „{{ $poll->question }}”?\n\nTej operacji nie można cofnąć.');">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-amber-600" title="Zeruj głosy" aria-label="Zeruj głosy">
                                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.ankiety.destroy', $poll) }}" onsubmit="return confirm('Usunąć ankietę &quot;{{ $poll->question }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                        @include('admin.partials.empty-state', ['icon' => 'fa-square-poll-vertical', 'title' => 'Nie ma jeszcze ankiet', 'text' => 'Krótka ankieta pozwala zapytać odwiedzających o zdanie.', 'route' => 'admin.ankiety.create', 'action' => 'Dodaj ankietę', 'colspan' => 5])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
