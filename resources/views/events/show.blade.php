@extends('layouts.site')

@section('title', $event->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $event->lead)

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $event->lead,
            'startDate' => optional($event->starts_at)->toIso8601String(),
            'endDate' => optional($event->ends_at)->toIso8601String(),
            'eventAttendanceMode' => $event->mode === 'zdalnie'
                ? 'https://schema.org/OnlineEventAttendanceMode'
                : 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $event->mode === 'zdalnie'
                ? array_filter(['@type' => 'VirtualLocation', 'url' => $event->online_url])
                : array_filter(['@type' => 'Place', 'name' => $event->location]),
            'organizer' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i wydarzenia', 'url' => route('events.index')],
        ['label' => $event->title, 'url' => null],
    ]])
@endsection

@php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($event->audience)); @endphp

@section('content')
<div x-data="{ sliderOpen: false }" style="--accent: {{ $accent }}">

    {{-- ── ARTYKUŁ (pełna szerokość) ── --}}
    <article class="mx-auto max-w-3xl px-4 py-12">
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

            {{-- Przycisk otwierający slider ze szczegółami miejsca --}}
            <button type="button"
                @click="sliderOpen = true"
                :aria-expanded="sliderOpen"
                aria-controls="event-slider"
                class="mt-5 inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-ink shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                style="outline-color: var(--accent)">
                <i class="fa-solid fa-location-dot flex-none" aria-hidden="true" style="color: var(--accent)"></i>
                <span>
                    <span class="font-bold">{{ $event->dateRangeLabel() }}</span>
                    @if ($event->location)
                        <span class="text-muted"> · {{ $event->location }}</span>
                    @else
                        <span class="text-muted"> · {{ $event->modeLabel() }}</span>
                    @endif
                </span>
                <i class="fa-solid fa-arrow-right flex-none text-xs text-muted" aria-hidden="true"></i>
            </button>

            @if (! $event->isPast() && $event->registrationHref())
                <a href="{{ $event->registrationHref() }}"
                   @if($event->registration_url) target="_blank" rel="noopener" @endif
                   class="mt-4 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
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
                <a href="{{ $event->online_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 break-all font-bold" style="color: var(--accent)">
                    <i class="fa-solid fa-link" aria-hidden="true"></i> {{ $event->online_url }}
                </a>
            </section>
        @endif

        @php
            $eventFaqs = $event->faqs->concat($event->globalFaqs->where('is_published', true));
        @endphp
        @if ($eventFaqs->isNotEmpty())
            <section aria-labelledby="faq" class="mb-8">
                <h2 id="faq" class="flex items-center gap-2 text-xl font-bold text-ink">
                    <i class="fa-solid fa-circle-question" aria-hidden="true" style="color: var(--accent)"></i> Najczęstsze pytania
                </h2>
                <div class="mt-3 space-y-2">
                    @foreach ($eventFaqs as $faq)
                        <details class="group rounded-xl border border-gray-200 bg-white [&[open]]:border-gray-300">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-bold text-ink [&::-webkit-details-marker]:hidden">
                                <span>{{ $faq->question }}</span>
                                <i class="fa-solid fa-chevron-down flex-none text-sm text-muted transition-transform group-open:rotate-180" aria-hidden="true"></i>
                            </summary>
                            <div class="mt-[-4px] px-5 pb-4 whitespace-pre-line text-gray-700">{{ $faq->answer }}</div>
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
                <a href="{{ $event->registrationHref() }}"
                   @if($event->registration_url) target="_blank" rel="noopener" @endif
                   class="mt-4 inline-flex items-center gap-2 rounded-lg px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                   style="background: var(--accent); outline-color: var(--accent)">
                    {{ $event->registration_cta_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </section>
    </article>

    {{-- ── SLIDER (wsuwa się z prawej) ── --}}
    <div
        x-show="sliderOpen"
        x-cloak
        id="event-slider"
        role="dialog"
        aria-modal="true"
        aria-label="Szczegóły miejsca i zapisów"
        class="fixed inset-0 z-50 flex justify-end"
        @keydown.escape.window="sliderOpen = false">

        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/40"
            aria-hidden="true"
            @click="sliderOpen = false"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
        </div>

        {{-- Panel wsuwający się z prawej --}}
        <div
            class="relative flex h-full w-full max-w-sm flex-col overflow-y-auto bg-white shadow-2xl"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            x-init="$watch('sliderOpen', val => { if (val) $nextTick(() => $el.querySelector('button')?.focus()) })">

            {{-- Nagłówek slidera --}}
            <div class="flex flex-none items-center justify-between border-b border-gray-100 px-6 py-4">
                <span class="font-bold text-ink">Szczegóły organizacji</span>
                <button
                    type="button"
                    @click="sliderOpen = false"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline focus-visible:outline-2"
                    style="outline-color: var(--accent)"
                    aria-label="Zamknij">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            {{-- Pasek akcentu --}}
            <div class="h-1 w-full flex-none" style="background: var(--accent)" aria-hidden="true"></div>

            {{-- Treść --}}
            <div class="flex-1 overflow-y-auto p-6">
                @include('events._sidebar-info')
            </div>
        </div>
    </div>

</div>
@endsection
