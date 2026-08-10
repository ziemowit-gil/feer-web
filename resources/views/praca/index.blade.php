@extends('layouts.site')

@section('title', 'Praca — ' . $siteSettings->site_name)
@section('meta_description', 'Aktualne oferty pracy w ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Praca', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Oferty pracy</h1>
        <p class="mb-8 max-w-2xl text-muted">Dołącz do naszego zespołu. Poniżej znajdziesz aktualne ogłoszenia o pracę — każde opisuje stanowisko, obowiązki i to, co oferujemy.</p>

        @if ($offers->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-muted">
                Obecnie nie prowadzimy rekrutacji. Zajrzyj wkrótce — albo napisz do nas przez <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">formularz kontaktowy</a>.
            </div>
        @else
            <ul class="flex flex-col divide-y divide-gray-100" role="list">
                @foreach ($offers as $offer)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($offer->audience)); @endphp
                    <li class="group py-6 first:pt-0 last:pb-0" style="--accent: {{ $accent }}">
                        <article>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-6">
                                <div class="hidden h-full w-1 flex-none rounded-full sm:block" style="background-color: var(--accent); min-height: 4rem" aria-hidden="true"></div>

                                <div class="flex-1 min-w-0">
                                    <div class="mb-2 flex flex-wrap gap-2 text-xs text-muted">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1">
                                            <i class="fa-solid fa-briefcase text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                            {{ $offer->jobTypeLabel() }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1">
                                            <i class="fa-solid fa-location-dot text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                            {{ $offer->modeLabel() }}@if ($offer->location) · {{ $offer->location }}@endif
                                        </span>
                                        @if ($offer->salary_range)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1">
                                                <i class="fa-solid fa-coins text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                                {{ $offer->salary_range }}
                                            </span>
                                        @endif
                                        @if ($offer->closes_at)
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-solid fa-calendar-day text-[10px]" aria-hidden="true" style="color: var(--accent)"></i>
                                                Aplikuj do {{ $offer->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                            </span>
                                        @endif
                                    </div>

                                    <h2 class="text-lg font-bold text-ink">
                                        <a href="{{ route('praca.show', $offer) }}"
                                            class="hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                                            style="text-decoration-color: var(--accent)">
                                            {{ $offer->title }}
                                        </a>
                                    </h2>

                                    @if ($offer->lead)
                                        <p class="mt-1 text-sm text-muted">{{ $offer->lead }}</p>
                                    @endif
                                </div>

                                <a href="{{ route('praca.show', $offer) }}"
                                    class="shrink-0 self-start rounded-lg border px-4 py-2 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 sm:self-center"
                                    style="border-color: var(--accent); color: var(--accent)"
                                    aria-label="Zobacz szczegóły: {{ $offer->title }}">
                                    Szczegóły <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
