@extends('admin.layout')

@section('title', 'Projekty')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.projekty.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj projekt
        </a>
    </div>

    @include('admin.partials.list-filters', [
        'action' => route('admin.projekty.index'),
        'status' => $status,
        'categories' => $categories,
        'categoryId' => $category,
        'sort' => $sort,
        'sortOptions' => ['default' => 'Domyślne (kolejność)', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
        'total' => $projects->count(),
    ])

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($projects as $project)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $project->title }}</td>
                        <td class="px-4 py-3 text-muted">{{ $project->category->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $project->order }}</td>
                        <td class="px-4 py-3">
                            @if ($project->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                            @endif
                            @if ($project->is_completed)
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">Zrealizowany</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('projects.show', $project) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                <a href="{{ route('admin.projekty.edit', $project) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.projekty.destroy', $project) }}" onsubmit="return confirm('Usunąć projekt &quot;{{ $project->title }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">Brak projektów. Dodaj pierwszy powyżej (wymaga co najmniej jednej kategorii).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
