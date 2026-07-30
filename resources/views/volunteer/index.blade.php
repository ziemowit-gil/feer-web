@extends('layouts.site')

@section('title', 'Wolontariat — ' . $siteSettings->site_name)
@section('meta_description', 'Aktualne ogłoszenia o wolontariacie w ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wolontariat', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Wolontariat</h1>
        <p class="mb-8 max-w-2xl text-muted">Dołącz do nas w konkretnym działaniu. Poniżej znajdziesz aktualne ogłoszenia — każde odpowiada na to, co warto wiedzieć, zanim się zgłosisz.</p>

        @if ($ads->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-muted">
                Obecnie nie prowadzimy naboru. Zajrzyj wkrótce — albo napisz do nas przez <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">formularz kontaktowy</a>.
            </div>
        @elseif (($siteSettings->volunteer_layout ?? 'grid') === 'list')
            {{-- ── Widok lista ── --}}
            <ul class="flex flex-col divide-y divide-gray-100" role="list">
                @foreach ($ads as $ad)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp
                    <li class="group py-6 first:pt-0 last:pb-0" style="--accent: {{ $accent }}">
                        <article>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-6">
                                <div class="hidden h-full w-1 flex-none rounded-full sm:block" style="background-color: var(--accent); min-height: 4rem" aria-hidden="true"></div>

                                <div class="flex-1 min-w-0">
                                    <div class="mb-2 flex flex-wrap gap-2 text-xs text-muted">
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

                                    <h2 class="text-lg font-bold text-ink">
                                        <a href="{{ route('volunteer.show', $ad) }}"
                                            class="hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                                            style="text-decoration-color: var(--accent)">
                                            {{ $ad->title }}
                                        </a>
                                    </h2>

                                    @if ($ad->lead)
                                        <p class="mt-1 text-sm text-muted">{{ $ad->lead }}</p>
                                    @endif
                                </div>

                                <a href="{{ route('volunteer.show', $ad) }}"
                                    class="shrink-0 self-start rounded-lg border px-4 py-2 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 sm:self-center"
                                    style="border-color: var(--accent); color: var(--accent)"
                                    aria-label="Zobacz szczegóły: {{ $ad->title }}">
                                    Szczegóły <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @else
            {{-- ── Widok siatka (domyślna) ── --}}
            <ul class="grid gap-5 sm:grid-cols-2">
                @foreach ($ads as $ad)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp
                    <li class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md" style="--accent: {{ $accent }}">
                        <div class="mb-3 flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
                                <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                                {{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif
                            </span>
                            @if ($ad->closes_at)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
                                    <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                                    do {{ $ad->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl font-bold text-ink">
                            <a href="{{ route('volunteer.show', $ad) }}" class="hover:underline" style="text-decoration-color: var(--accent)">{{ $ad->title }}</a>
                        </h2>
                        <p class="mt-2 flex-1 text-muted">{{ $ad->lead }}</p>
                        <a href="{{ route('volunteer.show', $ad) }}" class="mt-4 inline-flex items-center gap-2 self-start font-bold" style="color: var(--accent)">
                            Zobacz szczegóły <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
