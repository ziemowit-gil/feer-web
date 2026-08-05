@extends('admin.layout')

@section('title', 'Zadania')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.zadania.index', ['status' => 'open'] + ($mine ? ['moje' => 1] : [])) }}"
                class="rounded-full px-3 py-1 text-sm font-semibold {{ $filter === 'open' ? 'bg-brand text-white' : 'bg-gray-100 text-muted hover:bg-gray-200' }}">
                Otwarte ({{ $openCount }})
            </a>
            <a href="{{ route('admin.zadania.index', ['status' => 'done'] + ($mine ? ['moje' => 1] : [])) }}"
                class="rounded-full px-3 py-1 text-sm font-semibold {{ $filter === 'done' ? 'bg-brand text-white' : 'bg-gray-100 text-muted hover:bg-gray-200' }}">
                Zrobione ({{ $doneCount }})
            </a>
            <a href="{{ route('admin.zadania.index', ['status' => $filter, 'moje' => $mine ? null : 1]) }}"
                class="rounded-full px-3 py-1 text-sm font-semibold {{ $mine ? 'bg-brand-light text-brand' : 'bg-gray-100 text-muted hover:bg-gray-200' }}">
                <i class="fa-solid fa-user" aria-hidden="true"></i> Moje
            </a>
        </div>
        <a href="{{ route('admin.zadania.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj zadanie
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Zadanie</th>
                    <th class="px-4 py-3">Priorytet</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Przypisane do</th>
                    <th class="px-4 py-3">Termin</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tasks as $task)
                    <tr class="{{ $task->isDone() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium {{ $task->isDone() ? 'line-through text-muted' : '' }}">{{ $task->title }}</div>
                            @if ($task->description)
                                <div class="mt-0.5 text-xs text-muted line-clamp-1">{{ $task->description }}</div>
                            @endif
                            @if ($task->creator)
                                <div class="mt-0.5 text-xs text-gray-400">Dodał(-a): {{ $task->creator->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold
                                {{ $task->priority === 'high' ? 'bg-red-100 text-red-700' : ($task->priority === 'low' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700') }}">
                                {{ $task->priorityLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold
                                {{ $task->status === 'done' ? 'bg-green-100 text-green-700' : ($task->status === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $task->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $task->assignee?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap {{ $task->isOverdue() ? 'text-red-600 font-semibold' : 'text-muted' }}">
                            @if ($task->due_date)
                                {{ $task->due_date->format('d.m.Y') }}
                                @if ($task->isOverdue())
                                    <i class="fa-solid fa-triangle-exclamation ml-1" aria-label="Po terminie" title="Po terminie"></i>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @if (! $task->isDone())
                                    <form method="POST" action="{{ route('admin.zadania.done', $task) }}">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Oznacz jako zrobione" aria-label="Oznacz jako zrobione">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.zadania.edit', $task) }}" class="text-brand hover:text-brand-dark" title="Edytuj">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.zadania.destroy', $task) }}"
                                    onsubmit="return confirm('Usunąć zadanie &quot;{{ $task->title }}&quot;?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">
                            Brak zadań.
                            @if ($filter === 'open')
                                <a href="{{ route('admin.zadania.create') }}" class="text-brand underline">Dodaj pierwsze</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (session('status'))
        <p class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</p>
    @endif
@endsection
