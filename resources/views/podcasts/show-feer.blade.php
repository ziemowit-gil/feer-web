@extends('layouts.site')

@section('title', $podcast->title . ' — Podcasty — ' . $siteSettings->site_name)
@section('meta_description', \Illuminate\Support\Str::limit((string) $podcast->description, 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => route('podcasts.index')],
        ['label' => $podcast->title, 'url' => null],
    ]])
@endsection

{{--
    Odcinek w stylu FEER: tytuł z akcentem marki, numer i data zwykłym tekstem, odtwarzacz (partials.podcast-player-feer),
    pełny opis i „Kolejne odcinki”. Płasko, bez ramek i plakietek; zwykły CSS (klasy pd-*).
--}}
@section('content')
    @php
        $cover = $podcast->getFirstMediaUrl('cover');
        $more = $more ?? collect();
    @endphp
    <style>
        .pd-bar { display: block; width: 3.5rem; height: .25rem; background: #1e6dff; margin: 1rem 0 1.25rem; }
        .pd-meta { margin: 0; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #4b5563; }
        .pd-meta .pd-strong { color: #1752bf; }
        .pd-head { display: grid; gap: 1.5rem; align-items: center; }
        @media (min-width: 640px) { .pd-head.has-cover { grid-template-columns: 11rem minmax(0, 1fr); } }
        .pd-cover { width: 100%; max-width: 11rem; aspect-ratio: 1; object-fit: cover; border-radius: .75rem; }
        .pd-desc { margin: 2rem 0 0; font-size: 1.075rem; line-height: 1.75; color: #1d1d1a; white-space: pre-line; }
        .pd-section-h { margin: 3rem 0 1rem; font-size: 1.4rem; font-weight: 800; color: #1d1d1a; }
        .pd-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
        .pd-item { position: relative; display: flex; gap: 1rem; align-items: center; border-radius: .75rem; background: #f3f4f6; padding: .9rem 1.1rem; }
        .pd-item:hover { background: #e8f0ff; }
        .pd-item:focus-within { outline: 3px solid #1752bf; outline-offset: 2px; }
        .pd-num { flex: none; width: 2.5rem; text-align: center; font-size: 1.3rem; font-weight: 800; color: #1752bf; }
        .pd-item a { color: #1d1d1a; font-weight: 700; text-decoration: none; }
        .pd-item a::after { content: ''; position: absolute; inset: 0; border-radius: .75rem; }
        .pd-item a:focus-visible { outline: none; }
        .pd-back { display: inline-block; margin-top: 2rem; font-weight: 700; color: #1752bf; text-decoration: underline; text-underline-offset: 3px; }
        .pd-back:focus-visible { outline: 3px solid #1752bf; outline-offset: 2px; }
    </style>

    <div class="mx-auto max-w-3xl px-4 py-10 md:py-12">
        <header class="pd-head {{ $cover ? 'has-cover' : '' }}">
            @if ($cover)<img src="{{ $cover }}" alt="" class="pd-cover">@endif
            <div class="min-w-0">
                <p class="pd-meta">
                    <span class="pd-strong">Podcast</span>
                    @if ($podcast->episode_number) · odcinek {{ $podcast->episode_number }}@endif
                    @if ($podcast->published_at) · <time datetime="{{ $podcast->published_at->toDateString() }}">{{ $podcast->published_at->locale('pl')->translatedFormat('j F Y') }}</time>@endif
                    @if ($podcast->is_premium) · <i class="fa-solid fa-lock" aria-hidden="true"></i> dla subskrybentów @endif
                </p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $podcast->title }}</h1>
                <span class="pd-bar" aria-hidden="true"></span>
            </div>
        </header>

        <div class="mt-6">
            @include('partials.podcast-player-feer', ['podcast' => $podcast, 'canPlay' => $canPlay, 'compact' => true])
        </div>

        @if ($podcast->description)
            <h2 class="sr-only">Opis odcinka</h2>
            <p class="pd-desc">{{ $podcast->description }}</p>
        @endif

        @if ($more->isNotEmpty())
            <h2 id="pd-more-h" class="pd-section-h">Kolejne odcinki</h2>
            <ul class="pd-list" role="list" aria-labelledby="pd-more-h">
                @foreach ($more as $ep)
                    <li class="pd-item">
                        <span class="pd-num" aria-hidden="true">{{ $ep->episode_number ?: '•' }}</span>
                        <div class="min-w-0">
                            <a href="{{ route('podcasts.show', $ep) }}">@if ($ep->episode_number)<span class="sr-only">Odcinek {{ $ep->episode_number }}: </span>@endif{{ $ep->title }}</a>
                            @if ($ep->published_at)<p class="pd-meta" style="margin-top:.2rem">{{ $ep->published_at->locale('pl')->translatedFormat('j F Y') }}@if ($ep->is_premium) · dla subskrybentów @endif</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <a href="{{ route('podcasts.index') }}" class="pd-back">← Wszystkie odcinki</a>
    </div>
@endsection
