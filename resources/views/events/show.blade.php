@extends('layouts.site')

@section('title', $event->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $event->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i wydarzenia', 'url' => route('events.index')],
        ['label' => $event->title, 'url' => null],
    ]])
@endsection

@php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($event->audience)); @endphp

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12" style="--accent: {{ $accent }}">
        <header class="mb-8">
            <p class="inline-flex items-center gap-2 text-sm font-bold uppercase tracking-wide" style="color: var(--accent)">
                <i class="fa-solid {{ $event->typeIcon() }}" aria-hidden="true"></i> {{ $event->typeLabel() }}
            </p>
            <h1 class="mt-1 text-3xl font-bold text-ink">{{ $event->title }}</h1>
            <p class="mt-3 text-lg text-gray-700">{{ $event->lead }}</p>

            @if ($event->isPast())
                <p class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700" role="status">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> To wydarzenie już się odbyło.
                </p>
            @endif

            <dl class="mt-5 flex flex-wrap gap-2 text-sm">
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Termin</dt>
                    <dd><time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->dateRangeLabel() }}</time></dd>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                    <dt class="sr-only">Tryb i miejsce</dt>
                    <dd>{{ $event->modeLabel() }}@if ($event->location) · {{ $event->location }}@endif</dd>
                </div>
                @if ($event->price_info)
                    <div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                        <i class="fa-solid fa-tag" aria-hidden="true" style="color: var(--accent)"></i>
                        <dt class="sr-only">Koszt</dt>
                        <dd>{{ $event->price_info }}</dd>
                    </div>
                @endif
            </dl>

            @if (! $event->isPast() && $event->registrationHref())
                <a href="{{ $event->registrationHref() }}" @if($event->registration_url) target="_blank" rel="noopener" @endif
                   class="mt-6 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                   style="background: var(--accent); outline-color: var(--accent)">
                    {{ $event->registration_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </header>

        @if ($event->description)
            <section aria-labelledby="opis" class="mb-8">
                <h2 id="opis" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-circle-info" aria-hidden="true" style="color: var(--accent)"></i> O wydarzeniu
                </h2>
                <p class="mt-2 whitespace-pre-line text-gray-700">{{ $event->description }}</p>
            </section>
        @endif

        @if ($event->hasFacilitator())
            <section aria-labelledby="prowadzaca" class="mb-8">
                <h2 id="prowadzaca" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-chalkboard-user" aria-hidden="true" style="color: var(--accent)"></i> Prowadzący / Prowadząca
                </h2>
                <div class="mt-3 flex flex-col gap-5 rounded-xl border border-gray-200 bg-gray-50 p-6 sm:flex-row sm:items-start">
                    @if ($event->facilitatorPhotoUrl())
                        <img src="{{ $event->facilitatorPhotoUrl() }}"
                            alt="{{ $event->facilitator_name ? 'Zdjęcie: '.$event->facilitator_name : 'Zdjęcie osoby prowadzącej zajęcia' }}"
                            class="h-28 w-28 flex-none rounded-full object-cover ring-2" style="--tw-ring-color: color-mix(in srgb, var(--accent) 40%, white)">
                    @else
                        <span class="flex h-28 w-28 flex-none items-center justify-center rounded-full text-white" style="background: var(--accent)" aria-hidden="true">
                            <i class="fa-solid fa-user text-4xl"></i>
                        </span>
                    @endif
                    <div class="min-w-0">
                        @if ($event->facilitator_name)
                            <p class="text-lg font-bold text-ink">{{ $event->facilitator_name }}</p>
                        @endif
                        @if ($event->facilitator_role)
                            <p class="text-sm font-bold uppercase tracking-wide" style="color: var(--accent)">{{ $event->facilitator_role }}</p>
                        @endif
                        @if ($event->facilitator_bio)
                            <p class="mt-2 whitespace-pre-line text-gray-700">{{ $event->facilitator_bio }}</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($event->online_url && ! $event->isPast())
            <section aria-labelledby="online" class="mb-8 rounded-xl border-2 p-6" style="border-color: color-mix(in srgb, var(--accent) 30%, white)">
                <h2 id="online" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-video" aria-hidden="true" style="color: var(--accent)"></i> Udział online
                </h2>
                <p class="mt-2 text-gray-700">Wydarzenie odbędzie się także zdalnie. Link do spotkania:</p>
                <a href="{{ $event->online_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 font-bold break-all" style="color: var(--accent)">
                    <i class="fa-solid fa-link" aria-hidden="true"></i> {{ $event->online_url }}
                </a>
            </section>
        @endif

        @if ($event->faqs->isNotEmpty())
            <section aria-labelledby="faq" class="mb-8">
                <h2 id="faq" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-circle-question" aria-hidden="true" style="color: var(--accent)"></i> Najczęstsze pytania
                </h2>
                <div class="mt-3 space-y-2">
                    @foreach ($event->faqs as $faq)
                        <details class="group rounded-xl border border-gray-200 bg-white [&[open]]:border-gray-300">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-bold text-ink [&::-webkit-details-marker]:hidden">
                                <span>{{ $faq->question }}</span>
                                <i class="fa-solid fa-chevron-down flex-none text-sm text-muted transition-transform group-open:rotate-180" aria-hidden="true"></i>
                            </summary>
                            <div class="px-5 pb-4 -mt-1 whitespace-pre-line text-gray-700">{{ $faq->answer }}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="zapisy" class="rounded-xl border-2 p-6" style="border-color: color-mix(in srgb, var(--accent) 30%, white)">
            <h2 id="zapisy" class="flex items-center gap-2 text-xl font-bold text-ink">
                <i class="fa-solid fa-paper-plane" aria-hidden="true" style="color: var(--accent)"></i> Zapisy i kontakt
            </h2>
            @if ($event->isPast())
                <p class="mt-2 text-gray-700">To wydarzenie już się odbyło — zapisy są zamknięte. Napisz do nas, jeśli interesują Cię kolejne terminy.</p>
            @elseif ($event->registrationHref())
                <p class="mt-2 text-gray-700">Aby wziąć udział, zapisz się poniżej. W razie pytań chętnie pomożemy.</p>
            @else
                <p class="mt-2 text-gray-700">Szczegóły zapisów podamy wkrótce. W razie pytań napisz do nas.</p>
            @endif

            @if ($event->contact_email)
                <p class="mt-3 text-sm text-gray-700">
                    <i class="fa-solid fa-envelope mr-1" aria-hidden="true" style="color: var(--accent)"></i>
                    Kontakt: <a href="mailto:{{ $event->contact_email }}" class="font-bold" style="color: var(--accent)">{{ $event->contact_email }}</a>
                </p>
            @endif

            @if (! $event->isPast() && $event->registrationHref())
                <a href="{{ $event->registrationHref() }}" @if($event->registration_url) target="_blank" rel="noopener" @endif
                   class="mt-4 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                   style="background: var(--accent); outline-color: var(--accent)">
                    {{ $event->registration_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </section>
    </article>
@endsection
