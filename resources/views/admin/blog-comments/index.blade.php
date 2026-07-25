@extends('admin.layout')

@section('title', 'Moderacja komentarzy — Wiem FEER')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.wiem-feer.index') }}" class="text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left"></i> Wróć do artykułów
        </a>
    </div>

    {{-- Oczekujące na moderację --}}
    <section class="mb-10">
        <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-ink">
            <i class="fa-solid fa-clock text-amber-500"></i> Oczekujące na zatwierdzenie
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $pending->count() }}</span>
        </h2>

        @if ($pending->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center text-sm text-muted">Brak komentarzy do moderacji.</p>
        @else
            <ul class="space-y-3">
                @foreach ($pending as $comment)
                    <li class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
                        <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-bold text-ink">{{ $comment->author_name }}</span>
                            <span class="text-xs text-muted">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="mb-1 text-xs text-muted">
                            @if ($comment->email)<span>{{ $comment->email }}</span> · @endif
                            Artykuł:
                            @if ($comment->article)
                                <a href="{{ route('admin.wiem-feer.edit', $comment->article) }}" class="text-brand hover:underline">{{ $comment->article->title }}</a>
                            @else
                                <span class="italic">(usunięty)</span>
                            @endif
                        </p>
                        <p class="whitespace-pre-line text-sm text-ink">{{ $comment->body }}</p>

                        <div class="mt-3 flex gap-2">
                            <form method="POST" action="{{ route('admin.komentarze-bloga.approve', $comment) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700">
                                    <i class="fa-solid fa-check"></i> Zatwierdź
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.komentarze-bloga.destroy', $comment) }}" onsubmit="return confirm('Usunąć ten komentarz?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-red-300 hover:text-red-600">
                                    <i class="fa-solid fa-trash"></i> Usuń
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Zatwierdzone --}}
    <section>
        <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-ink">
            <i class="fa-solid fa-check-circle text-green-600"></i> Zatwierdzone
        </h2>

        @if ($approved->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center text-sm text-muted">Brak zatwierdzonych komentarzy.</p>
        @else
            <ul class="space-y-3">
                @foreach ($approved as $comment)
                    <li class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-bold text-ink">{{ $comment->author_name }}</span>
                            <span class="text-xs text-muted">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="mb-1 text-xs text-muted">
                            Artykuł:
                            @if ($comment->article)
                                <a href="{{ route('admin.wiem-feer.edit', $comment->article) }}" class="text-brand hover:underline">{{ $comment->article->title }}</a>
                            @else
                                <span class="italic">(usunięty)</span>
                            @endif
                        </p>
                        <p class="whitespace-pre-line text-sm text-ink">{{ $comment->body }}</p>

                        <div class="mt-3">
                            <form method="POST" action="{{ route('admin.komentarze-bloga.destroy', $comment) }}" onsubmit="return confirm('Usunąć ten komentarz?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-xs font-bold text-muted hover:border-red-300 hover:text-red-600">
                                    <i class="fa-solid fa-trash"></i> Usuń
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
