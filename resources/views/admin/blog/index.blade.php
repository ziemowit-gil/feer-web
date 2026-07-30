@extends('admin.layout')

@section('title', 'Wiem FEER — artykuły')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('admin.komentarze-bloga.index') }}" class="text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-comments"></i> Moderacja komentarzy
        </a>
        <a href="{{ route('admin.wiem-feer.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj artykuł
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Autor</th>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Komentarze</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($articles as $article)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $article->title }}</td>
                        <td class="px-4 py-3 text-muted">{{ $article->author_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ optional($article->published_at ?? $article->created_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-muted">
                            {{ $article->comments_count }}
                            @if ($article->pending_comments_count)
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $article->pending_comments_count }} do moderacji</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($article->isVisible())
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                            @endif
                            @if ($article->is_disabled)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700"><i class="fa-solid fa-ban"></i> Wyłączony</span>
                            @endif
                            @if ($article->isWip())
                                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700"><i class="fa-solid fa-person-digging"></i> W przygotowaniu</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                @if ($article->isVisible())
                                    <a href="{{ route('blog.show', $article) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="Zobacz na stronie"><i class="fa-solid fa-up-right-from-square"></i></a>
                                @else
                                    <a href="{{ $article->previewUrl() }}" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700" title="Podgląd wersji roboczej (link ważny 14 dni)"><i class="fa-solid fa-eye"></i></a>
                                @endif
                                <a href="{{ route('admin.wiem-feer.edit', $article) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.wiem-feer.wylacz', $article) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($article->is_disabled)
                                        <button type="submit" class="text-red-600 hover:text-green-600" title="Włącz artykuł (jest wyłączony)"><i class="fa-solid fa-ban"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-red-600" title="Wyłącz artykuł"><i class="fa-solid fa-power-off"></i></button>
                                    @endif
                                </form>
                                <form method="POST" action="{{ route('admin.wiem-feer.destroy', $article) }}" onsubmit="return confirm('Usunąć artykuł &quot;{{ $article->title }}&quot; wraz z komentarzami?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-muted">Brak artykułów. Dodaj pierwszy powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
