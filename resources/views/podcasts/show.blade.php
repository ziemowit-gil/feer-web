@extends('layouts.site')

@section('title', $podcast->title . ' — Podcasty — ' . $siteSettings->site_name)
@section('meta_description', $podcast->description)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => route('podcasts.index')],
        ['label' => $podcast->title, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12">
        <a href="{{ route('podcasts.index') }}"
            class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            Powrót do podcastów
        </a>

        @php $cover = $podcast->getFirstMediaUrl('cover'); @endphp
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $podcast->title }}"
                class="mb-6 w-full rounded-xl object-cover shadow-sm" style="max-height:18rem">
        @endif

        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-muted">
            @if ($podcast->episode_number)
                <span class="font-medium text-brand">Odcinek {{ $podcast->episode_number }}</span>
                <span aria-hidden="true">·</span>
            @endif
            @if ($podcast->published_at)
                <time datetime="{{ $podcast->published_at->toDateString() }}">
                    {{ $podcast->published_at->format('d.m.Y') }}
                </time>
            @endif
            @if ($podcast->is_premium)
                <span class="inline-flex items-center gap-1 rounded border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                    <svg class="h-3 w-3" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .67.415l2.302 4.667 5.145.748a.75.75 0 0 1 .416 1.279l-3.724 3.629.879 5.122a.75.75 0 0 1-1.088.79L10 15.547l-4.6 2.419a.75.75 0 0 1-1.088-.79l.879-5.122L1.467 8.11a.75.75 0 0 1 .416-1.279l5.145-.748L9.33 1.415A.75.75 0 0 1 10 1Z" clip-rule="evenodd"/></svg>
                    Premium
                </span>
            @endif
        </div>

        <h1 class="mb-4 text-3xl font-bold text-ink">{{ $podcast->title }}</h1>

        @if ($podcast->description)
            <p class="mb-6 text-muted">{{ $podcast->description }}</p>
        @endif

        @include('partials.podcast-player', ['podcast' => $podcast, 'canPlay' => $canPlay])
    </section>
@endsection
