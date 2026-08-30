@extends('layouts.site')

@section('title', ($page->title ?? 'Dołącz do nas') . ' — ' . $siteSettings->site_name)
@section('meta_description', $page?->hub_intro ?? 'Oferty pracy i wolontariatu w ' . $siteSettings->site_name . '. Znajdź sposób na działanie razem z nami.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title ?? 'Dołącz do nas', 'url' => null],
    ]])
@endsection

@section('content')
@php
    $title    = $page->title   ?? 'Dołącz do nas';
    $intro    = $page->hub_intro ?? null;
    $hubLinks = collect($page->hub_links ?? [])->filter(fn ($l) => filled($l['label'] ?? null) && filled($l['url'] ?? null))->values();

    $gradientMap = [
        'blue'   => 'linear-gradient(135deg, #1a56a4 0%, #2563eb 100%)',
        'dark'   => 'linear-gradient(135deg, #1f2937 0%, #374151 100%)',
        'green'  => 'linear-gradient(135deg, #166534 0%, #16a34a 100%)',
        'purple' => 'linear-gradient(135deg, #581c87 0%, #7e22ce 100%)',
        'orange' => 'linear-gradient(135deg, #c2410c 0%, #f97316 100%)',
        'red'    => 'linear-gradient(135deg, #991b1b 0%, #ef4444 100%)',
    ];
    $gradientFallback = array_values($gradientMap);
@endphp

@php
    $joinTabs = collect([
        ['id' => 'skroty',      'label' => 'Na skróty',    'show' => $hubLinks->isNotEmpty()],
        ['id' => 'praca',       'label' => 'Oferty pracy', 'show' => $jobsActive],
        ['id' => 'wolontariat', 'label' => 'Wolontariat',  'show' => $volunteeringActive],
    ])->filter(fn ($tab) => $tab['show'])->values();

    $joinTabIds = $joinTabs->pluck('id')->all();
@endphp

<noscript>
    {{-- Bez JS panele nie przełączają się — pokazujemy je wszystkie. --}}
    <style>[x-cloak] { display: block !important; }</style>
</noscript>

