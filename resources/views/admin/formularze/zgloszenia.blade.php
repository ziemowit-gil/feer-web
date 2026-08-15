@extends('admin.layout')

@section('title', 'Zgłoszenia: ' . $form->title)

@section('content')
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.formularze.index') }}"
                class="text-sm text-muted hover:text-brand focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Formularze
            </a>
            <h1 class="mt-1 text-lg font-bold text-ink">Zgłoszenia: {{ $form->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.formularze.edit', $form) }}"
                class="rounded border border-gray-200 bg-white px-3 py-2 text-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-pen mr-1" aria-hidden="true"></i> Edytuj formularz
            </a>
            @if ($submissions->total() > 0)
                <a href="{{ route('admin.formularze.zgloszenia.eksport', $form) }}"
                    class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-file-csv mr-1" aria-hidden="true"></i> Eksport CSV
                </a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
            {{ session('status') }}
        </div>
    @endif

    @php $fields = $form->normalizedFields(); @endphp

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Data</th>
                    @foreach ($fields as $field)
                        <th class="px-4 py-3">{{ $field['label'] }}</th>
                    @endforeach
                    <th class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($submissions as $sub)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-muted">{{ $sub->id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $sub->created_at->format('d.m.Y H:i') }}</td>
                        @foreach ($fields as $field)
                            <td class="max-w-xs px-4 py-3">
                                @php $value = $sub->data[$field['key']] ?? null; @endphp
                                @if (is_null($value) || $value === '')
                                    <span class="text-gray-300">—</span>
                                @elseif ($field['type'] === 'checkbox')
                                    <i class="fa-solid fa-circle-check text-green-500" aria-label="Zaznaczone"></i>
                                @else
                                    <span class="break-words">{{ $value }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            <form method="POST"
                                action="{{ route('admin.formularze.zgloszenia.destroy', [$form, $sub]) }}"
                                onsubmit="return confirm('Usunąć to zgłoszenie?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="rounded p-1.5 text-gray-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                    title="Usuń zgłoszenie">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($fields) + 3 }}" class="px-4 py-10 text-center text-muted">
                            <i class="fa-solid fa-inbox mb-2 text-3xl text-gray-300" aria-hidden="true"></i>
                            <p class="text-sm">Brak zgłoszeń do tego formularza.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($submissions->hasPages())
        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
@endsection
