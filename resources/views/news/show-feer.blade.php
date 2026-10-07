@extends('layouts.site')

{{-- Widok FEER: nagłówek z kategorią/datą/zajawką, treść i narzędzia artykułu w bocznej kolumnie (wszystkie funkcje jak w news.show). --}}
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
        ['label' => 'Aktualności', 'url' => site_route('news.index')],
        $news->category ? ['label' => $news->category->name, 'url' => null] : null,
        ['label' => $news->title, 'url' => null],
    ])])
@endsection

@php
    // Sprawdzamy uprawnienia po stronie serwera — Alpine dostaje tylko URL do
    // zapisu, nie żadnych uprawnień. Faktyczna autoryzacja jest w quickUpdate().
    $canQuickEdit = auth()->check() && auth()->user()->canAccessModule('news');
@endphp

@section('content')
    <style>
        /* Mniejsze obrazy w treści aktualności (FEER): maks. 28 rem, proporcje zachowane. */
        .news-feer-body img { max-width: min(100%, 28rem); height: auto; border-radius: .5rem; }
        .news-feer-body figure { max-width: min(100%, 28rem); }
    </style>

    @if ($canQuickEdit)
        <div x-data="newsInlineEditor(
            @js(['title' => $news->title, 'excerpt' => $news->excerpt, 'is_published' => $news->is_published]),
            '{{ route('admin.newsy.szybka-edycja', $news) }}',
            @js(['engine' => $siteSettings->contentEditorValue(), 'uploadUrl' => route('admin.multimedia.upload-ajax'), 'richContent' => ! \App\Support\ShortcodeParser::has($news->content)])
        )">
            @include('partials.news-editor-bar')
    @endif

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
        $imgAlt        = $news->image_alt ?: 'Zdjęcie ilustracyjne';
        $tool = 'inline-flex min-h-11 w-full items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10" x-data="audioPlayer()">
        @if ($news->is_legacy)
            @include('partials.legacy-notice')
        @elseif ($news->is_archived)
            @include('partials.archival-notice', ['date' => $news->published_at])
        @endif

        @include('partials.etr-toggle', ['etr' => $news->etr, 'title' => $news->title])

        <div x-show="!etr" x-cloak>
            <a href="{{ site_route('news.index') }}" class="mb-6 inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Wszystkie aktualności</a>

            <header class="mb-10 max-w-4xl">
                <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                    {{ $news->category?->name ?? 'Aktualności' }}
                    <span class="font-medium text-muted"> · <time datetime="{{ $news->published_at->toDateString() }}">{{ $news->published_at->translatedFormat('j F Y') }}</time>
                    @if ($news->updated_at->gt($news->published_at)) · zaktualizowano {{ $news->updated_at->format('d.m.Y') }}@endif</span>
                </p>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">
                    @if ($canQuickEdit)
                        <span x-show="!editMode" x-cloak>{{ $news->title }}</span>
                        <input x-show="editMode" x-cloak x-model="form.title" type="text" maxlength="255"
                            class="w-full rounded border-gray-400 text-3xl font-bold focus:border-brand focus:ring-brand" aria-label="Tytuł aktualności">
                    @else
                        {{ $news->title }}
                    @endif
                </h1>
                @if ($news->excerpt)
                    <p class="mt-5 text-xl leading-relaxed text-muted">{{ $news->excerpt }}</p>
                @endif
            </header>

            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_16rem] lg:gap-14">
                <div class="min-w-0">
                    @if ($img && $articleLayout !== 'none')
                        <img src="{{ $img }}" alt="{{ $imgAlt }}" data-lightbox class="mb-8 aspect-[16/10] w-full max-w-md rounded-lg object-cover">
                    @endif

                    <div id="article-text" data-news-content class="news-feer-body prose prose-lg max-w-3xl text-ink">@shortcodes($news->content)</div>

                    @include('partials.attachments-list', ['attachments' => $news->attachments])
                </div>

                <aside class="space-y-8 print:hidden lg:sticky lg:top-6 lg:self-start" aria-label="Opcje artykułu">
                    <div class="space-y-2">
                        <button type="button" x-show="supported" x-cloak @click="play()" :aria-pressed="playing.toString()" class="{{ $tool }}"
                                :class="playing ? 'bg-ink text-white hover:bg-ink' : ''">
                            <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-volume-high'" aria-hidden="true"></i>
                            <span x-text="playing ? 'Zatrzymaj odczyt' : 'Odsłuchaj artykuł'"></span>
                        </button>
                        <button type="button" onclick="window.print()" class="{{ $tool }}"><i class="fa-solid fa-print" aria-hidden="true"></i> Drukuj</button>
                        <a href="{{ site_route('news.pdf', $news) }}" target="_blank" rel="noopener" class="{{ $tool }}">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Tekst w PDF<span class="sr-only"> (otwiera się w nowej karcie)</span>
                        </a>
                    </div>

                    @if ($news->tags->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">Tagi</p>
                            <ul class="flex flex-wrap gap-2" role="list">
                                @foreach ($news->tags as $tag)
                                    <li class="rounded-md bg-gray-100 px-3 py-1 text-sm font-medium text-ink">{{ $tag->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

    @if ($canQuickEdit)
        </div>
    @endif
@endsection
