@extends('admin.layout')

@section('title', $task->exists ? 'Edytuj zadanie' : 'Nowe zadanie')

@section('content')
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ $task->exists ? route('admin.zadania.update', $task) : route('admin.zadania.store') }}" class="space-y-6">
            @csrf
            @if ($task->exists)
                @method('PUT')
            @endif

            <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-5">
                <div>
                    <label for="title" class="block text-sm font-semibold text-ink">Tytuł zadania <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}"
                        required maxlength="255"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('title') border-red-400 @enderror">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-ink">Opis</label>
                    <textarea id="description" name="description" rows="4" maxlength="5000"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('description') border-red-400 @enderror">{{ old('description', $task->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="status" class="block text-sm font-semibold text-ink">Status</label>
                        <select id="status" name="status"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                            <option value="todo" @selected(old('status', $task->status) === 'todo')>Do zrobienia</option>
                            <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>W trakcie</option>
                            <option value="done" @selected(old('status', $task->status) === 'done')>Zrobione</option>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-semibold text-ink">Priorytet</label>
                        <select id="priority" name="priority"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                            <option value="low" @selected(old('priority', $task->priority) === 'low')>Niski</option>
                            <option value="normal" @selected(old('priority', $task->priority ?? 'normal') === 'normal')>Normalny</option>
                            <option value="high" @selected(old('priority', $task->priority) === 'high')>Wysoki</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="assigned_to" class="block text-sm font-semibold text-ink">Przypisz do</label>
                        <select id="assigned_to" name="assigned_to"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                            <option value="">— brak przypisania —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected(old('assigned_to', $task->assigned_to) == $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">Przypisana osoba otrzyma powiadomienie e-mail (jeśli ma włączone).</p>
                        @error('assigned_to')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-semibold text-ink">Termin</label>
                        <input type="date" id="due_date" name="due_date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('due_date') border-red-400 @enderror">
                        @error('due_date')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.zadania.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-muted hover:bg-gray-50">
                    Anuluj
                </a>
                <button type="submit" class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    {{ $task->exists ? 'Zapisz zmiany' : 'Dodaj zadanie' }}
                </button>
            </div>
        </form>
    </div>
@endsection
