{{--
    Wariant motywu FEER „FEER IS”: portal usług w układzie kafli (po jednym dużym kafelku na odbiorcę, jak na stronie ZDMK; wcześniej zakładki i siatka usług jak gov.pl), ale z nagłówkiem, stopką, paletą i krojem FEER (te same partiale,
    theme-feer). Niżej aktualności (opcjonalnie), szkolenia z zapisami, moduły szkoleniowe i wsparcie.
    Klasy tylko takie, które już są w zbudowanym CSS (wdrożenie nie buduje stylów).
--}}
@extends('layouts.site')

@section('content')
    @php
        $newsItems = $newsItems ?? collect();
        $featured = $newsItems->first();
        $sideNews = $newsItems->slice(1, 2);
        $cardNews = $newsItems->slice(3, 3);
        $allUrl = $siteSettings->services_all_url ?: (\Illuminate\Support\Facades\Route::has('projects.index') ? route('projects.index') : site_route('home'));
        $allLabel = $siteSettings->services_all_label ?: 'Zobacz wszystkie usługi';
    @endphp
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>

    {{-- Slider jak na stronie głównej FEER; slajdy i misja dziedziczone z witryny głównej, gdy ta witryna nie ma własnych. --}}
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.feer.partials.hero')
    @endif

    @if ($servicesTabs->isNotEmpty())
        {{-- Kafle: po jednym dużym kafelku na odbiorcę (ikona, nazwa, liczba usług), bez zakładek i bez siatki usług. Kafel
             prowadzi na stronę odbiorcy, gdzie są jego usługi. Kolory marki (brand, brand-dark, ink) z białym tekstem
             dają kontrast ≥ 4,5:1. Wymiary inline, bo wdrożenie nie buduje CSS. --}}
        <style>
            .feer-is-tiles { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .feer-is-tile { display: flex; align-items: center; gap: 1.25rem; min-height: 8rem; padding: 1.5rem; color: #fff; text-decoration: none; }
            .feer-is-tile:hover, .feer-is-tile:focus-visible { filter: brightness(.88); }
            .feer-is-tile:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
            .feer-is-tile-icon { flex: none; width: 3.5rem; text-align: center; font-size: 2.75rem; line-height: 1; }
            .feer-is-tile-title { display: block; font-size: 1.25rem; font-weight: 700; line-height: 1.25; }
            .feer-is-tile-count { display: block; margin-top: .35rem; font-size: .875rem; }
            @media (max-width: 900px) { .feer-is-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 560px) { .feer-is-tiles { grid-template-columns: 1fr; } .feer-is-tile { min-height: 6rem; } }
        </style>
        <section aria-labelledby="feer-is-services-heading">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <h2 id="feer-is-services-heading" class="text-2xl font-bold text-ink">W czym możemy pomóc</h2>
                <ul class="feer-is-tiles mt-6" role="list">
                    @foreach ($servicesTabs as $i => $tab)
                        @php
                            $tileBg = ['bg-brand', 'bg-brand-dark', 'bg-ink'][$i % 3];
                            $n = $tab['services']->count();
                            $nLabel = $n === 1 ? '1 usługa' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $n.' usługi' : $n.' usług');
                        @endphp
                        <li>
                            <a href="{{ $tab['url'] ?? $allUrl }}" class="feer-is-tile {{ $tileBg }}" style="height: 100%">
                                <span class="feer-is-tile-icon" aria-hidden="true">{{ icon_html(filled($tab['icon'] ?? null) ? $tab['icon'] : 'mi-description', 'text-4xl') }}</span>
                                <span>
                                    <span class="feer-is-tile-title">{{ $tab['label'] }}</span>
                                    <span class="feer-is-tile-count">{{ $nLabel }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8 text-center">
                    <a href="{{ $allUrl }}" class="inline-flex min-h-12 items-center rounded-md bg-brand-dark px-6 text-sm font-bold uppercase tracking-wide text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">{{ $allLabel }}</a>
                </div>
            </div>
        </section>
    @endif

    @if ($featured && $siteSettings->isHomepageSectionEnabled('news'))
        <section class="pb-12" aria-labelledby="feer-is-news-heading">
            <div class="mx-auto max-w-6xl px-4">
                <div class="grid gap-8 lg:grid-cols-3">
                    <article class="group relative lg:col-span-2">
                        @if ($featured->image_url)
                            <img src="{{ $featured->image_url }}" alt="{{ $featured->image_alt ?? '' }}" class="aspect-[16/9] w-full rounded-md object-cover">
                        @endif
                        <div class="mt-4">
                            <p class="text-sm text-ink">
                                <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->format('d.m.Y') }}</time>
                                @if ($featured->category)<span aria-hidden="true"> | </span>{{ $featured->category->name }}@endif
                            </p>
                            <h3 class="mt-1 text-xl font-bold leading-snug">
                                <a href="{{ site_route('news.show', $featured) }}" class="stretched-link text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">{{ $featured->title }}</a>
                            </h3>
                        </div>
                    </article>

                    <div>
                        <h2 id="feer-is-news-heading" class="text-2xl font-bold text-ink">Aktualności</h2>
                        <ul class="mt-6 space-y-6" role="list">
                            @foreach ($sideNews as $item)
                                <li>
                                    <p class="text-sm text-ink">
                                        <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                                        @if ($item->category)<span aria-hidden="true"> | </span>{{ $item->category->name }}@endif
                                    </p>
                                    <a href="{{ site_route('news.show', $item) }}" class="mt-1 block text-base font-bold leading-snug text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $item->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-6">
                            <a href="{{ site_route('news.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Wszystkie aktualności →</a>
                        </p>
                    </div>
                </div>

                @if ($cardNews->isNotEmpty())
                    <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        @foreach ($cardNews as $item)
                            <li>
                                <article class="group relative">
                                    @if ($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->image_alt ?? '' }}" loading="lazy" class="aspect-[16/10] w-full rounded-md object-cover">
                                    @endif
                                    <p class="mt-3 text-sm text-ink">
                                        <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                                        @if ($item->category)<span aria-hidden="true"> | </span>{{ $item->category->name }}@endif
                                    </p>
                                    <h3 class="mt-1 text-base font-bold leading-snug">
                                        <a href="{{ site_route('news.show', $item) }}" class="stretched-link text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    @include('templates.feer.partials.courses-upcoming')

    {{-- Moduły szkoleniowe: wyróżnione szkolenia i najbliższe terminy (typy stron „Szkolenie”). Bez sekcji „Nasze działania”. --}}
    @include('templates.feer.partials.training-modules')
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif
    @include('templates.feer.partials.support-cta')
@endsection
