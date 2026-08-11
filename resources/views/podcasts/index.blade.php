@extends('layouts.site')

@section('title', 'Podcasty — ' . $siteSettings->site_name)
@section('meta_description', 'Słuchaj podcastów ' . $siteSettings->site_name . ' — dostępność cyfrowa, edukacja i szkolenia.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Podcasty</h1>
        <p class="mb-8 max-w-2xl text-muted">Słuchaj rozmów o dostępności cyfrowej, edukacji i technologiach wspierających inkluzję.</p>

        @if ($podcasts->isEmpty())
            <p class="text-muted">Nie ma jeszcze żadnych odcinków. Zajrzyj wkrótce.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($podcasts as $podcast)
                    @php $cover = $podcast->getFirstMediaUrl('cover'); @endphp
                    <article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                        <a href="{{ route('podcasts.show', $podcast) }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                            @if ($cover)
                                <img src="{{ $cover }}" alt="" class="h-44 w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-44 w-full items-center justify-center bg-brand-light">
                                    <svg class="h-12 w-12 text-brand opacity-40" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 0 1 6 0v8.25a3 3 0 0 1-3 3Z"/>
                                    </svg>
                                </div>
                            @endif
                        </a>

                        <div class="flex flex-1 flex-col p-4">
                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-muted">
                                @if ($podcast->episode_number)
                                    <span class="font-medium text-brand">Odc. {{ $podcast->episode_number }}</span>
                                    <span aria-hidden="true">·</span>
                                @endif
                                @if ($podcast->published_at)
                                    <time datetime="{{ $podcast->published_at->toDateString() }}">
                                        {{ $podcast->published_at->format('d.m.Y') }}
                                    </time>
                                @endif
                                @if ($podcast->is_premium)
                                    <span class="inline-flex items-center gap-1 rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">
                                        <svg class="h-3 w-3" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .67.415l2.302 4.667 5.145.748a.75.75 0 0 1 .416 1.279l-3.724 3.629.879 5.122a.75.75 0 0 1-1.088.79L10 15.547l-4.6 2.419a.75.75 0 0 1-1.088-.79l.879-5.122L1.467 8.11a.75.75 0 0 1 .416-1.279l5.145-.748L9.33 1.415A.75.75 0 0 1 10 1Z" clip-rule="evenodd"/></svg>
                                        Premium
                                    </span>
                                @endif
                            </div>

                            <h3 class="mb-2 font-bold leading-snug text-ink">
                                <a href="{{ route('podcasts.show', $podcast) }}"
                                    class="hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    {{ $podcast->title }}
                                </a>
                            </h3>

                            @if ($podcast->description)
                                <p class="mb-4 line-clamp-3 text-sm text-muted">{{ $podcast->description }}</p>
                            @endif

                            <div class="mt-auto">
                                <a href="{{ route('podcasts.show', $podcast) }}"
                                    aria-label="Słuchaj: {{ $podcast->title }}"
                                    class="inline-flex items-center gap-2 rounded border border-gray-300 px-4 py-2 text-xs font-bold uppercase tracking-wide text-ink transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.344-5.891a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z"/></svg>
                                    Słuchaj
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $podcasts->links() }}
            </div>
        @endif
    </section>
@endsection
