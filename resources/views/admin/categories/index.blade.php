@extends('admin.layout')

@section('title', 'Kategorie projektów')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.kategorie.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kategorię
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Projekty</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-muted">/kategoria/{{ $category->slug }}</td>
                        <td class="px-4 py-3 text-muted">{{ $category->projects_count }}</td>
                        <td class="px-4 py-3 text-muted">{{ $category->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('categories.show', $category) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                <a href="{{ route('admin.kategorie.edit', $category) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.kategorie.destroy', $category) }}" onsubmit="return confirm('Usunąć kategorię &quot;{{ $category->name }}&quot; wraz z jej projektami?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak kategorii. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
