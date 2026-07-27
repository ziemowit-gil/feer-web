@extends('layouts.site')

@section('title', $ad->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $ad->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wolontariat', 'url' => route('volunteer.index')],
        ['label' => $ad->title, 'url' => null],
    ]])
@endsection

@php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12" style="--accent: {{ $accent }}">
        <header class="mb-8">
            <p class="text-sm font-bold uppercase tracking-wide" style="color: var(--accent)">Ogłoszenie o wolontariacie</p>
            <h1 class="mt-1 text-3xl font-bold text-ink">{{ $ad->title }}</h1>
            <p class="mt-3 text-lg text-gray-700">{{ $ad->lead }}</p>

            <dl class="mt-5 flex flex-wrap gap-2 text-sm">
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Tryb i miejsce</dt>
                    <dd>{{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif</dd>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-clock" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Zaangażowanie czasowe</dt>
                    <dd>{{ $ad->q_time_commitment }}</dd>
                </div>
                @if ($ad->closes_at)
                    <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                        <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                        <dt class="sr-only">Termin zgłoszeń</dt>
                        <dd>Zgłoszenia do {{ $ad->closes_at->locale('pl')->isoFormat('D MMMM YYYY') }}</dd>
                    </div>
                @endif
            </dl>

            @if ($ad->applyHref())
                <a href="{{ $ad->applyHref() }}" @if($ad->application_url) target="_blank" rel="noopener" @endif
                   class="mt-6 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                   style="background: var(--accent); outline-color: var(--accent)">
                    {{ $ad->application_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </header>

        <div class="space-y-8">
            <section aria-labelledby="q1">
                <h2 id="q1" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-bullseye" aria-hidden="true" style="color: var(--accent)"></i> Cel wolontariatu
                </h2>
                <p class="mt-2 whitespace-pre-line text-gray-700">{{ $ad->q_beneficiaries }}</p>
            </section>

            <section aria-labelledby="q2">
                <h2 id="q2" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-list-check" aria-hidden="true" style="color: var(--accent)"></i> Na czym polega wolontariat?
                </h2>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($ad->q_tasks as $task)
                        <li class="flex gap-2 text-gray-700">
                            <i class="fa-solid fa-check mt-1 shrink-0" aria-hidden="true" style="color: var(--accent)"></i>
                            <span>{{ $task }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="q3">
                <h2 id="q3" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true" style="color: var(--accent)"></i> Kiedy i gdzie?
                </h2>
                <p class="mt-2 text-gray-700">
                    <strong>{{ $ad->modeLabel() }}</strong>@if ($ad->q_location), {{ $ad->q_location }}@endif. {{ $ad->q_schedule }}
                </p>
            </section>

            <section aria-labelledby="q5">
                <h2 id="q5" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-seedling" aria-hidden="true" style="color: var(--accent)"></i> Co zyskasz?
                </h2>
                <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                    @foreach ($ad->q_benefits as $benefit)
                        <li class="flex gap-2 rounded-lg bg-gray-50 p-3 text-gray-700">
                            <i class="fa-solid fa-star mt-0.5 shrink-0" aria-hidden="true" style="color: var(--accent)"></i>
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="q6" class="rounded-xl border-2 p-6" style="border-color: color-mix(in srgb, var(--accent) 30%, white)">
                <h2 id="q6" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true" style="color: var(--accent)"></i> Jak się zgłosić?
                </h2>
                <p class="mt-2 whitespace-pre-line text-gray-700">{{ $ad->q_how_to_apply }}</p>
                @if ($ad->contact_name || $ad->contact_email)
                    <p class="mt-3 text-sm text-gray-700">
                        <i class="fa-solid fa-user mr-1" aria-hidden="true" style="color: var(--accent)"></i>
                        Osoba kontaktowa:
                        @if ($ad->contact_name)<strong>{{ $ad->contact_name }}</strong>@endif
                        @if ($ad->contact_email)
                            <a href="mailto:{{ $ad->contact_email }}" class="font-bold" style="color: var(--accent)">{{ $ad->contact_email }}</a>
                        @endif
                    </p>
                @endif
                @if ($ad->applyHref())
                    <a href="{{ $ad->applyHref() }}" @if($ad->application_url) target="_blank" rel="noopener" @endif
                       class="mt-4 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                       style="background: var(--accent); outline-color: var(--accent)">
                        {{ $ad->application_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </section>
        </div>
    </article>
@endsection
