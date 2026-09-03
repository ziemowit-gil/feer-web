@extends('layouts.site')

@section('title', $material->title . ' — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-6 text-2xl font-bold text-ink">{{ $material->title }}</h1>

        @if ($material->youtubeId())
            <div class="aspect-video overflow-hidden rounded-xl border border-gray-200">
                <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $material->youtubeId() }}"
                    title="{{ $material->title }}" allowfullscreen></iframe>
            </div>
        @else
            <a href="{{ $material->video_url }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-play" aria-hidden="true"></i> Obejrzyj nagranie
            </a>
        @endif
    </section>
@endsection
