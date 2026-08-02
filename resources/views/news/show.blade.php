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
    <section class="mx-auto max-w-2xl px-4 py-12" x-data="{ etr: false }">
        @if ($news->is_archived)
            @include('partials.archival-notice', ['date' => $news->published_at])
        @endif

        @include('partials.etr-toggle', ['etr' => $news->etr, 'title' => $news->title])

        <div x-show="!etr">
            <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
                <span class="text-muted">{{ $news->published_at->format('d.m.Y') }}</span>
                @if ($news->updated_at->gt($news->published_at))
                    <span class="text-muted">&middot; Zaktualizowano: {{ $news->updated_at->format('d.m.Y') }}</span>
                @endif
            </div>

            <h1 class="mb-6 text-3xl font-bold text-ink">{{ $news->title }}</h1>

            @php $img = $news->imageUrlOrDefault(); @endphp
            @if ($img)
                <img src="{{ $img }}" alt="{{ $news->image_alt ?: 'Zdjęcie ilustracyjne: '.$news->title }}" data-lightbox class="mb-6 h-64 w-full rounded-lg object-cover">
            @endif

            <div class="prose max-w-none text-ink">{!! $news->content !!}</div>

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
