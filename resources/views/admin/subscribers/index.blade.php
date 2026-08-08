@extends('admin.layout')

@section('title', 'Subskrybenci')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold">Subskrybenci powiadomień</h1>
        <a href="{{ route('admin.subskrybenci.export', request()->only('topic')) }}"
            class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">
            <i class="fa-solid fa-download" aria-hidden="true"></i> Eksport CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.subskrybenci.index') }}" class="mb-4 flex flex-wrap gap-3">
        <select name="topic" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Filtruj po temacie">
            <option value="">Wszystkie tematy</option>
            @foreach ($topics as $key => $label)
                <option value="{{ $key }}" {{ $topic === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="confirmed" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" aria-label="Filtruj po statusie">
            <option value="">Wszyscy</option>
            <option value="1" {{ $confirmed === '1' ? 'selected' : '' }}>Potwierdzeni</option>
            <option value="0" {{ $confirmed === '0' ? 'selected' : '' }}>Niepotwierdzeni</option>
        </select>

        <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Filtruj</button>
        @if ($topic || $confirmed !== '')
            <a href="{{ route('admin.subskrybenci.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm text-muted hover:bg-gray-50">Wyczyść</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Imię / nick</th>
                    <th class="px-4 py-3">Tematy</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Data zapisu</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($subscribers as $subscriber)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3 text-muted">{{ $subscriber->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($subscriber->topicLabels() as $label)
                                    <span class="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-bold text-brand">{{ $label }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($subscriber->isConfirmed())
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Potwierdzony</span>
                            @else
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-bold text-yellow-700">Oczekuje</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $subscriber->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('admin.subskrybenci.destroy', $subscriber) }}"
                                    onsubmit="return confirm('Usunąć subskrybenta {{ $subscriber->email }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń {{ $subscriber->email }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Brak subskrybentów.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-sm text-muted">Łącznie: {{ $subscribers->count() }}</p>
@endsection
