@extends('admin.layout')

@section('title', 'Podcasty')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}"
                placeholder="Szukaj podcastu…"
                class="w-64 rounded border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            <button type="submit" class="rounded bg-gray-100 px-3 py-1.5 text-sm font-medium hover:bg-gray-200">Szukaj</button>
        </form>
        <a href="{{ route('admin.podcasty.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podcast
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Odc.</th>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($podcasts as $podcast)
                    <tr class="{{ $podcast->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $podcast->title }}</td>
                        <td class="px-4 py-3 text-muted">{{ $podcast->episode_number ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ optional($podcast->published_at)->format('d.m.Y') ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($podcast->is_premium)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Premium</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">Bezpłatny</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($podcast->trashed())
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Usunięty</span>
                            @elseif ($podcast->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">Szkic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @unless ($podcast->trashed())
                                <a href="{{ route('admin.podcasty.edit', $podcast) }}"
                                    class="inline-flex items-center gap-1 rounded border border-gray-300 px-3 py-1 text-xs font-medium text-ink hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-brand">
                                    Edytuj
                                </a>
                                <form method="POST" action="{{ route('admin.podcasty.destroy', $podcast) }}" class="inline" onsubmit="return confirm('Usunąć ten podcast?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-1 inline-flex items-center gap-1 rounded border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500">
                                        Usuń
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-muted">Brak podcastów.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $podcasts->links() }}</div>
@endsection
