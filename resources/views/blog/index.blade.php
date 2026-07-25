@extends('layouts.site')

@section('title', 'Wiem FEER — ' . $siteSettings->site_name)
@section('meta_description', 'Wiem FEER — blog fundacji ' . $siteSettings->site_name . ': artykuły o dostępności, edukacji i empatii.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wiem FEER', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <header class="mb-8">
            <p class="text-xs font-bold uppercase tracking-wider text-brand">Blog</p>
            <h1 class="mt-1 text-3xl font-bold text-ink">Wiem FEER</h1>
            <p class="mt-2 max-w-2xl text-muted">Artykuły o dostępności cyfrowej, edukacji i empatii — miejsce, w którym dzielimy się wiedzą i doświadczeniem.</p>
        </header>

        @if ($articles->isEmpty())
            <p class="text-muted">Nie ma jeszcze żadnych artykułów. Zajrzyj wkrótce!</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <article class="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-brand/40 hover:shadow-lg">
                        <h2 class="text-lg font-bold text-ink">
                            <a href="{{ route('blog.show', $article) }}" class="hover:text-brand">{{ $article->title }}</a>
                        </h2>
                        <p class="mt-1 text-xs text-muted">
                            @if ($article->author_name){{ $article->author_name }} · @endif
                            {{ optional($article->published_at ?? $article->created_at)->translatedFormat('j F Y') }}
                        </p>
                        @if ($article->excerpt)
                            <p class="mt-3 flex-1 text-sm text-muted">{{ $article->excerpt }}</p>
                        @endif
                        <a href="{{ route('blog.show', $article) }}" class="mt-4 inline-flex w-fit items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                            Czytaj dalej <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </section>
@endsection
