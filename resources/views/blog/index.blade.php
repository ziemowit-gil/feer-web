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
        <header class="mb-10">
            <p class="text-xs font-bold uppercase tracking-wider text-brand">Blog</p>
            <h1 class="mt-1 text-3xl font-bold text-ink sm:text-4xl">Wiem FEER</h1>
            <p class="mt-2 max-w-2xl text-muted">Artykuły o dostępności cyfrowej, edukacji i empatii — miejsce, w którym dzielimy się wiedzą i doświadczeniem.</p>
        </header>

        @if (! $featured)
            <p class="text-muted">Nie ma jeszcze żadnych artykułów. Zajrzyj wkrótce!</p>
        @else
            {{-- ==================== HERO — NAJNOWSZY WPIS (tylko strona 1) ==================== --}}
            @if ($articles->onFirstPage())
                <article class="group relative mb-12 overflow-hidden rounded-2xl bg-gradient-to-br from-brand to-brand-dark text-white shadow-lg">
                    <div class="relative z-10 p-8 sm:p-10 lg:p-12">
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider">
                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                            Najnowszy wpis
                        </p>
                        <h2 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl lg:text-4xl">
                            <a href="{{ route('blog.show', $featured) }}"
                               class="rounded outline-none after:absolute after:inset-0 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand">
                                {{ $featured->title }}
                            </a>
                        </h2>
                        <p class="mt-3 text-sm text-white/80">
                            @if ($featured->author_name){{ $featured->author_name }} · @endif
                            {{ optional($featured->published_at ?? $featured->created_at)->translatedFormat('j F Y') }}
                        </p>
                        @if ($featured->excerpt)
                            <p class="mt-5 max-w-2xl text-base leading-relaxed text-white/90">{{ $featured->excerpt }}</p>
                        @endif
                        <span class="mt-7 inline-flex items-center gap-2 text-sm font-bold">
                            Czytaj artykuł
                            <i class="fa-solid fa-arrow-right transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true"></i>
                        </span>
                    </div>
                    <i class="fa-solid fa-feather-pointed pointer-events-none absolute -bottom-6 -right-4 text-[9rem] text-white/10 sm:text-[12rem]" aria-hidden="true"></i>
                </article>
            @endif

            {{-- ==================== POZOSTAŁE WPISY — LISTA REDAKCYJNA ==================== --}}
            @if ($articles->isNotEmpty())
                @if ($articles->onFirstPage())
                    <h2 class="mb-2 text-xl font-bold text-ink">Pozostałe wpisy</h2>
                @endif

                <ul class="divide-y divide-gray-200 border-t border-gray-200">
                    @foreach ($articles as $article)
                        <li class="group relative py-6 transition duration-200 sm:flex sm:gap-8">
                            <p class="mb-2 shrink-0 text-xs font-bold uppercase tracking-wider text-muted sm:mb-0 sm:w-40 sm:pt-1">
                                <time datetime="{{ optional($article->published_at ?? $article->created_at)->toDateString() }}">
                                    {{ optional($article->published_at ?? $article->created_at)->translatedFormat('j F Y') }}
                                </time>
                            </p>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-ink">
                                    <a href="{{ route('blog.show', $article) }}"
                                       class="rounded outline-none transition-colors group-hover:text-brand after:absolute after:inset-0 focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        {{ $article->title }}
                                    </a>
                                </h3>
                                @if ($article->author_name)
                                    <p class="mt-1 text-xs text-muted">{{ $article->author_name }}</p>
                                @endif
                                @if ($article->excerpt)
                                    <p class="mt-2 text-sm text-muted">{{ $article->excerpt }}</p>
                                @endif
                                <span class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand">
                                    Czytaj dalej
                                    <i class="fa-solid fa-arrow-right transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true"></i>
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-10">
                    {{ $articles->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