<div x-data="sectionTabs(@js($joinTabIds))">

    {{-- Ciemny pas z tytułem --}}
    <section class="bg-ink">
        <div class="mx-auto max-w-6xl px-4 py-10">
            <h1 class="text-3xl font-bold uppercase tracking-wide text-white sm:text-4xl">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-3 max-w-3xl text-white/90">{{ $intro }}</p>
            @endif
        </div>
    </section>

    @include('partials.tab-strip', [
        'tabItems' => $joinTabs->all(),
        'tabsLabel' => 'Sekcje strony „Dołącz do nas"',
    ])

    <div class="mx-auto max-w-6xl px-4 py-10">

        @if ($hubLinks->isNotEmpty())
            <div id="panel-skroty" role="tabpanel" aria-labelledby="tab-skroty" tabindex="0"
                 x-show="tab === 'skroty'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                <h2 class="mb-6 text-2xl font-bold text-ink">Na skróty</h2>
{{-- Metro kafelki (edytowalne z admina) --}}
@if ($hubLinks->isNotEmpty())
    <ul class="grid gap-5 {{ $hubLinks->count() === 2 ? 'sm:grid-cols-2' : ($hubLinks->count() === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4') }}" role="list">
        @foreach ($hubLinks as $i => $link)
            @php
                $colorKey = $link['color'] ?? null;
                $grad = ($colorKey && isset($gradientMap[$colorKey])) ? $gradientMap[$colorKey] : $gradientFallback[$i % count($gradientFallback)];
                $ctaLabel = filled($link['cta_label'] ?? null) ? $link['cta_label'] : 'Dowiedz się więcej';
            @endphp
            <li>
                <a href="{{ $link['url'] }}"
                   class="group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-2xl p-8 text-white shadow-md transition hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                   style="background: {{ $grad }}">
                    <span class="relative z-10">
                        @if (filled($link['icon'] ?? null))
                            <span class="mb-3 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur" aria-hidden="true">
                                <i class="{{ $link['icon'] }} text-xl text-white"></i>
                            </span>
                        @endif
                        <span class="block text-2xl font-extrabold leading-tight">{{ $link['label'] }}</span>
                        @if (filled($link['description'] ?? null))
                            <span class="mt-1 block text-sm text-white/80">{{ $link['description'] }}</span>
                        @endif
                        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 transition group-hover:gap-3">
                            {{ $ctaLabel }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
            </div>
        @endif

        @if ($jobsActive)
            <div id="panel-praca" role="tabpanel" aria-labelledby="tab-praca" tabindex="0" x-cloak
                 x-show="tab === 'praca'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
{{-- ── Sekcja: Praca ── --}}
@if ($jobsActive)
    <section aria-labelledby="section-praca">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 id="section-praca" class="text-2xl font-bold text-ink">Oferty pracy</h2>
                <p class="mt-1 text-sm text-muted">Dołącz do naszego zespołu.</p>
            </div>
            <a href="{{ route('praca.index') }}"
               class="shrink-0 text-sm font-bold text-brand hover:text-brand-dark focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Wszystkie oferty <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>

        @if ($offers->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-sm text-muted">
                Obecnie nie prowadzimy rekrutacji — zajrzyj wkrótce.
            </div>
        @else
            <ul class="flex flex-col divide-y divide-gray-100" role="list">
                @foreach ($offers as $offer)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($offer->audience)); @endphp
                    <li class="py-5 first:pt-0 last:pb-0" style="--accent: {{ $accent }}">
                        <article>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-5">
                                <div class="hidden w-1 flex-none self-stretch rounded-full sm:block" style="background-color: var(--accent); min-height: 3.5rem" aria-hidden="true"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="mb-1.5 flex flex-wrap gap-2 text-xs text-muted">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1">
                                            <i class="fa-solid fa-briefcase text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                            {{ $offer->jobTypeLabel() }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1">
                                            <i class="fa-solid fa-location-dot text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                            {{ $offer->modeLabel() }}@if ($offer->location) · {{ $offer->location }}@endif
                                        </span>
                                        @if ($offer->closes_at)
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-solid fa-calendar-day text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                                Aplikuj do {{ $offer->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-base font-bold text-ink">
                                        <a href="{{ route('praca.show', $offer) }}"
                                           class="hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                                           style="text-decoration-color: var(--accent)">
                                            {{ $offer->title }}
                                        </a>
                                    </h3>
                                    @if ($offer->lead)
                                        <p class="mt-1 text-sm text-muted">{{ $offer->lead }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('praca.show', $offer) }}"
                                   class="shrink-0 self-start rounded-lg border px-3 py-1.5 text-sm font-bold transition sm:self-center focus-visible:outline-2 focus-visible:outline-offset-2"
                                   style="border-color: var(--accent); color: var(--accent)"
                                   aria-label="Zobacz ofertę: {{ $offer->title }}">
                                    Szczegóły <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif
            </div>
        @endif

        @if ($volunteeringActive)
            <div id="panel-wolontariat" role="tabpanel" aria-labelledby="tab-wolontariat" tabindex="0" x-cloak
                 x-show="tab === 'wolontariat'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
{{-- ── Sekcja: Wolontariat ── --}}
@if ($volunteeringActive)
    <section aria-labelledby="section-wolontariat">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 id="section-wolontariat" class="text-2xl font-bold text-ink">Wolontariat</h2>
                <p class="mt-1 text-sm text-muted">Dołącz do konkretnego działania.</p>
            </div>
            <a href="{{ route('volunteer.index') }}"
               class="shrink-0 text-sm font-bold text-brand hover:text-brand-dark focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Wszystkie ogłoszenia <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>

        @if ($ads->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-sm text-muted">
                Obecnie nie prowadzimy naboru wolontariuszy — zajrzyj wkrótce.
            </div>
        @else
            <ul class="flex flex-col divide-y divide-gray-100" role="list">
                @foreach ($ads as $ad)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp
                    <li class="py-5 first:pt-0 last:pb-0" style="--accent: {{ $accent }}">
                        <article>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-5">
                                <div class="hidden w-1 flex-none self-stretch rounded-full sm:block" style="background-color: var(--accent); min-height: 3.5rem" aria-hidden="true"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="mb-1.5 flex flex-wrap gap-2 text-xs text-muted">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fa-solid fa-location-dot text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                            {{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif
                                        </span>
                                        @if ($ad->closes_at)
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-solid fa-calendar-day text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                                Zgłoszenia do {{ $ad->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-base font-bold text-ink">
                                        <a href="{{ route('volunteer.show', $ad) }}"
                                           class="hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                                           style="text-decoration-color: var(--accent)">
                                            {{ $ad->title }}
                                        </a>
                                    </h3>
                                    @if ($ad->lead)
                                        <p class="mt-1 text-sm text-muted">{{ $ad->lead }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('volunteer.show', $ad) }}"
                                   class="shrink-0 self-start rounded-lg border px-3 py-1.5 text-sm font-bold transition sm:self-center focus-visible:outline-2 focus-visible:outline-offset-2"
                                   style="border-color: var(--accent); color: var(--accent)"
                                   aria-label="Zobacz ogłoszenie: {{ $ad->title }}">
                                    Szczegóły <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif
            </div>
        @endif

        @if (! $jobsActive && ! $volunteeringActive)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-10 text-center">
                <p class="mb-4 text-muted">Aktualnie nie prowadzimy naboru.</p>
                <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">
                    Skontaktuj się z nami
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
