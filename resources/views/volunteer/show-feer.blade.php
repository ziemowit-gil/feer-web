@extends('layouts.site')

@section('title', $ad->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $ad->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wolontariat', 'url' => route('volunteer.index')],
        ['label' => $ad->title, 'url' => null],
    ]])
@endsection

{{--
    Ogłoszenie wolontariackie w stylu FEER: jasny nagłówek z faktami (miejsce, czas, termin) jako płaskie kafelki,
    treść w kolumnie, po prawej przyklejona karta „Jak się zgłosić". Kolor grupy docelowej tylko jako pasek (kontrast ≥ 4,5:1).
--}}
@php
    $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience));
    $apply = 'inline-flex min-h-12 items-center gap-2 rounded-md bg-brand px-6 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
@endphp

@section('content')
    <section class="bg-gray-50" style="border-left: 6px solid {{ $accent }}">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Ogłoszenie o wolontariacie</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{{ $ad->title }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">{{ $ad->lead }}</p>

            <dl class="mt-8 grid gap-3 sm:grid-cols-3">
                <div class="rounded-md bg-white p-4">
                    <dt class="text-xs font-bold uppercase tracking-widest text-muted">Tryb i miejsce</dt>
                    <dd class="mt-1 font-bold text-ink">{{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif</dd>
                </div>
                <div class="rounded-md bg-white p-4">
                    <dt class="text-xs font-bold uppercase tracking-widest text-muted">Zaangażowanie</dt>
                    <dd class="mt-1 font-bold text-ink">{{ $ad->q_time_commitment }}</dd>
                </div>
                @if ($ad->closes_at)
                    <div class="rounded-md bg-white p-4">
                        <dt class="text-xs font-bold uppercase tracking-widest text-muted">Termin zgłoszeń</dt>
                        <dd class="mt-1 font-bold text-ink">do {{ $ad->closes_at->locale('pl')->isoFormat('D MMMM YYYY') }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="min-w-0 space-y-10">
                <section aria-labelledby="q1">
                    <h2 id="q1" class="text-2xl font-bold text-ink">Cel wolontariatu</h2>
                    <p class="mt-3 whitespace-pre-line text-lg leading-relaxed text-ink">{{ $ad->q_beneficiaries }}</p>
                </section>

                <section aria-labelledby="q2">
                    <h2 id="q2" class="text-2xl font-bold text-ink">Na czym polega wolontariat?</h2>
                    <ul class="mt-3 space-y-2" role="list">
                        @foreach ($ad->q_tasks as $task)
                            <li class="flex gap-3 text-lg leading-relaxed text-ink"><span class="mt-2.5 h-2 w-2 flex-none rounded-sm bg-brand-dark" aria-hidden="true"></span><span>{{ $task }}</span></li>
                        @endforeach
                    </ul>
                </section>

                <section aria-labelledby="q3">
                    <h2 id="q3" class="text-2xl font-bold text-ink">Kiedy i gdzie?</h2>
                    <p class="mt-3 text-lg leading-relaxed text-ink"><strong>{{ $ad->modeLabel() }}</strong>@if ($ad->q_location), {{ $ad->q_location }}@endif. {{ $ad->q_schedule }}</p>
                </section>

                <section aria-labelledby="q5">
                    <h2 id="q5" class="text-2xl font-bold text-ink">Co zyskasz?</h2>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2" role="list">
                        @foreach ($ad->q_benefits as $benefit)
                            <li class="rounded-md bg-gray-50 p-4 font-medium leading-relaxed text-ink">{{ $benefit }}</li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside aria-labelledby="q6" class="rounded-lg bg-brand-light p-6 lg:sticky lg:top-6">
                <h2 id="q6" class="text-xl font-bold text-ink">Jak się zgłosić?</h2>
                <p class="mt-3 whitespace-pre-line leading-relaxed text-ink">{{ $ad->q_how_to_apply }}</p>
                @if ($ad->contact_name || $ad->contact_email)
                    <p class="mt-4 text-sm text-ink">
                        Osoba kontaktowa:
                        @if ($ad->contact_name)<strong>{{ $ad->contact_name }}</strong>@endif
                        @if ($ad->contact_email)<a href="mailto:{{ $ad->contact_email }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $ad->contact_email }}</a>@endif
                    </p>
                @endif
                @if ($ad->applyHref())
                    <a href="{{ $ad->applyHref() }}" @if ($ad->application_url) target="_blank" rel="noopener" @endif class="{{ $apply }} mt-5">
                        {{ $ad->application_cta_label }}@if ($ad->application_url)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                    </a>
                @endif
            </aside>
        </div>
    </div>
@endsection
