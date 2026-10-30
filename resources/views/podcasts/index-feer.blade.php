@extends('layouts.site')

@section('title', 'Podcasty — ' . $siteSettings->site_name)
@section('meta_description', 'Słuchaj podcastów ' . $siteSettings->site_name . ' — dostępność cyfrowa, edukacja i technologie wspierające inkluzję.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Podcasty', 'url' => null],
    ]])
@endsection

{{--
    Podcasty w stylu FEER: tytuł z akcentem marki, najnowszy odcinek z odtwarzaczem od razu na stronie (bez przechodzenia dalej),
    pozostałe odcinki jako płaskie wiersze (numer, tytuł, data, opis), obok kolumna „O podcaście”. Odcinki dla subskrybentów
    oznaczone zwykłym tekstem z ikoną kłódki (bez plakietek). Zwykły CSS (klasy pd-*) — nie zależy od przebudowy Tailwinda.
--}}
@section('content')
    @php
        $featured = $podcasts->onFirstPage() ? $podcasts->first() : null;
        $rest = $featured ? $podcasts->slice(1) : $podcasts;
        $playable = $playable ?? [];
        $hasPremium = $podcasts->getCollection()->contains('is_premium', true);
        $date = fn ($p) => $p->published_at ? '<time datetime="'.$p->published_at->toDateString().'">'.e($p->published_at->locale('pl')->translatedFormat('j F Y')).'</time>' : '';
    @endphp
    <style>
        .pd-wrap { display: grid; gap: 2.5rem; }
        @media (min-width: 1024px) { .pd-wrap { grid-template-columns: minmax(0, 1fr) 20rem; } }
        .pd-bar { display: block; width: 3.5rem; height: .25rem; background: #1e6dff; margin: 1rem 0 1.25rem; }
        .pd-meta { margin: 0; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #4b5563; }
        .pd-meta .pd-strong { color: #1752bf; }
        .pd-feat { display: grid; gap: 1.5rem; align-items: start; }
        @media (min-width: 640px) { .pd-feat.has-cover { grid-template-columns: 12rem minmax(0, 1fr); } }
        .pd-cover { width: 100%; max-width: 12rem; aspect-ratio: 1; object-fit: cover; border-radius: .75rem; }
        .pd-h2 { margin: .5rem 0 .75rem; font-size: clamp(1.6rem, 3vw, 2.25rem); line-height: 1.15; font-weight: 800; color: #1d1d1a; }
        .pd-desc { margin: 0 0 1.25rem; font-size: 1.05rem; line-height: 1.7; color: #1d1d1a; }
        .pd-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
        .pd-item { position: relative; display: flex; gap: 1.1rem; align-items: flex-start; border-radius: .75rem; background: #f3f4f6; padding: 1rem 1.25rem; }
        .pd-item:hover { background: #e8f0ff; }
        .pd-item:focus-within { outline: 3px solid #1752bf; outline-offset: 2px; }
        .pd-num { flex: none; width: 3rem; text-align: center; font-size: 1.6rem; font-weight: 800; line-height: 1; color: #1752bf; }
        .pd-num small { display: block; margin-top: .25rem; font-size: .65rem; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; }
        .pd-item h3 { margin: .25rem 0 .3rem; font-size: 1.1rem; line-height: 1.35; font-weight: 700; }
        .pd-item h3 a { color: #1d1d1a; text-decoration: none; }
        .pd-item h3 a::after { content: ''; position: absolute; inset: 0; border-radius: .75rem; }
        .pd-item h3 a:focus-visible { outline: none; }
        .pd-item p.pd-sum { margin: 0; font-size: .95rem; line-height: 1.6; color: #374151; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .pd-go { flex: none; align-self: center; font-weight: 700; color: #1752bf; }
        .pd-aside { display: grid; gap: 1rem; align-content: start; }
        .pd-box { border-radius: .75rem; background: #f3f4f6; padding: 1.25rem; color: #1d1d1a; }
        .pd-box h2 { margin: 0 0 .5rem; font-size: 1.1rem; font-weight: 800; }
        .pd-box p { margin: 0; line-height: 1.6; font-size: .95rem; }
        .pd-box ul { margin: .25rem 0 0; padding-left: 1.1rem; line-height: 1.7; font-size: .95rem; }
        .pd-box.is-brand { background: #e8f0ff; }
        .pd-cta { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-top: .9rem; border-radius: .6rem; background: #1752bf; color: #fff; font-weight: 700; padding: 0 1.1rem; text-decoration: none; }
        .pd-cta:hover { background: #1d1d1a; }
        .pd-cta:focus-visible, .pd-feat-link:focus-visible { outline: 3px solid #1752bf; outline-offset: 2px; }
        .pd-feat-link { font-weight: 700; color: #1752bf; text-decoration: underline; text-underline-offset: 3px; }
        .pd-section-h { margin: 3rem 0 1rem; font-size: 1.5rem; font-weight: 800; color: #1d1d1a; }
    </style>

    <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
        <header class="max-w-3xl">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Podcasty</h1>
            <span class="pd-bar" aria-hidden="true"></span>
            <p class="text-lg leading-relaxed text-ink">Rozmowy o dostępności cyfrowej, edukacji i technologiach wspierających inkluzję. Słuchaj od razu na stronie, w swoim tempie.</p>
        </header>

        <div class="pd-wrap mt-10">
            <div class="min-w-0">
                @if ($podcasts->isEmpty())
                    <p class="pd-box">Nie ma jeszcze żadnych odcinków. Zajrzyj wkrótce albo zapisz się na newsletter, damy znać o pierwszym.</p>
                @else
                    @if ($featured)
                        @php $fCover = $featured->getFirstMediaUrl('cover'); @endphp
                        <article class="pd-feat {{ $fCover ? 'has-cover' : '' }}" aria-labelledby="pd-featured-title">
                            @if ($fCover)<img src="{{ $fCover }}" alt="" class="pd-cover">@endif
                            <div class="min-w-0">
                                <p class="pd-meta">
                                    <span class="pd-strong">Najnowszy odcinek</span>
                                    @if ($featured->episode_number) · odcinek {{ $featured->episode_number }}@endif
                                    @if ($featured->published_at) · {!! $date($featured) !!}@endif
                                    @if ($featured->is_premium) · <i class="fa-solid fa-lock" aria-hidden="true"></i> dla subskrybentów @endif
                                </p>
                                <h2 id="pd-featured-title" class="pd-h2"><a href="{{ route('podcasts.show', $featured) }}" class="pd-feat-link" style="color:inherit;text-decoration:none">{{ $featured->title }}</a></h2>
                                @if ($featured->description)<p class="pd-desc">{{ \Illuminate\Support\Str::limit($featured->description, 320) }}</p>@endif
                                @include('partials.podcast-player-feer', ['podcast' => $featured, 'canPlay' => in_array($featured->id, $playable, true), 'compact' => true])
                                <p class="mt-4"><a href="{{ route('podcasts.show', $featured) }}" class="pd-feat-link">Opis odcinka<span class="sr-only">: {{ $featured->title }}</span></a></p>
                            </div>
                        </article>
                    @endif

                    @if ($rest->isNotEmpty())
                        <h2 id="pd-list-h" class="pd-section-h">{{ $featured ? 'Wcześniejsze odcinki' : 'Odcinki' }}</h2>
                        <ul class="pd-list" role="list" aria-labelledby="pd-list-h">
                            @foreach ($rest as $podcast)
                                <li class="pd-item">
                                    <span class="pd-num" aria-hidden="true">{{ $podcast->episode_number ?: '•' }}@if ($podcast->episode_number)<small>odc.</small>@endif</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="pd-meta">
                                            @if ($podcast->episode_number)<span class="sr-only">Odcinek {{ $podcast->episode_number }} · </span>@endif
                                            {!! $date($podcast) !!}
                                            @if ($podcast->is_premium) · <i class="fa-solid fa-lock" aria-hidden="true"></i> dla subskrybentów @endif
                                        </p>
                                        <h3><a href="{{ route('podcasts.show', $podcast) }}">{{ $podcast->title }}</a></h3>
                                        @if ($podcast->description)<p class="pd-sum">{{ $podcast->description }}</p>@endif
                                    </div>
                                    <span class="pd-go" aria-hidden="true">Słuchaj →</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-10">{{ $podcasts->links() }}</div>
                @endif
            </div>

            <aside class="pd-aside" aria-label="O podcaście">
                <section class="pd-box">
                    <h2>O podcaście</h2>
                    <p>Rozmawiamy o tym, jak technologia może otwierać drzwi: o dostępności stron i dokumentów, narzędziach dla osób z niepełnosprawnościami i o edukacji, która nikogo nie zostawia z tyłu.</p>
                </section>
                <section class="pd-box">
                    <h2>Jak słuchać</h2>
                    <ul>
                        <li>Odtwarzacz obsłużysz klawiaturą i czytnikiem ekranu.</li>
                        <li>Możesz przyspieszyć nagranie do 2×.</li>
                        <li>Przyciski cofają o 15 s i przewijają o 30 s.</li>
                    </ul>
                </section>
                @if ($hasPremium)
                    <section class="pd-box">
                        <h2><i class="fa-solid fa-lock" aria-hidden="true"></i> Odcinki dla subskrybentów</h2>
                        <p>Część odcinków jest dostępna po zalogowaniu na konto z subskrypcją.</p>
                    </section>
                @endif
                <section class="pd-box is-brand">
                        <h2>Nie przegap odcinka</h2>
                        <p>Zapisz się na newsletter, a damy znać, gdy pojawi się nowa rozmowa.</p>
                        <a href="{{ url('/newsletter') }}" class="pd-cta">Zapisz się <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </section>
            </aside>
        </div>
    </div>
@endsection
