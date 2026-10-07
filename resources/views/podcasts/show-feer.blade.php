@extends('layouts.site')

@section('title', $podcast->title . ' — Podcasty — ' . $siteSettings->site_name)
@section('meta_description', $podcast->description)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => route('podcasts.index')],
        ['label' => $podcast->title, 'url' => null],
    ]])
@endsection

{{-- Odcinek w stylu FEER: jasny nagłówek z okładką, numerem i datą; odtwarzacz (partials.podcast-player) w jasnoszarym bloku. --}}
@section('content')
    @php $cover = $podcast->getFirstMediaUrl('cover'); @endphp

    <section class="bg-gray-50">
        <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-12 md:py-16 {{ $cover ? 'md:grid-cols-[16rem_minmax(0,1fr)]' : '' }}">
            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $podcast->title }}" class="aspect-square w-full max-w-xs rounded-lg object-cover">
            @endif
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                    Podcast @if ($podcast->episode_number) · odcinek {{ $podcast->episode_number }}@endif
                    @if ($podcast->published_at)<span class="font-medium text-muted"> · <time datetime="{{ $podcast->published_at->toDateString() }}">{{ $podcast->published_at->translatedFormat('j F Y') }}</time></span>@endif
                    @if ($podcast->is_premium)<span class="ml-2 rounded-sm bg-amber-100 px-1.5 py-0.5 text-amber-900">Premium</span>@endif
                </p>
                <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{{ $podcast->title }}</h1>
                @if ($podcast->description)<p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">{{ $podcast->description }}</p>@endif
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-10">
        @include('partials.podcast-player', ['podcast' => $podcast, 'canPlay' => $canPlay])

        <p class="mt-8"><a href="{{ route('podcasts.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Wszystkie odcinki</a></p>
    </div>
@endsection
