@extends('layouts.site')

@section('title', 'Podcasty — ' . $siteSettings->site_name)
@section('meta_description', 'Słuchaj podcastów ' . $siteSettings->site_name . ' — dostępność cyfrowa, edukacja i szkolenia.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => null],
    ]])
@endsection

{{--
    Podcasty w stylu FEER: jasny nagłówek, najnowszy odcinek wyróżniony (okładka + tekst + przycisk „Słuchaj"),
    pozostałe jako płaskie wiersze: duży numer odcinka, tytuł, opis, data. Premium jako tekstowa plakietka (ink na amber-100 ≥ 4,5:1).
--}}
@section('content')
    @php
        $featured = $podcasts->onFirstPage() ? $podcasts->first() : null;
        $rest = $featured ? $podcasts->slice(1) : $podcasts;
        $listen = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Podcasty</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">Słuchaj rozmów o dostępności cyfrowej, edukacji i technologiach wspierających inkluzję.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($podcasts->isEmpty())
            <p class="text-muted">Nie ma jeszcze żadnych odcinków. Zajrzyj wkrótce.</p>
        @else
            @if ($featured)
                @php $fCover = $featured->getFirstMediaUrl('cover'); @endphp
                <article class="mb-12 grid items-center gap-8 {{ $fCover ? 'lg:grid-cols-[22rem_minmax(0,1fr)]' : '' }}">
                    @if ($fCover)
                        <img src="{{ $fCover }}" alt="" class="aspect-square w-full max-w-sm rounded-lg object-cover lg:max-w-none">
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                            Najnowszy odcinek @if ($featured->episode_number) · odc. {{ $featured->episode_number }}@endif
                            @if ($featured->published_at)<span class="font-medium text-muted"> · <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->translatedFormat('j F Y') }}</time></span>@endif
                            @if ($featured->is_premium)<span class="ml-2 rounded-sm bg-amber-100 px-1.5 py-0.5 text-amber-900">Premium</span>@endif
                        </p>
                        <h2 class="mt-3 text-2xl font-extrabold leading-tight text-ink md:text-4xl">{{ $featured->title }}</h2>
                        @if ($featured->description)<p class="mt-4 line-clamp-4 text-lg leading-relaxed text-muted">{{ $featured->description }}</p>@endif
                        <a href="{{ route('podcasts.show', $featured) }}" class="{{ $listen }} mt-6">Słuchaj odcinka<span class="sr-only">: {{ $featured->title }}</span></a>
                    </div>
                </article>
            @endif

            @if ($rest->isNotEmpty())
                <h2 id="odcinki-heading" class="mb-5 text-2xl font-bold text-ink">Wcześniejsze odcinki</h2>
                <ul class="space-y-3" role="list" aria-labelledby="odcinki-heading">
                    @foreach ($rest as $podcast)
                        @php $cover = $podcast->getFirstMediaUrl('cover'); @endphp
                        <li>
                            <article class="feer-card group relative flex items-center gap-5 rounded-md bg-gray-50 p-4 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand sm:p-5">
                                <div class="hidden w-14 shrink-0 text-center sm:block" aria-hidden="true">
                                    <span class="block text-3xl font-extrabold leading-none text-ink">{{ $podcast->episode_number ?: '·' }}</span>
                                    <span class="mt-1 block text-[11px] font-bold uppercase tracking-wide text-muted">odc.</span>
                                </div>
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="" loading="lazy" class="hidden h-20 w-20 flex-none rounded-md object-cover md:block">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted">
                                        @if ($podcast->episode_number)<span class="sm:hidden">Odc. {{ $podcast->episode_number }} · </span>@endif
                                        @if ($podcast->published_at)<time datetime="{{ $podcast->published_at->toDateString() }}">{{ $podcast->published_at->format('d.m.Y') }}</time>@endif
                                        @if ($podcast->is_premium)<span class="ml-2 rounded-sm bg-amber-100 px-1.5 py-0.5 text-amber-900">Premium</span>@endif
                                    </p>
                                    <h3 class="mt-1 text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">
                                        <a href="{{ route('podcasts.show', $podcast) }}" class="stretched-link focus-visible:outline-none">{{ $podcast->title }}</a>
                                    </h3>
                                    @if ($podcast->description)<p class="mt-1 line-clamp-2 text-sm leading-relaxed text-muted">{{ $podcast->description }}</p>@endif
                                </div>
                                <span class="flex-none text-sm font-bold text-brand-dark" aria-hidden="true">Słuchaj →</span>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-10">{{ $podcasts->links() }}</div>
        @endif
    </div>
@endsection
