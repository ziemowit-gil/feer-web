@extends('layouts.site')

@section('title', ($news->meta_title ?: $news->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $news->meta_description ?: $news->excerpt)

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news->title,
            'description' => $news->excerpt,
            'datePublished' => optional($news->published_at)->toIso8601String(),
            'image' => method_exists($news, 'getFirstMediaUrl') ? ($news->getFirstMediaUrl('image') ?: null) : null,
            'author' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'publisher' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
@if ($news->image_url)
    @section('og_image', $news->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        ['label' => 'Aktualności', 'url' => route('news.index')],
        $news->category ? ['label' => $news->category->name, 'url' => null] : null,
        ['label' => $news->title, 'url' => null],
    ])])
@endsection

@section('content')
    @if ($preview ?? false)
        <div class="border-b border-amber-300 bg-amber-50 px-4 py-3" role="status">
            <div class="mx-auto flex max-w-2xl flex-wrap items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-sm font-bold text-amber-800">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    Podgląd szkicu — ta aktualność nie jest jeszcze widoczna dla odwiedzających.
                </p>
                <a href="{{ route('admin.newsy.edit', $news) }}"
                    class="inline-flex items-center gap-2 rounded bg-amber-700 px-4 py-1.5 text-sm font-bold text-white hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edytuj w panelu
                </a>
            </div>
        </div>
    @endif
    @php
        $articleLayout = $news->article_layout ?? 'default';
        $img           = $news->imageUrlOrDefault();
        $imgAlt        = $news->image_alt ?: 'Zdjęcie ilustracyjne: ' . $news->title;
        $isSide        = $articleLayout === 'side' && $img;
    @endphp

    {{-- Układ "obok": max-w-5xl, żeby było miejsce na dwie kolumny --}}
    <section class="mx-auto px-4 py-12 {{ $isSide ? 'max-w-5xl' : 'max-w-2xl' }}" x-data="{ etr: false }">
        @if ($news->is_archived)
            @include('partials.archival-notice', ['date' => $news->published_at])
        @endif

        @include('partials.etr-toggle', ['etr' => $news->etr, 'title' => $news->title])

        <div x-show="!etr">
            <a href="{{ route('news.index') }}"
                class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                Powrót do aktualności
            </a>

            <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
                <span class="text-muted">{{ $news->published_at->format('d.m.Y') }}</span>
                @if ($news->updated_at->gt($news->published_at))
                    <span class="text-muted">&middot; Zaktualizowano: {{ $news->updated_at->format('d.m.Y') }}</span>
                @endif
            </div>

            <h1 class="mb-6 text-3xl font-bold text-ink">{{ $news->title }}</h1>

            @if ($articleLayout === 'side' && $img)
                {{-- ── Obok tekstu: zdjęcie po lewej, treść po prawej ── --}}
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <div class="shrink-0 sm:w-72">
                        <img src="{{ $img }}" alt="{{ $imgAlt }}" data-lightbox
                            class="w-full rounded-lg object-cover sm:aspect-[4/3]">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="prose max-w-none text-ink">{!! $news->content !!}</div>
                    </div>
                </div>
            @else
                {{-- ── Banner (default / wide) lub bez zdjęcia ── --}}
                @if ($img && $articleLayout !== 'none')
                    <img src="{{ $img }}" alt="{{ $imgAlt }}" data-lightbox
                        class="mb-6 w-full rounded-lg object-cover {{ $articleLayout === 'wide' ? 'h-96' : 'h-64' }}">
                @endif
                <div class="prose max-w-none text-ink">{!! $news->content !!}</div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3 print:hidden" aria-label="Opcje artykułu">
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-print text-muted" aria-hidden="true"></i>
                    Drukuj
                </button>
                <a href="{{ route('news.pdf', $news) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-file-pdf text-muted" aria-hidden="true"></i>
                    Tekst w PDF
                </a>
            </div>

            @include('partials.attachments-list', ['attachments' => $news->attachments])
        </div>
    </section>
@endsection
