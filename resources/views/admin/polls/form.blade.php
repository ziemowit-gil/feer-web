@extends('admin.layout')

@section('title', $poll->exists ? 'Edytuj ankietę' : 'Nowa ankieta')

@section('content')
    <form method="POST" action="{{ $poll->exists ? route('admin.ankiety.update', $poll) : route('admin.ankiety.store') }}" class="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($poll->exists) @method('PUT') @endif

        <div>
            <label for="question" class="mb-1 block text-sm font-bold">Pytanie</label>
            <input type="text" id="question" name="question" value="{{ old('question', $poll->question) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('question') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $poll->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Aktywna (widoczna na stronie głównej)</span>
        </label>

        <div>
            <p class="mb-2 text-sm font-bold">Opcje odpowiedzi</p>

            <div id="poll-options" class="space-y-2">
                @forelse ($poll->options ?? [] as $i => $option)
                    <div class="flex items-center gap-2" data-option-row>
                        <input type="hidden" name="options[{{ $i }}][id]" value="{{ $option->id }}">
                        <input type="text" name="options[{{ $i }}][label]" value="{{ $option->label }}" required
                            class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <label class="flex items-center gap-1 text-xs text-muted">
                            <input type="checkbox" name="options[{{ $i }}][delete]" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            Usuń
                        </label>
                    </div>
                @empty
                    <div class="flex items-center gap-2" data-option-row>
                        <input type="text" name="options[0][label]" required placeholder="Opcja 1"
                            class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                    <div class="flex items-center gap-2" data-option-row>
                        <input type="text" name="options[1][label]" required placeholder="Opcja 2"
                            class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                @endforelse
            </div>

            <button type="button" id="add-option" class="mt-3 text-sm font-bold text-brand hover:text-brand-dark">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj opcję
            </button>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.ankiety.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>

    <script>
        (function () {
            const container = document.getElementById('poll-options');
            let nextIndex = container.querySelectorAll('[data-option-row]').length;

            document.getElementById('add-option').addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                row.dataset.optionRow = '';
                row.innerHTML = `
                    <input type="text" name="options[${nextIndex}][label]" required placeholder="Nowa opcja"
                        class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                `;
                container.appendChild(row);
                nextIndex++;
            });
        })();
    </script>
@endsection
