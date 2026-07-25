@extends('admin.layout')

@section('title', 'Kategorie newsów')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.kategorie-newsow.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj kategorię
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Nazwa</th>
                    <th class="px-4 py-3">Kolor</th>
                    <th class="px-4 py-3">Newsy</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($newsCategories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            <span class="mr-2 inline-block h-3 w-3 rounded-full align-middle" style="background-color: {{ $category->badgeColor() }}"></span>
                            {{ $category->name }}
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $category->color ?: 'kolor marki' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $category->news_count }}</td>
                        <td class="px-4 py-3 text-muted">{{ $category->order }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.kategorie-newsow.edit', $category) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.kategorie-newsow.destroy', $category) }}" onsubmit="return confirm('Usunąć kategorię &quot;{{ $category->name }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
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
