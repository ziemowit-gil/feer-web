@extends('layouts.site')

@section('title', 'Dołącz do nas — ' . $siteSettings->site_name)
@section('meta_description', 'Oferty pracy i wolontariatu w ' . $siteSettings->site_name . '. Znajdź sposób na działanie razem z nami.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Dołącz do nas', 'url' => null],
    ]])
@endsection

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-12">

        {{-- Nagłówek --}}
        <div class="mb-12">
            <h1 class="mb-2 text-3xl font-bold text-ink">Dołącz do nas</h1>
            <p class="max-w-2xl text-muted">
                Działamy na rzecz dostępności cyfrowej i edukacji. Jeśli chcesz być częścią naszych działań —
                sprawdź aktualne oferty pracy lub możliwości wolontariatu.
            </p>
        </div>

        @if (! $jobsActive && ! $volunteeringActive)
            {{-- Oba moduły wyłączone --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-10 text-center">
                <p class="mb-4 text-muted">Aktualnie nie prowadzimy naboru.</p>
                <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">
                    Skontaktuj się z nami
                </a>
            </div>
        @else

            {{-- ── Sekcja: Praca ── --}}
            @if ($jobsActive)
                <section class="mb-16" aria-labelledby="section-praca">
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

        @endif
    </div>
@endsection
