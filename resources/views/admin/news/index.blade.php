@extends('admin.layout')

@section('title', 'Aktualności')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.newsy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj news
        </a>
    </div>

    @include('admin.partials.list-filters', [
        'action' => route('admin.newsy.index'),
        'status' => $status,
        'categories' => $categories,
        'categoryId' => $category,
        'sort' => $sort,
        'sortOptions' => ['date_desc' => 'Najnowsze', 'date_asc' => 'Najstarsze', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
    ])

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Kategoria</th>
                    <th class="px-4 py-3">Tagi</th>
                    <th class="px-4 py-3">Obraz</th>
                    <th class="px-4 py-3">Od kiedy</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($news as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            @if ($item->is_featured)
                                <i class="fa-solid fa-star mr-1 text-amber-400" title="Wyróżniony" aria-hidden="true"></i>
                            @endif
                            {{ $item->title }}
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $item->category->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $item->tags->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">
                            @if ($item->image_width && $item->image_height)
                                {{ $item->image_width }}×{{ $item->image_height }} px
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $item->published_at?->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($item->is_published && $item->published_at?->isPast())
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                            @elseif ($item->is_published)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zaplanowany</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                @if ($item->is_published && $item->published_at <= now())
                                    <a href="{{ route('news.show', $item) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye"></i></a>
                                @else
                                    <a href="{{ $item->previewUrl() }}" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700" title="Podgląd wersji roboczej (link ważny 14 dni)"><i class="fa-solid fa-eye"></i></a>
                                @endif
                                <a href="{{ route('admin.newsy.edit', $item) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.newsy.klonuj', $item) }}">
                                    @csrf
                                    <button type="submit" class="text-muted hover:text-brand" title="Klonuj"><i class="fa-solid fa-copy"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.newsy.destroy', $item) }}" onsubmit="return confirm('Usunąć news &quot;{{ $item->title }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-muted">Brak newsów. Dodaj pierwszy powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
