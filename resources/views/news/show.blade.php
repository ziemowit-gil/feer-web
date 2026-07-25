@extends('layouts.site')

@section('title', $news->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $news->excerpt)
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
    <section class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="text-muted">{{ $news->published_at->format('d.m.Y') }}</span>
            @if ($news->updated_at->gt($news->published_at))
                <span class="text-muted">&middot; Zaktualizowano: {{ $news->updated_at->format('d.m.Y') }}</span>
            @endif
        </div>

        <h1 class="mb-6 text-3xl font-bold text-ink">{{ $news->title }}</h1>

        @php $img = $news->imageUrlOrDefault(); @endphp
        @if ($img)
            <img src="{{ $img }}" alt="{{ $news->image_alt ?: 'Zdjęcie ilustracyjne: '.$news->title }}" class="mb-6 h-64 w-full rounded-lg object-cover">
        @endif

        <div class="prose max-w-none text-ink">{!! $news->content !!}</div>

        @include('partials.attachments-list', ['attachments' => $news->attachments])
    </section>
@endsection
