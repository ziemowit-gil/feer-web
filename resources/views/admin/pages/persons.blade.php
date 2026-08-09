@extends('admin.layout')

@section('title', 'Osoby')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <div class="mb-4 flex items-center justify-between gap-2">
        <p class="text-sm text-muted">Pełna lista osób ze wszystkich stron „O organizacji".</p>
        <a href="{{ route('admin.osoby.create') }}"
            class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Dodaj osobę
        </a>
    </div>

    {{-- Filtry --}}
    <form method="GET" action="{{ route('admin.osoby.index') }}" class="mb-4 flex flex-wrap gap-2">
        <input
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="Szukaj po nazwisku…"
            class="w-60 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand"
            aria-label="Szukaj osoby"
        >
        @if ($departments->isNotEmpty())
            <select name="dept" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand focus:ring-brand" aria-label="Filtruj po dziale">
                <option value="">Wszystkie działy</option>
                @foreach ($departments as $d)
                    <option value="{{ $d }}" @selected($dept === $d)>{{ $d }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-bold text-muted hover:bg-gray-50">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Szukaj
        </button>
        @if ($q || $dept)
            <a href="{{ route('admin.osoby.index') }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-muted hover:bg-gray-50">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Wyczyść
            </a>
        @endif
    </form>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-muted">
                <tr>
                    <th class="w-14 px-3 py-2">Zdjęcie</th>
                    <th class="px-4 py-2">Imię i nazwisko</th>
                    <th class="px-4 py-2">Stanowisko</th>
                    <th class="px-4 py-2">Działy</th>
                    <th class="px-4 py-2">Strona nadrzędna</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($persons as $person)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                            @if (filled($person->content_image))
                                <img src="{{ $person->content_image }}" alt="" class="h-10 w-10 rounded-full object-cover" loading="lazy">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 font-medium text-ink">{{ $person->title }}</td>
                        <td class="px-4 py-2 text-muted">{{ $person->person_role ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($person->person_department)
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($person->person_department as $d)
                                        <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs font-bold text-brand-dark">{{ $d }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-muted">
                            @if ($person->parent)
                                <a href="{{ route('admin.podstrony.edit', $person->parent) }}"
                                    class="text-brand hover:underline">{{ $person->parent->title }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if ($person->is_published)
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-800">
                                    <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i> Opublikowana
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-muted">
                                    <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i> Szkic
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.podstrony.edit', $person) }}"
                                    class="text-muted hover:text-brand"
                                    title="Edytuj" aria-label="Edytuj {{ $person->title }}">
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.podstrony.destroy', $person) }}"
                                    data-confirm="Usunąć osobę „{{ $person->title }}"? Tej operacji nie można cofnąć.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-muted hover:text-red-600"
                                        title="Usuń" aria-label="Usuń {{ $person->title }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-muted">
                            @if ($q || $dept)
                                Nie znaleziono osób pasujących do podanych filtrów.
                            @else
                                Brak osób. Dodaj je przez formularz strony „O organizacji".
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($persons->hasPages())
        <div class="mt-4">
            {{ $persons->links() }}
        </div>
    @endif
@endsection
