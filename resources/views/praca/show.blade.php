@extends('layouts.site')

@section('title', $offer->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $offer->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Praca', 'url' => route('praca.index')],
        ['label' => $offer->title, 'url' => null],
    ]])
@endsection

@php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($offer->audience)); @endphp

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12" style="--accent: {{ $accent }}">
        <header class="mb-8">
            <p class="text-sm font-bold uppercase tracking-wide" style="color: var(--accent)">Oferta pracy</p>
            <h1 class="mt-1 text-3xl font-bold text-ink">{{ $offer->title }}</h1>
            <p class="mt-3 text-lg text-gray-700">{{ $offer->lead }}</p>

            <dl class="mt-5 flex flex-wrap gap-2 text-sm">
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-briefcase" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Rodzaj umowy</dt>
                    <dd>{{ $offer->jobTypeLabel() }}</dd>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Tryb i miejsce</dt>
                    <dd>{{ $offer->modeLabel() }}@if ($offer->location) · {{ $offer->location }}@endif</dd>
                </div>
                @if ($offer->salary_range)
                    <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                        <i class="fa-solid fa-coins" aria-hidden="true" style="color: var(--accent)"></i>
                        <dt class="sr-only">Wynagrodzenie</dt>
                        <dd>{{ $offer->salary_range }}</dd>
                    </div>
                @endif
                @if ($offer->closes_at)
                    <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                        <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                        <dt class="sr-only">Termin aplikacji</dt>
                        <dd>Aplikuj do {{ $offer->closes_at->locale('pl')->isoFormat('D MMMM YYYY') }}</dd>
                    </div>
                @endif
            </dl>

            @if ($offer->applyHref())
                <a href="{{ $offer->applyHref() }}" @if($offer->application_url) target="_blank" rel="noopener" @endif
                   class="mt-6 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                   style="background: var(--accent); outline-color: var(--accent)">
                    {{ $offer->application_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </header>

        <div class="space-y-8">
            <section aria-labelledby="duties-heading">
                <h2 id="duties-heading" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-list-check" aria-hidden="true" style="color: var(--accent)"></i> Zakres obowiązków
                </h2>
                <ul class="mt-3 space-y-1.5">
                    @foreach ($offer->duties as $duty)
                        <li class="flex gap-2 text-gray-700">
                            <i class="fa-solid fa-check mt-1 shrink-0" aria-hidden="true" style="color: var(--accent)"></i>
                            <span>{{ $duty }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="requirements-heading">
                <h2 id="requirements-heading" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-user-check" aria-hidden="true" style="color: var(--accent)"></i> Wymagania
                </h2>
                <ul class="mt-3 space-y-1.5">
                    @foreach ($offer->requirements as $req)
                        <li class="flex gap-2 text-gray-700">
                            <i class="fa-solid fa-circle-dot mt-1.5 shrink-0 text-[8px]" aria-hidden="true" style="color: var(--accent)"></i>
                            <span>{{ $req }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @if (!empty($offer->benefits))
                <section aria-labelledby="benefits-heading">
                    <h2 id="benefits-heading" class="flex items-center gap-2 text-xl font-bold text-ink">
                        <i class="fa-solid fa-seedling" aria-hidden="true" style="color: var(--accent)"></i> Co oferujemy
                    </h2>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($offer->benefits as $benefit)
                            <li class="flex gap-2 rounded-lg bg-gray-50 p-3 text-gray-700">
                                <i class="fa-solid fa-star mt-0.5 shrink-0" aria-hidden="true" style="color: var(--accent)"></i>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="apply-heading" class="rounded-xl border-2 p-6" style="border-color: color-mix(in srgb, var(--accent) 30%, white)">
                <h2 id="apply-heading" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true" style="color: var(--accent)"></i> Jak aplikować?
                </h2>
                @if ($offer->apply_note)
                    <p class="mt-3 text-sm text-gray-700">{{ $offer->apply_note }}</p>
                @endif
                @if ($offer->grant_condition)
                    <p class="mt-3 flex items-start gap-2 text-sm text-gray-600">
                        <i class="fa-solid fa-circle-info mt-0.5 shrink-0" aria-hidden="true" style="color: var(--accent)"></i>
                        Oferta ma charakter informacyjny. Związanie ofertą oraz zawarcie umowy warunkowane jest pozyskaniem środków zewnętrznych (dotacji) na realizację zadania.
                    </p>
                @endif
                @if ($offer->contact_name || $offer->contact_email)
                    <p class="mt-3 text-sm text-gray-700">
                        <i class="fa-solid fa-user mr-1" aria-hidden="true" style="color: var(--accent)"></i>
                        Osoba kontaktowa:
                        @if ($offer->contact_name)<strong>{{ $offer->contact_name }}</strong>@endif
                        @if ($offer->contact_email)
                            — <a href="mailto:{{ $offer->contact_email }}" class="font-bold" style="color: var(--accent)">{{ $offer->contact_email }}</a>
                        @endif
                    </p>
                @endif
                @if ($offer->applyHref())
                    <a href="{{ $offer->applyHref() }}" @if($offer->application_url) target="_blank" rel="noopener" @endif
                       class="mt-4 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                       style="background: var(--accent); outline-color: var(--accent)">
                        {{ $offer->application_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </section>
        </div>
    </article>
@endsection
