@extends('admin.layout')

@section('title', 'Wiem FEER — autorzy')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.wiem-feer.index') }}" class="text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do artykułów
        </a>
        <a href="{{ route('admin.autorzy-bloga.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj autora
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Autor</th>
                    <th class="px-4 py-3">Rola</th>
                    <th class="px-4 py-3">Wpisy</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($authors as $a)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-3">
                                @if ($a->photoUrl())<img src="{{ $a->photoUrl() }}" alt="" class="h-9 w-9 rounded-md object-cover">@endif
                                <span class="font-medium">{{ $a->name }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $a->role ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $a->articles_total }}</td>
                        <td class="px-4 py-3 font-bold text-ink">{{ $a->is_active ? 'Aktywny' : 'Nieaktywny' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('blog.author', $a) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="Strona autora"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i><span class="sr-only">Strona autora {{ $a->name }} (nowa karta)</span></a>
                                <a href="{{ route('admin.autorzy-bloga.edit', $a) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj {{ $a->name }}</span></a>
                                <form method="POST" action="{{ route('admin.autorzy-bloga.aktywnosc', $a) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-muted hover:text-brand" title="{{ $a->is_active ? 'Dezaktywuj' : 'Aktywuj' }}">
                                        <i class="fa-solid {{ $a->is_active ? 'fa-user-slash' : 'fa-user-check' }}" aria-hidden="true"></i><span class="sr-only">{{ $a->is_active ? 'Dezaktywuj' : 'Aktywuj' }} {{ $a->name }}</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-muted">Brak autorów. Dodaj pierwszego powyżej.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
