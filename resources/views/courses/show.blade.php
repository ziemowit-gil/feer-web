{{--
    Strona szkolenia (moduł „Szkolenia i zajęcia”). Układ: biały nagłówek z kategorią, tytułem, leadem, metryczką (cena, czas,
    poziom, certyfikat) i dwoma przyciskami; pasek „Na tej stronie”; po lewej treść w sekcjach (opis, dla kogo, efekty,
    program jako lista kroków, terminy jako karty z paskiem zajętości, trener z dużym zdjęciem, cennik, warunki, FAQ);
    po prawej przyklejona karta zapisu. Płasko, bez cieni, kolory marki; klasy tylko z już zbudowanego CSS plus kilka stylów
    inline w bloku <style> (wdrożenie nie buduje Tailwinda). Kontrast tekstu ≥ 4,5:1.
--}}
@extends('layouts.site')

@section('title', $course->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $course->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i zajęcia', 'url' => route('courses.index')],
        ['label' => $course->title, 'url' => null],
    ]])
@endsection

@php
    $isPortal = $siteSettings->themeVariant() === 'feer_is';
    $trainer = $course->facilitator;
    $registrationOn = $siteSettings->coursesRegistrationEnabled();
    $openEditions = $registrationOn ? $editions->filter(fn ($e) => $e->acceptsRegistrations()) : collect();
    $nextEdition = $editions->first();
    $facts = array_filter([
        ['fa-coins', 'Cena', $course->priceLabel()],
        ['fa-clock', 'Czas trwania', $course->duration],
        ['fa-chalkboard', 'Forma', $course->formatLabel()],
        ['fa-signal', 'Poziom', $course->level],
        ['fa-award', 'Certyfikat', $course->certificate],
        ['fa-calendar-days', 'Najbliższy termin', $nextEdition?->datesLabel()],
    ], fn ($f) => filled($f[2]));
    $sections = array_filter([
        'opis' => filled($course->description) ? 'O szkoleniu' : null,
        'dla-kogo' => $course->listOf('audience') ? 'Dla kogo' : null,
        'efekty' => $course->listOf('outcomes') ? 'Czego się nauczysz' : null,
        'program' => $course->programModules() ? 'Program' : null,
        'terminy' => 'Terminy',
        'trener' => $trainer ? 'Prowadzi' : null,
        'cennik' => ($course->price !== null || filled($course->price_note) || filled($course->funding)) ? 'Cennik' : null,
        'warunki' => filled($course->terms) ? 'Warunki' : null,
        'faq' => $course->faqItems() ? 'Pytania' : null,
    ]);
    $h2 = 'text-2xl font-bold text-ink md:text-3xl';
    $card = 'rounded-md bg-gray-50 p-6';
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org', '@type' => 'Course',
            'name' => $course->title, 'description' => $course->lead,
            'provider' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'hasCourseInstance' => $editions->map(fn ($e) => array_filter([
                '@type' => 'CourseInstance', 'courseMode' => $course->formatLabel(), 'startDate' => $e->starts_on->toDateString(),
                'endDate' => $e->ends_on?->toDateString(), 'location' => filled($e->location) ? ['@type' => 'Place', 'name' => $e->location] : null,
                'instructor' => $trainer ? ['@type' => 'Person', 'name' => $trainer->name] : null,
            ]))->values()->all() ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<style>
    .cs-grid { display: grid; gap: 3rem; }
    @media (min-width: 1024px) { .cs-grid { grid-template-columns: minmax(0, 1fr) 24rem; align-items: start; } .cs-sticky { position: sticky; top: 1.5rem; } }
    .cs-facts { display: grid; gap: 1px; background: #e5e7eb; border-radius: .375rem; overflow: hidden; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 768px) { .cs-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .cs-fact { background: #f9fafb; padding: 1rem 1.25rem; }
    .cs-nav { display: flex; gap: .25rem; overflow-x: auto; scrollbar-width: none; border-bottom: 2px solid #e5e7eb; }
    .cs-nav a { display: inline-flex; align-items: center; min-height: 3rem; padding: 0 1rem; white-space: nowrap; font-weight: 700; font-size: .875rem; color: #1d1d1a; border-bottom: 3px solid transparent; margin-bottom: -2px; }
    .cs-nav a:hover { border-bottom-color: #1d1d1a; }
    .cs-step { display: grid; grid-template-columns: 3rem minmax(0, 1fr); gap: 1.25rem; }
    .cs-step + .cs-step { margin-top: 1rem; }
    .cs-num { display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: .375rem; background: #1d1d1a; color: #fff; font-weight: 800; font-size: 1.125rem; }
    .cs-bar { height: .5rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
    .cs-bar > span { display: block; height: 100%; background: #1752bf; }
    .cs-edition[data-selected="1"] { outline: 3px solid #1752bf; outline-offset: -3px; }
    .cs-trainer { display: grid; gap: 1.5rem; }
    @media (min-width: 640px) { .cs-trainer { grid-template-columns: 12rem minmax(0, 1fr); } }
</style>

    {{-- Nagłówek: biały, lewostronny, z paskiem w kolorze marki nad kategorią. --}}
    <header class="border-b border-gray-200">
        <div class="mx-auto max-w-6xl px-4 pb-10 pt-10 md:pt-14">
            <span class="block h-1.5 w-16 bg-brand" aria-hidden="true"></span>
            <p class="mt-5 text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $course->categoryLabel() }} · {{ $course->formatLabel() }}</p>
            <h1 class="mt-3 max-w-4xl text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{{ $course->title }}</h1>
            @if ($course->lead)<p class="mt-5 max-w-3xl text-lg leading-relaxed text-ink md:text-xl">{{ $course->lead }}</p>@endif

            <div class="mt-8 flex flex-wrap gap-3">
                @if ($openEditions->isNotEmpty())
                    <a href="#zapis" class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Zapisz się</a>
                @endif
                <a href="#terminy" class="inline-flex min-h-12 items-center gap-2 rounded-md border-2 border-ink px-6 text-base font-bold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Zobacz terminy</a>
            </div>

            @if ($facts)
                <dl class="cs-facts mt-10" aria-label="Najważniejsze informacje">
                    @foreach ($facts as [$icon, $label, $value])
                        <div class="cs-fact">
                            <dt class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted"><i class="fa-solid {{ $icon }} text-brand-dark" aria-hidden="true"></i>{{ $label }}</dt>
                            <dd class="mt-1 text-lg font-bold text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </header>

    <div class="mx-auto max-w-6xl px-4">
        <nav class="cs-nav mt-2" aria-label="Na tej stronie">
            @foreach ($sections as $id => $label)<a href="#{{ $id }}">{{ $label }}</a>@endforeach
        </nav>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if (session('status'))
            <p role="status" class="mb-8 rounded-md bg-brand-light p-4 font-bold text-ink">{{ session('status') }}</p>
        @endif

        <div class="cs-grid">
            <div class="min-w-0 space-y-14">
                @if (filled($course->description))
                    <section id="opis" class="scroll-mt-24" aria-labelledby="c-desc">
                        <h2 id="c-desc" class="{{ $h2 }}">O szkoleniu</h2>
                        <div class="prose mt-4 max-w-none text-lg leading-relaxed text-ink">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->description), 1) !!}</div>
                    </section>
                @endif

                @if ($course->listOf('audience') || $course->listOf('outcomes'))
                    <div class="grid gap-6 md:grid-cols-2">
                        @if ($course->listOf('audience'))
                            <section id="dla-kogo" class="{{ $card }} scroll-mt-24" aria-labelledby="c-audience">
                                <h2 id="c-audience" class="flex items-center gap-3 text-xl font-bold text-ink"><i class="fa-solid fa-users text-brand-dark" aria-hidden="true"></i>Dla kogo</h2>
                                <ul class="mt-4 space-y-2 text-ink">@foreach ($course->listOf('audience') as $row)<li class="flex gap-3"><i class="fa-solid fa-circle mt-2 text-[.4rem] text-brand-dark" aria-hidden="true"></i><span>{{ $row }}</span></li>@endforeach</ul>
                            </section>
                        @endif
                        @if ($course->listOf('outcomes'))
                            <section id="efekty" class="{{ $card }} scroll-mt-24" aria-labelledby="c-outcomes">
                                <h2 id="c-outcomes" class="flex items-center gap-3 text-xl font-bold text-ink"><i class="fa-solid fa-bullseye text-brand-dark" aria-hidden="true"></i>Czego się nauczysz</h2>
                                <ul class="mt-4 space-y-2 text-ink">@foreach ($course->listOf('outcomes') as $row)<li class="flex gap-3"><i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i><span>{{ $row }}</span></li>@endforeach</ul>
                            </section>
                        @endif
                    </div>
                @endif

                @if ($course->programModules())
                    <section id="program" class="scroll-mt-24" aria-labelledby="c-program">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <h2 id="c-program" class="{{ $h2 }}">Program</h2>
                            @php $hours = collect($course->programModules())->sum(fn ($m) => (float) str_replace(',', '.', $m['hours'])); @endphp
                            @if ($hours > 0)<p class="text-sm font-bold text-ink">Razem {{ rtrim(rtrim(number_format($hours, 1, ',', ' '), '0'), ',') }} godz.</p>@endif
                        </div>
                        <ol class="mt-6">
                            @foreach ($course->programModules() as $i => $row)
                                <li class="cs-step">
                                    <span class="cs-num" aria-hidden="true">{{ $i + 1 }}</span>
                                    <div class="min-w-0 border-b border-gray-200 pb-4">
                                        <h3 class="flex flex-wrap items-baseline justify-between gap-2 text-lg font-bold text-ink">
                                            <span><span class="sr-only">Moduł {{ $i + 1 }}: </span>{{ $row['title'] }}</span>
                                            @if ($row['hours'] !== '')<span class="text-sm font-semibold text-ink">{{ $row['hours'] }} godz.</span>@endif
                                        </h3>
                                        @if ($row['text'] !== '')<p class="mt-1 leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <section id="terminy" class="scroll-mt-24" aria-labelledby="c-editions">
                    <h2 id="c-editions" class="{{ $h2 }}">Terminy</h2>
                    @if ($editions->isEmpty())
                        <p class="mt-4 {{ $card }} text-ink">Nie ma jeszcze zaplanowanych terminów. Zajrzyj tu wkrótce albo napisz do nas przez <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4">formularz kontaktowy</a>.</p>
                    @else
                        <ul class="mt-6 grid gap-4 md:grid-cols-2" role="list">
                            @foreach ($editions as $e)
                                @php $taken = max(0, $e->capacity - $e->free_seats); $pct = $e->capacity > 0 ? min(100, (int) round($taken / $e->capacity * 100)) : 0; @endphp
                                <li class="cs-edition {{ $card }} flex flex-col gap-3" data-selected="{{ $selected && $selected->id === $e->id ? 1 : 0 }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="text-lg font-bold text-ink"><i class="fa-solid fa-calendar-days mr-2 text-brand-dark" aria-hidden="true"></i>{{ $e->datesLabel() }}</h3>
                                        @if ($selected && $selected->id === $e->id)<span class="shrink-0 rounded-md bg-brand-dark px-2 py-1 text-xs font-bold text-white">Wybrany</span>@endif
                                    </div>
                                    <dl class="space-y-1 text-sm text-ink">
                                        @if ($e->schedule)<div class="flex gap-2"><dt class="w-28 shrink-0 font-bold">Harmonogram</dt><dd>{{ $e->schedule }}</dd></div>@endif
                                        @if ($e->location)<div class="flex gap-2"><dt class="w-28 shrink-0 font-bold">Miejsce</dt><dd>{{ $e->location }}</dd></div>@endif
                                        @if ($e->instructor)<div class="flex gap-2"><dt class="w-28 shrink-0 font-bold">Prowadzi</dt><dd>{{ $e->instructor }}</dd></div>@endif
                                    </dl>
                                    <div class="mt-auto">
                                        @if ($e->enrollment_status !== 'open')
                                            <p class="text-sm font-bold text-ink">{{ $e->enrollmentLabel() }}</p>
                                        @else
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-bold text-ink">{{ $e->isFull() ? 'Brak miejsc, lista rezerwowa' : 'Wolne miejsca: '.$e->free_seats.' z '.$e->capacity }}</span>
                                                <span class="text-muted">{{ $pct }}% zajęte</span>
                                            </div>
                                            <div class="cs-bar mt-2" role="img" aria-label="Zajęte {{ $pct }} procent miejsc"><span style="width: {{ $pct }}%"></span></div>
                                        @endif
                                        @if ($registrationOn && $e->acceptsRegistrations())
                                            <a href="{{ route('courses.show', [$course, 'termin' => $e->id]) }}#zapis" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-md {{ $e->isFull() ? 'border-2 border-ink text-ink hover:bg-gray-100' : 'bg-brand-dark text-white hover:bg-ink' }} px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                                {{ $e->isFull() ? 'Zapisz się na listę rezerwową' : 'Wybierz ten termin' }}<span class="sr-only"> ({{ $e->datesLabel() }})</span>
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if (filled($course->prerequisites))
                    <section class="{{ $card }}" aria-labelledby="c-prereq">
                        <h2 id="c-prereq" class="flex items-center gap-3 text-xl font-bold text-ink"><i class="fa-solid fa-list-check text-brand-dark" aria-hidden="true"></i>Wymagania wstępne</h2>
                        <p class="mt-3 leading-relaxed text-ink">{!! nl2br(e($course->prerequisites)) !!}</p>
                    </section>
                @endif

                @if ($trainer)
                    <section id="trener" class="scroll-mt-24" aria-labelledby="c-trainer">
                        <h2 id="c-trainer" class="{{ $h2 }}">Prowadzi</h2>
                        <div class="cs-trainer mt-6">
                            @if ($trainer->photoUrl())
                                <img src="{{ $trainer->photoUrl() }}" alt="{{ $trainer->name }}" class="aspect-square w-full rounded-md object-cover">
                            @else
                                <span class="flex aspect-square w-full items-center justify-center rounded-md bg-brand-light text-6xl text-brand-dark" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                            @endif
                            <div class="min-w-0">
                                <h3 class="text-2xl font-bold text-ink">{{ $trainer->name }}</h3>
                                @if ($trainer->role)<p class="mt-1 text-lg text-brand-dark">{{ $trainer->role }}</p>@endif
                                @if ($trainer->bio)<div class="prose mt-4 max-w-none text-ink">{!! \App\Support\SafeHtml::clean($trainer->bio) !!}</div>@endif
                                @php $links = array_filter(['Strona WWW' => $trainer->website, 'LinkedIn' => $trainer->linkedin, 'Facebook' => $trainer->facebook, 'Instagram' => $trainer->instagram, 'X' => $trainer->twitter]); @endphp
                                @if ($links)
                                    <ul class="mt-4 flex flex-wrap gap-2" role="list">
                                        @foreach ($links as $label => $url)
                                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $label }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @if ($course->price !== null || filled($course->price_note) || filled($course->funding))
                    <section id="cennik" class="scroll-mt-24" aria-labelledby="c-pricing">
                        <h2 id="c-pricing" class="{{ $h2 }}">Cennik</h2>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-md bg-ink p-6 text-white">
                                <p class="text-xs font-bold uppercase tracking-wide">Cena za osobę</p>
                                <p class="mt-2 text-4xl font-extrabold">{{ $course->priceLabel() }}</p>
                                @if (filled($course->price_note))<p class="mt-2 text-sm">{{ $course->price_note }}</p>@endif
                            </div>
                            @if (filled($course->funding))
                                <div class="{{ $card }}">
                                    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted"><i class="fa-solid fa-hand-holding-dollar text-brand-dark" aria-hidden="true"></i>Dofinansowanie</p>
                                    <p class="mt-2 text-lg font-bold text-ink">{{ $course->funding }}</p>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                @if (filled($course->terms))
                    <section id="warunki" class="scroll-mt-24" aria-labelledby="c-terms">
                        <h2 id="c-terms" class="{{ $h2 }}">Warunki udziału</h2>
                        <div class="prose mt-4 max-w-none text-ink">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->terms), 1) !!}</div>
                    </section>
                @endif

                @if ($course->faqItems())
                    <section id="faq" class="scroll-mt-24" aria-labelledby="c-faq">
                        <h2 id="c-faq" class="{{ $h2 }}">Najczęstsze pytania</h2>
                        <div class="mt-6 space-y-2">
                            @foreach ($course->faqItems() as $row)
                                <details class="group rounded-md bg-gray-50">
                                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 text-lg font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark [&::-webkit-details-marker]:hidden">
                                        <span>{{ $row['question'] }}</span>
                                        <i class="fa-solid fa-plus text-brand-dark transition-transform group-open:rotate-45" aria-hidden="true"></i>
                                    </summary>
                                    <div class="px-5 pb-5 leading-relaxed text-ink">{!! nl2br(e($row['answer'])) !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside id="zapis" class="cs-sticky scroll-mt-6 rounded-md bg-brand-light p-6" aria-labelledby="c-signup">
                <h2 id="c-signup" class="text-2xl font-bold text-ink">{{ $registrationOn ? 'Zapisz się' : 'Chcesz wziąć udział?' }}</h2>
                @if (! $registrationOn)
                    <p class="mt-3 leading-relaxed text-ink">Zapisy online są na razie wyłączone. Napisz do nas, a odpowiemy na pytania i zapiszemy Cię na wybrany termin.</p>
                    <a href="{{ route('contact.show') }}" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-md bg-brand-dark px-6 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Napisz do nas</a>
                @elseif ($selected)
                    <p class="mt-1 text-sm text-ink">Wybrany termin: <strong>{{ $selected->datesLabel() }}</strong>. Możesz go zmienić w polu poniżej.</p>
                    @include('courses.partials.form', ['course' => $course, 'editions' => $editions, 'selected' => $selected])
                @else
                    <p class="mt-3 leading-relaxed text-ink">Rekrutacja jest teraz zamknięta. Napisz do nas, a damy znać o następnym terminie.</p>
                    <a href="{{ route('contact.show') }}" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-md bg-brand-dark px-6 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Napisz do nas</a>
                @endif
            </aside>
        </div>
    </div>
@endsection
