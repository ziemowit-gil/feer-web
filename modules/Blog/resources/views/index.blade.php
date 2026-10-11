@extends('layouts.site')

{{--
    Lista Wiem FEER w układzie portalu (jak szablon „Strona działu"): lewa, szeroka
    kolumna — tytuł, wstęp, wpis wyróżniony (zdjęcie po lewej, tekst po prawej),
    siatka po trzy, stronicowanie; prawa — przyklejona kolumna boczna (wyszukiwarka,
    kategorie, tagi, newsletter). Na telefonie jedna kolumna, kolumna boczna pod
    listą, a nad listą skrócona wyszukiwarka. Płasko: bez ramek i plakietek.
    Dane: BlogController::index().
--}}

@php
    $blogSite = (bool) request()->attributes->get('blog_site');
    $blogName = $blogSite ? $siteSettings->site_name : 'Wiem FEER';
    $filterParts = array_filter([
        $q !== '' ? '„' . $q . '”' : null,
        $category ? 'kategoria: ' . $category->name : null,
        $tag ? 'tag: ' . $tag->name : null,
        $author ? 'autor: ' . $author->name : null,
    ]);
    $pageTitle = $filtered ? 'Wyniki dla ' . implode(', ', $filterParts) . ' — ' . $blogName : $blogName;
    if ($articles->currentPage() > 1) {
        $pageTitle .= ' (strona ' . $articles->currentPage() . ')';
    }
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $date = fn ($d) => $d?->locale('pl')->translatedFormat('j F Y');
    $showFeatured = $featured && $articles->onFirstPage();
    // Odmiana: 1 wpis, 2–4 wpisy (poza 12–14), pozostałe — wpisów.
    $plural = fn (int $n) => $n === 1 ? 'wpis' : (in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) ? 'wpisy' : 'wpisów');
@endphp

@section('title', $blogSite ? $pageTitle : $pageTitle . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Wiem FEER — blog fundacji ' . $siteSettings->site_name . ': artykuły o dostępności, edukacji i empatii.')
@if ($showFeatured && $featured->imageUrl())
    @section('og_image', url($featured->imageUrl()))
@endif

@include('blog::partials.feed-link')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => $filtered
        ? [['label' => $blogName, 'url' => route('blog.index')], ['label' => 'Wyniki', 'url' => null]]
        : [['label' => $blogName, 'url' => null]]])
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0">
                <header class="mb-8">
                    <h1 class="text-4xl text-ink md:text-5xl" style="font-family:'Pacifico',cursive;font-weight:400;line-height:1.3">Wiem FEER</h1>
                    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">Artykuły o dostępności cyfrowej, edukacji i empatii — miejsce, w którym dzielimy się wiedzą i doświadczeniem.</p>
                </header>

                {{-- Na telefonie wyszukiwarka nad listą (kolumna boczna jest dopiero pod nią). --}}
                <div class="mb-8 lg:hidden">
                    @include('blog::partials.search-form', ['formId' => 'blog-search-mobile', 'q' => $q])
                </div>

                @if ($filtered)
                    <section class="mb-8 rounded-md bg-gray-50 p-4" aria-labelledby="blog-results-heading">
                        <h2 id="blog-results-heading" class="text-xl font-bold text-ink">Wyniki dla {{ implode(', ', $filterParts) }}</h2>
                        @if ($category && $category->description)
                            <p class="mt-2 text-ink">{{ $category->description }}</p>
                        @endif
                        <p class="mt-1 text-sm text-ink" role="status">
                            {{ $articles->total() }} {{ $plural($articles->total()) }}
                            · <a href="{{ route('blog.index') }}" class="{{ $link }}">Wyczyść filtr</a>
                        </p>
                    </section>
                @endif

                {{-- ===== WPIS WYRÓŻNIONY ===== --}}
                @if ($showFeatured)
                    @php $fImg = $featured->imageUrl(); $fDate = $featured->publishedDate(); @endphp
                    <section class="mb-10" aria-labelledby="blog-featured-heading">
                        <h2 id="blog-featured-heading" class="mb-4 text-2xl font-bold text-ink">
                            Wyróżniony wpis
                            <span class="mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                        </h2>
                        <article class="grid gap-6 rounded-md bg-gray-50 p-4 md:p-6 {{ $fImg ? 'md:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' : '' }}">
                            @if ($fImg)
                                <img src="{{ $fImg }}" alt="{{ $featured->imageAlt() }}" class="aspect-[4/3] w-full rounded-md object-cover">
                            @endif
                            <div>
                                <p class="text-sm text-muted">
                                    @if ($fDate)<time datetime="{{ $fDate->toDateString() }}">{{ $date($fDate) }}</time>@endif
                                    @if ($featured->category) · {{ $featured->category->name }}@endif
                                </p>
                                <h3 class="mt-2 text-2xl font-bold leading-tight text-ink">
                                    <a href="{{ route('blog.show', $featured) }}" class="hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $featured->title }}</a>
                                </h3>
                                @include('blog::partials.card-authors', ['article' => $featured, 'class' => 'mt-2'])
                                @if ($featured->excerpt)
                                    <p class="mt-3 leading-relaxed text-ink">{{ $featured->excerpt }}</p>
                                @endif
                                <p class="mt-4"><a href="{{ route('blog.show', $featured) }}" class="{{ $link }}">Pełna treść<span class="sr-only">: {{ $featured->title }}</span></a></p>
                            </div>
                        </article>
                    </section>
                @endif

                {{-- ===== SIATKA WPISÓW ===== --}}
                @if ($articles->isNotEmpty())
                    <section aria-labelledby="blog-list-heading">
                        <h2 id="blog-list-heading" class="{{ $filtered ? 'sr-only' : 'mb-6 text-2xl font-bold text-ink' }}">
                            {{ $filtered ? 'Znalezione wpisy' : 'Najnowsze wpisy' }}
                            @unless ($filtered)<span class="mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endunless
                        </h2>
                        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($articles as $article)
                                <li>@include('blog::partials.card', ['article' => $article])</li>
                            @endforeach
                        </ul>

                        {{ $articles->links('blog::partials.pagination') }}
                    </section>
                @elseif (! $showFeatured)
                    <p class="rounded-md bg-gray-50 p-4 text-ink">
                        @if ($filtered)
                            Nie znaleźliśmy wpisów pasujących do wybranych kryteriów. <a href="{{ route('blog.index') }}" class="{{ $link }}">Zobacz wszystkie wpisy</a>.
                        @else
                            Nie ma jeszcze żadnych artykułów. Zajrzyj wkrótce!
                        @endif
                    </p>
                @endif
            </div>

            @include('blog::partials.sidebar')
        </div>
    </div>
@endsection
