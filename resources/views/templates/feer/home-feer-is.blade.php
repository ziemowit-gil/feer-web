{{--
    Wariant motywu FEER „FEER IS”: portal usług w układzie jak gov.pl, ale z nagłówkiem, stopką, paletą i krojem FEER
    (te same partiale, theme-feer). Strona główna: zakładki odbiorców z siatką usług, aktualności (wyróżniony wpis,
    dwa kolejne i trzy karty), a niżej te same sekcje co w standardowym FEER (szkolenia, działania, wsparcie).
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
        {{-- Układ jak strona „Działania”: po lewej lista kategorii z licznikami (pionowe zakładki), po prawej nagłówek kategorii
             (ikona, tytuł ze strzałką, kreska w kolorze marki) i płaskie wiersze usług. Układ kolumn w stylu poniżej, bo wdrożenie nie buduje CSS. --}}
        <style>
            .feer-is-split { display: grid; gap: 2.5rem; }
            @media (min-width: 1024px) { .feer-is-split { grid-template-columns: 15rem minmax(0, 1fr); } }
        </style>
        <section class="mx-auto max-w-6xl px-4 py-12" aria-labelledby="feer-is-services-heading" x-data="{ tab: 0 }">
            <h2 id="feer-is-services-heading" class="sr-only">Usługi i informacje według odbiorcy</h2>
            <div class="feer-is-split">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-ink" id="feer-is-tabs-label">Kategorie</p>
                    <div role="tablist" aria-orientation="vertical" aria-labelledby="feer-is-tabs-label" class="flex flex-col gap-1">
                        @foreach ($servicesTabs as $i => $tab)
                            <button type="button" role="tab" id="feer-is-tab-{{ $i }}" aria-controls="feer-is-panel-{{ $i }}"
                                :aria-selected="(tab === {{ $i }}).toString()" :tabindex="tab === {{ $i }} ? 0 : -1"
                                @click="tab = {{ $i }}"
                                @keydown.arrow-down.prevent="tab = (tab + 1) % {{ $servicesTabs->count() }}; $nextTick(() => document.getElementById('feer-is-tab-' + tab).focus())"
                                @keydown.arrow-up.prevent="tab = (tab + {{ $servicesTabs->count() - 1 }}) % {{ $servicesTabs->count() }}; $nextTick(() => document.getElementById('feer-is-tab-' + tab).focus())"
                                :class="tab === {{ $i }} ? 'bg-gray-100 text-brand-dark' : 'text-ink hover:bg-gray-50'"
                                class="flex w-full items-center justify-between gap-3 rounded-md px-4 py-3 text-left text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <span>{{ $tab['label'] }}</span>
                                <span class="shrink-0">{{ $tab['services']->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-6">
                        <a href="{{ $allUrl }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $allLabel }} →</a>
                    </p>
                </div>

                <div class="min-w-0">
                    @foreach ($servicesTabs as $i => $tab)
                        <div role="tabpanel" id="feer-is-panel-{{ $i }}" aria-labelledby="feer-is-tab-{{ $i }}" x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif tabindex="0" class="focus-visible:outline-none">
                            <div class="mb-6 flex items-center gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-brand-light text-brand-dark" aria-hidden="true">
                                    {{ icon_html($tab['icon'] ?: 'mi-groups', 'text-2xl') }}
                                </span>
                                <div>
                                    <h3 class="text-2xl font-bold leading-tight text-ink">
                                        @if (filled($tab['url'] ?? null))
                                            <a href="{{ $tab['url'] }}" class="hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $tab['label'] }} <span class="text-brand-dark" aria-hidden="true">→</span></a>
                                        @else
                                            {{ $tab['label'] }}
                                        @endif
                                    </h3>
                                    <span class="mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                                </div>
                            </div>

                            <ul class="space-y-2" role="list">
                                @foreach ($tab['services'] as $service)
                                    <li class="group relative flex items-center gap-4 rounded-md bg-gray-50 p-4 transition-colors hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-light text-brand-dark" aria-hidden="true">
                                            {{ icon_html($service['icon'] ?: 'mi-description', 'text-2xl') }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-base font-bold leading-snug">
                                                <a href="{{ $service['url'] }}" class="stretched-link text-ink hover:text-brand-dark focus-visible:outline-none">{{ $service['title'] }}</a>
                                            </h4>
                                            @if (filled($service['description']))
                                                <p class="mt-1 text-sm leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($service['description'], 160) }}</p>
                                            @endif
                                        </div>
                                        <span class="shrink-0 text-lg text-brand-dark" aria-hidden="true">→</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featured)
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
