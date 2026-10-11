@extends('admin.layout')

@section('title', 'Wiem FEER — artykuły')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-4 text-sm font-bold">
            <a href="{{ route('admin.komentarze-bloga.index') }}" class="text-muted hover:text-brand"><i class="fa-solid fa-comments" aria-hidden="true"></i> Moderacja komentarzy</a>
            <a href="{{ route('admin.kategorie-bloga.index') }}" class="text-muted hover:text-brand"><i class="fa-solid fa-folder" aria-hidden="true"></i> Kategorie</a>
            <a href="{{ route('admin.autorzy-bloga.index') }}" class="text-muted hover:text-brand"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Autorzy</a>
        </div>
        <a href="{{ route('admin.wiem-feer.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj artykuł
        </a>
    </div>

    <form method="GET" action="{{ route('admin.wiem-feer.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="mb-1 block text-sm font-bold">Status</label>
            <select id="status" name="status" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                @foreach (\Modules\Blog\Models\BlogArticle::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded bg-gray-100 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Filtruj</button>
        @if ($status !== '')
            <a href="{{ route('admin.wiem-feer.index') }}" class="py-2 text-sm font-bold text-brand-dark underline">Pokaż wszystkie</a>
        @endif
    </form>

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
                        <td class="px-4 py-3 font-medium">
                            {{ $article->title }}
                            @if ($article->category)<span class="block text-xs text-muted">{{ $article->category->name }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $article->authorLine() ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ optional($article->published_at ?? $article->created_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-muted">
                            {{ $article->comments_count }}
                            @if ($article->pending_comments_count)
                                <span class="block text-xs font-bold text-ink">{{ $article->pending_comments_count }} do moderacji</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-ink">{{ $article->statusLabel() }}</span>
                            @if ($article->status() === 'scheduled')
                                <span class="block text-xs text-muted">od {{ $article->published_at->format('Y-m-d H:i') }}</span>
                            @endif
                            @if ($article->isWip())
                                <span class="block text-xs text-muted">W przygotowaniu</span>
                            @endif
                            @if ($article->is_featured)
                                <span class="block text-xs text-muted">Wyróżniony</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                @if ($article->isVisible())
                                    <a href="{{ route('blog.show', $article) }}" target="_blank" rel="noopener" class="text-muted hover:text-brand" title="Zobacz na stronie"><i class="fa-solid fa-up-right-from-square"></i></a>
                                @else
                                    <a href="{{ $article->previewUrl() }}" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700" title="Podgląd wersji roboczej (link ważny 14 dni)"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                @endif
                                <a href="{{ route('admin.wiem-feer.edit', $article) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.wiem-feer.klonuj', $article) }}">
                                    @csrf
                                    <button type="submit" class="text-muted hover:text-brand" title="Klonuj jako szkic" aria-label="Klonuj jako szkic"><i class="fa-solid fa-copy"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.wiem-feer.wylacz', $article) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($article->is_disabled)
                                        <button type="submit" class="text-red-600 hover:text-green-600" title="Włącz artykuł (jest wyłączony)" aria-label="Włącz artykuł (jest wyłączony)"><i class="fa-solid fa-ban"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-red-600" title="Wyłącz artykuł" aria-label="Wyłącz artykuł"><i class="fa-solid fa-power-off"></i></button>
                                    @endif
                                </form>
                                <form method="POST" action="{{ route('admin.wiem-feer.destroy', $article) }}" onsubmit="return confirm('Usunąć artykuł &quot;{{ $article->title }}&quot; wraz z komentarzami?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
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
