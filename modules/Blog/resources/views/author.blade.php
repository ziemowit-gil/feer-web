@extends('layouts.site')

{{--
    Strona autora Wiem FEER: profil (zdjęcie, rola, nota, linki) i jego wpisy —
    ta sama siatka kart i stronicowanie co lista, kolumna boczna jak na liście.
    Dane: BlogController::author().
--}}

@php
    $blogSite = (bool) request()->attributes->get('blog_site');
    $blogName = $blogSite ? $siteSettings->site_name : 'Wiem FEER';
@endphp

@section('title', $author->name . ($articles->currentPage() > 1 ? ' (strona ' . $articles->currentPage() . ')' : '') . ' — ' . $blogName)
@section('meta_description', $author->bio ? \Illuminate\Support\Str::limit($author->bio, 155) : 'Wpisy autora ' . $author->name . ' w blogu Wiem FEER.')
@if ($author->photoUrl())
    @section('og_image', url($author->photoUrl()))
@endif

@include('blog::partials.feed-link')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $author->name,
                'jobTitle' => $author->role,
                'description' => $author->bio,
                'url' => $author->url(),
                'image' => $author->photoUrl() ? url($author->photoUrl()) : null,
                'sameAs' => collect($author->linkList())->pluck('url')->all() ?: null,
            ]),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $blogName, 'url' => route('blog.index')],
        ['label' => 'Autorzy', 'url' => route('blog.authors')],
        ['label' => $author->name, 'url' => null],
    ]])
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0">
                <header class="mb-8">
                    <p class="text-sm font-bold uppercase tracking-widest text-brand-dark">Autor Wiem FEER</p>
                    <h1 class="mt-2 text-3xl font-bold text-ink md:text-4xl">{{ $author->name }}</h1>
                    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                </header>

                @if ($author->photoUrl() || $author->role || $author->bio || $author->linkList())
                    <section class="mb-10" aria-label="O autorze">
                        @include('blog::partials.author-box', ['author' => $author, 'headingLevel' => 2, 'linkName' => false])
                    </section>
                @endif

                <section aria-labelledby="blog-author-articles-heading">
                    <h2 id="blog-author-articles-heading" class="mb-6 text-2xl font-bold text-ink">
                        Wpisy
                        <span class="mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                    </h2>
                    @if ($articles->isEmpty())
                        <p class="rounded-md bg-gray-50 p-4 text-ink">Ten autor nie ma jeszcze opublikowanych wpisów.</p>
                    @else
                        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($articles as $article)
                                <li>@include('blog::partials.card', ['article' => $article])</li>
                            @endforeach
                        </ul>
                        {{ $articles->links('blog::partials.pagination') }}
                    @endif
                </section>
            </div>

            @include('blog::partials.sidebar', ['q' => ''])
        </div>
    </div>
@endsection
