@extends('layouts.site')

@section('title', $article->title . ' — Wiem FEER')
@section('meta_description', $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 155))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wiem FEER', 'url' => route('blog.index')],
        ['label' => $article->title, 'url' => null],
    ]])
@endsection

@section('content')
    @if ($article->showsPlaceholder())
    @include('partials.unavailable-notice', ['entity' => $article, 'backUrl' => route('blog.index'), 'backLabel' => 'Wróć do wszystkich artykułów'])
    @else
    <article class="mx-auto max-w-3xl px-4 py-12">
        @if ($article->wipIsNotice())
            @include('partials.page-wip-notice', ['message' => $article->wipMessage(), 'heading' => 'Artykuł w przygotowaniu'])
        @endif

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">{{ $article->title }}</h1>
            <p class="mt-3 text-sm text-muted">
                @if ($article->author_name)<span class="font-bold text-ink">{{ $article->author_name }}</span> · @endif
                {{ optional($article->published_at ?? $article->created_at)->translatedFormat('j F Y') }}
            </p>
        </header>

        <div class="prose max-w-none text-ink">{!! $article->body !!}</div>

        {{-- ==================== KOMENTARZE ==================== --}}
        <section id="komentarze" class="mt-14 border-t border-gray-200 pt-8">
            <h2 class="mb-6 flex items-center gap-2 text-2xl font-bold text-ink">
                <i class="fa-solid fa-comments text-brand" aria-hidden="true"></i>
                Komentarze
                <span class="text-base font-normal text-muted">({{ $article->approvedComments->count() }})</span>
            </h2>

            @if (session('comment_status'))
                <div class="mb-6 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('comment_status') }}</span>
                </div>
            @endif

            @if ($article->approvedComments->isEmpty())
                <p class="mb-8 text-sm text-muted">Brak komentarzy. Bądź pierwszą osobą, która skomentuje ten artykuł.</p>
            @else
                <ul class="mb-10 space-y-5">
                    @foreach ($article->approvedComments as $comment)
                        <li class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-bold text-ink">{{ $comment->author_name }}</span>
                                <span class="text-xs text-muted">{{ $comment->created_at->translatedFormat('j F Y, H:i') }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm text-ink">{{ $comment->body }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Formularz dodania komentarza --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-bold text-ink">Dodaj komentarz</h3>
                <p class="mb-4 text-xs text-muted">Komentarz pojawi się po zatwierdzeniu przez moderatora.</p>

                <form method="POST" action="{{ route('blog.comments.store', $article) }}" class="space-y-4">
                    @csrf

                    {{-- Honeypot antyspamowy — ukryty przed użytkownikami. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Zostaw to pole puste</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="author_name" class="mb-1 block text-sm font-bold">Imię lub podpis</label>
                            <input type="text" id="author_name" name="author_name" value="{{ old('author_name') }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('author_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">(opcjonalnie, nie publikujemy)</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="body" class="mb-1 block text-sm font-bold">Komentarz</label>
                        <textarea id="body" name="body" rows="4" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('body') }}</textarea>
                        @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                        Wyślij komentarz
                    </button>
                </form>
            </div>
        </section>
    </article>
    @endif
@endsection
