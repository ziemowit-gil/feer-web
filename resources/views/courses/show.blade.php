{{--
    Strona szkolenia (moduł „Szkolenia i zajęcia”). Układ nastawiony na czytelność: jedna wąska kolumna tekstu (do 46 rem),
    duża czcionka, interlinia 1,7, sekcje rozdzielone cienką linią, zwykłe listy zamiast
    kart, terminy jako tabela (termin, miejsce, miejsca, akcja), cena jako zwykłe zdanie; po prawej przyklejona karta zapisu.
    Bez ikon dekoracyjnych i bez kolorowych tła. Style inline z prefiksem cs- (wdrożenie nie buduje Tailwinda).
    Kontrast: tekst #1d1d1a na bieli, linki i przyciski #1752bf (≥ 6,9:1), opisy #4b5563 (≥ 7:1).
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
    ], fn ($f) => filled($f[2]));
    $seatsText = function ($e) {
        if ($e->enrollment_status !== 'open') {
            return $e->enrollmentLabel();
        }
        if ($e->isFull()) {
            return 'Brak miejsc, lista rezerwowa';
        }
        $t = 'Wolne miejsca: '.$e->free_seats.' z '.$e->capacity;

        return ($e->free_seats <= 3 && $e->capacity >= 5) ? $t.' (ostatnie miejsca)' : $t;
    };
    $contactPhone = $siteSettings->contact_phone;
    $contactEmail = $siteSettings->contact_email;
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
        'zobacz-takze' => ($related ?? collect())->isNotEmpty() ? 'Zobacz także' : null,
    ]);
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
    .cs { color: #1d1d1a; font-size: 1.125rem; line-height: 1.7; }
    .cs a { color: #1752bf; text-underline-offset: 3px; }
    .cs a:hover { color: #12409a; }
    .cs :focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
    .cs-wrap { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
    .cs-head { padding: 2.5rem 0 2.25rem; border-bottom: 1px solid #d1d5db; }
    .cs-kicker { margin: 0; font-size: .95rem; font-weight: 700; color: #1752bf; }
    .cs-title { margin: .5rem 0 1rem; max-width: 46rem; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; line-height: 1.15; letter-spacing: -.01em; }
    .cs-lead { margin: 0; max-width: 46rem; font-size: 1.3rem; line-height: 1.6; }
    .cs-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
    .cs .cs-btn { display: inline-flex; align-items: center; min-height: 3rem; padding: 0 1.5rem; border-radius: .375rem; background: #1752bf; color: #fff; font-weight: 700; font-size: 1rem; text-decoration: none; border: 2px solid #1752bf; }
    .cs .cs-btn:hover { background: #12409a; border-color: #12409a; color: #fff; }
    .cs .cs-btn-o { background: #fff; color: #1d1d1a; border-color: #1d1d1a; }
    .cs .cs-btn-o:hover { background: #f3f4f6; color: #1d1d1a; border-color: #1d1d1a; }
    .cs-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 1.25rem 2rem; margin: 2.25rem 0 0; max-width: 56rem; }
    .cs-facts dt { font-size: .9rem; font-weight: 700; color: #4b5563; line-height: 1.4; }
    .cs-facts dd { margin: .15rem 0 0; font-size: 1.2rem; font-weight: 700; line-height: 1.4; }
    .cs-layout { display: grid; gap: 3rem; padding: 2rem 0 4rem; }
    @media (min-width: 1024px) { .cs-layout { grid-template-columns: minmax(0, 46rem) 22rem; justify-content: space-between; align-items: start; } .cs-aside { position: sticky; top: 1.5rem; } }
    .cs-toc { margin: 0 0 1rem; padding: 0 0 1.5rem; border-bottom: 1px solid #d1d5db; }
    .cs-toc p { margin: 0 0 .5rem; font-size: .9rem; font-weight: 700; color: #4b5563; }
    .cs-toc ul { display: flex; flex-wrap: wrap; gap: .25rem 1.5rem; margin: 0; padding: 0; list-style: none; }
    .cs-toc a { display: inline-block; padding: .25rem 0; font-weight: 700; }
    .cs-sec { padding: 2.25rem 0; border-top: 1px solid #d1d5db; scroll-margin-top: 1.5rem; }
    .cs-sec:first-of-type { border-top: 0; padding-top: 1rem; }
    .cs-sec h2 { margin: 0 0 1rem; font-size: 1.75rem; font-weight: 800; line-height: 1.25; }
    .cs-sec h3 { margin: 0; font-size: 1.2rem; font-weight: 700; line-height: 1.4; }
    .cs-sec p { margin: 0 0 1rem; }
    .cs-list { margin: 0; padding-left: 1.5rem; list-style: disc; }
    .cs-list li { margin: 0 0 .5rem; padding-left: .25rem; }
    .cs-list li::marker { color: #1752bf; }
    .cs-prog { margin: 0; padding-left: 1.75rem; }
    .cs-prog li { margin: 0 0 1.25rem; padding-left: .25rem; }
    .cs-prog li::marker { font-weight: 700; }
    .cs-hours { font-weight: 400; color: #4b5563; font-size: 1rem; white-space: nowrap; }
    .cs-prose { max-width: none; font-size: 1.125rem; line-height: 1.7; color: #1d1d1a; }
    .cs-table { width: 100%; border-collapse: collapse; font-size: 1rem; line-height: 1.5; }
    .cs-table caption { text-align: left; }
    .cs-table th { padding: .5rem .75rem .6rem 0; text-align: left; font-size: .9rem; font-weight: 700; color: #4b5563; border-bottom: 2px solid #1d1d1a; }
    .cs-table td { padding: 1rem .75rem 1rem 0; vertical-align: top; border-bottom: 1px solid #d1d5db; }
    .cs-table td strong { display: block; font-size: 1.1rem; }
    .cs-table small { display: block; color: #4b5563; font-size: .9rem; }
    .cs-table tr[data-selected="1"] td { background: #f3f4f6; }
    @media (max-width: 720px) {
        .cs-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        .cs-table, .cs-table tbody, .cs-table tr, .cs-table td { display: block; }
        .cs-table td { padding: .25rem 0; border: 0; }
        .cs-table td[data-label]::before { content: attr(data-label) ": "; font-weight: 700; }
        .cs-table tr { padding: 1rem 0; border-bottom: 1px solid #d1d5db; }
    }
    .cs-trainer { display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap; }
    .cs-trainer img, .cs-trainer .cs-nophoto { width: 8rem; height: 8rem; border-radius: 50%; object-fit: cover; flex: none; }
    .cs-trainer .cs-nophoto { background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #4b5563; }
    .cs-trainer > div { flex: 1 1 18rem; min-width: 0; }
    .cs-price { margin: 0; font-size: 2.25rem; font-weight: 800; line-height: 1.2; }
    .cs-faq details { border-bottom: 1px solid #d1d5db; }
    .cs-faq summary { display: flex; justify-content: space-between; gap: 1rem; padding: 1rem 0; font-size: 1.15rem; font-weight: 700; cursor: pointer; list-style: none; }
    .cs-faq summary::-webkit-details-marker { display: none; }
    .cs-faq summary::after { content: "+"; font-size: 1.5rem; line-height: 1; flex: none; }
    .cs-faq details[open] summary::after { content: "–"; }
    .cs-faq details > div { padding: 0 0 1.25rem; }
    .cs-aside { padding: 1.5rem; background: #f3f4f6; border-radius: .375rem; scroll-margin-top: 1.5rem; }
    .cs-aside h2 { margin: 0 0 .75rem; font-size: 1.5rem; font-weight: 800; line-height: 1.25; }
    .cs-aside p { margin: 0 0 1rem; font-size: 1rem; }
    .cs-aside .cs-btn { width: 100%; justify-content: center; }
    .cs-back { margin: 0 0 1rem; font-size: 1rem; }
    .cs-back a { font-weight: 700; }
    .cs-next { margin-top: 1.75rem; padding: 1rem 1.25rem; max-width: 36rem; border-left: 6px solid #1752bf; background: #f3f4f6; }
    .cs-next p { margin: 0; line-height: 1.5; }
    .cs-next-label { font-size: .9rem; font-weight: 700; color: #4b5563; }
    .cs-next-date { font-size: 1.4rem; font-weight: 800; }
    .cs-next-meta { font-size: 1rem; }
    .cs-card-link { display: inline-block; margin-top: 1.25rem; font-weight: 700; font-size: 1rem; }
    .cs-toc { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1.5rem; }
    .cs-toc p { margin: 0; }
    .cs-toc ul { flex: 1 1 24rem; }
    .cs-toc a[aria-current="location"] { text-decoration-thickness: 3px; color: #1d1d1a; }
    .cs-size { display: flex; align-items: center; gap: .35rem; }
    .cs-size button { min-width: 2.75rem; min-height: 2.75rem; border: 2px solid #1d1d1a; border-radius: .375rem; background: #fff; color: #1d1d1a; font-weight: 700; cursor: pointer; }
    .cs-size button[aria-pressed="true"] { background: #1d1d1a; color: #fff; }
    .cs-main[data-size="2"] .cs-sec, .cs-main[data-size="2"] .cs-prose, .cs-main[data-size="2"] .cs-table { font-size: 1.35rem; }
    .cs-main[data-size="3"] .cs-sec, .cs-main[data-size="3"] .cs-prose, .cs-main[data-size="3"] .cs-table { font-size: 1.6rem; }
    @media (min-width: 1024px) { .cs-toc { position: sticky; top: 0; z-index: 6; background: #fff; padding-top: .75rem; } .cs-toc ul { flex-wrap: nowrap; overflow-x: auto; } }
    .cs-rel { margin: 0; padding: 0; list-style: none; }
    .cs-rel li { padding: .9rem 0; border-bottom: 1px solid #d1d5db; }
    .cs-rel strong { font-size: 1.15rem; }
    .cs-rel small { display: block; color: #4b5563; font-size: .95rem; }
    .cs-sticky { display: none; }
    @media (max-width: 1023px) {
        .cs { padding-bottom: 5.5rem; }
        .cs-sticky { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; align-items: center; justify-content: space-between; gap: 1rem; padding: .6rem 1rem; background: #fff; border-top: 2px solid #1d1d1a; }
        .cs-sticky.is-hidden { display: none; }
        .cs-sticky strong { display: block; font-size: 1rem; line-height: 1.3; }
        .cs-sticky span { display: block; font-size: .875rem; color: #4b5563; line-height: 1.3; }
        .cs-sticky .cs-btn { flex: none; }
    }
    .cs-input { display: block; width: 100%; min-height: 3rem; border: 2px solid #6b7280; border-radius: .5rem; padding: .5rem .75rem; font-size: 1rem; color: #1d1d1a; background: #fff; }
    .cs-input[aria-invalid="true"] { border-color: #b91c1c; }
    .cs-err { margin: .25rem 0 0; font-weight: 700; color: #b91c1c; font-size: .95rem; }
    .cs-contact { margin: 1.25rem 0 0; padding-top: 1rem; border-top: 1px solid #d1d5db; font-size: 1rem; }
    .cs-contact a { font-weight: 700; }
    .cs-change { font-weight: 700; }
    .cs-status { margin: 1.5rem 0 0; padding: 1rem 1.25rem; border: 2px solid #1d1d1a; border-radius: .375rem; font-weight: 700; }
</style>

<div class="cs">
    <header class="cs-head">
        <div class="cs-wrap">
            <p class="cs-back"><a href="{{ route('courses.index') }}">← Wszystkie szkolenia</a></p>
            <p class="cs-kicker">{{ $course->categoryLabel() }} · {{ $course->formatLabel() }}</p>
            <h1 class="cs-title">{{ $course->title }}</h1>
            @if ($course->lead)<p class="cs-lead">{{ $course->lead }}</p>@endif

            <div class="cs-actions">
                @if ($openEditions->isNotEmpty())
                    <a class="cs-btn" href="#zapis">Zapisz się</a>
                @endif
                <a class="cs-btn cs-btn-o" href="#terminy">Zobacz terminy</a>
            </div>

            @if ($nextEdition)
                <div class="cs-next" role="group" aria-label="Najbliższy termin">
                    <p class="cs-next-label">Najbliższy termin</p>
                    <p class="cs-next-date">{{ $nextEdition->datesLabel() }}</p>
                    <p class="cs-next-meta">@if ($nextEdition->location){{ $nextEdition->location }} · @endif{{ $seatsText($nextEdition) }}</p>
                </div>
            @endif

            @if ($facts)
                <dl class="cs-facts" aria-label="Najważniejsze informacje">
                    @foreach ($facts as [$icon, $label, $value])
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <a class="cs-card-link" href="{{ route('courses.card', $course) }}">Pobierz kartę szkolenia (PDF)</a>
        </div>
    </header>

    <div class="cs-wrap">
        @if (session('status'))
            <p role="status" class="cs-status">{{ session('status') }}</p>
        @endif

        <div class="cs-layout">
            <div class="cs-main" id="cs-main" data-size="1">
                <nav class="cs-toc" aria-label="Na tej stronie">
                    <p>Na tej stronie</p>
                    <ul>@foreach ($sections as $id => $label)<li><a href="#{{ $id }}" data-spy="{{ $id }}">{{ $label }}</a></li>@endforeach</ul>
                    <div class="cs-size" role="group" aria-label="Rozmiar tekstu">
                        <button type="button" data-size="1" aria-pressed="true" aria-label="Tekst normalny">A</button>
                        <button type="button" data-size="2" aria-pressed="false" aria-label="Większy tekst" style="font-size:1.2rem">A</button>
                        <button type="button" data-size="3" aria-pressed="false" aria-label="Największy tekst" style="font-size:1.45rem">A</button>
                    </div>
                </nav>

                @if (filled($course->description))
                    <section id="opis" class="cs-sec" aria-labelledby="c-desc">
                        <h2 id="c-desc">O szkoleniu</h2>
                        <div class="prose cs-prose">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->description), 1) !!}</div>
                    </section>
                @endif

                @if ($course->listOf('audience'))
                    <section id="dla-kogo" class="cs-sec" aria-labelledby="c-audience">
                        <h2 id="c-audience">Dla kogo</h2>
                        <ul class="cs-list">@foreach ($course->listOf('audience') as $row)<li>{{ $row }}</li>@endforeach</ul>
                    </section>
                @endif

                @if ($course->listOf('outcomes'))
                    <section id="efekty" class="cs-sec" aria-labelledby="c-outcomes">
                        <h2 id="c-outcomes">Czego się nauczysz</h2>
                        <ul class="cs-list">@foreach ($course->listOf('outcomes') as $row)<li>{{ $row }}</li>@endforeach</ul>
                    </section>
                @endif

                @if ($course->programModules())
                    <section id="program" class="cs-sec" aria-labelledby="c-program">
                        <h2 id="c-program">Program</h2>
                        @php $hours = collect($course->programModules())->sum(fn ($m) => (float) str_replace(',', '.', $m['hours'])); @endphp
                        @if ($hours > 0)<p>Razem {{ rtrim(rtrim(number_format($hours, 1, ',', ' '), '0'), ',') }} godz.</p>@endif
                        <ol class="cs-prog">
                            @foreach ($course->programModules() as $row)
                                <li>
                                    <h3>{{ $row['title'] }}@if ($row['hours'] !== '') <span class="cs-hours">({{ $row['hours'] }} godz.)</span>@endif</h3>
                                    @if ($row['text'] !== '')<p style="margin:.25rem 0 0">{{ $row['text'] }}</p>@endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <section id="terminy" class="cs-sec" aria-labelledby="c-editions">
                    <h2 id="c-editions">Terminy</h2>
                    @if ($editions->isEmpty())
                        <p>Nie ma jeszcze zaplanowanych terminów. Zajrzyj tu wkrótce albo napisz do nas przez <a href="{{ route('contact.show') }}">formularz kontaktowy</a>.</p>
                    @else
                        <table class="cs-table">
                            <caption class="sr-only">Terminy szkolenia {{ $course->title }}</caption>
                            <thead><tr><th scope="col">Termin</th><th scope="col">Miejsce</th><th scope="col">Miejsca</th><th scope="col"><span class="sr-only">Zapis</span></th></tr></thead>
                            <tbody>
                                @foreach ($editions as $e)
                                    <tr data-selected="{{ $selected && $selected->id === $e->id ? 1 : 0 }}">
                                        <td>
                                            <strong>{{ $e->datesLabel() }}@if ($selected && $selected->id === $e->id) (wybrany)@endif</strong>
                                            @if ($e->schedule)<small>{{ $e->schedule }}</small>@endif
                                            @if ($e->instructor)<small>Prowadzi: {{ $e->instructor }}</small>@endif
                                        </td>
                                        <td data-label="Miejsce">{{ $e->location ?: '—' }}</td>
                                        <td data-label="Miejsca">
                                            {{ $seatsText($e) }}
                                        </td>
                                        <td>
                                            <a href="{{ route('courses.ics', [$course, $e]) }}" style="display:block;margin-bottom:.35rem">Dodaj do kalendarza<span class="sr-only"> ({{ $e->datesLabel() }}, plik .ics)</span></a>
                                            @if ($registrationOn && $e->acceptsRegistrations())
                                                <a href="{{ route('courses.show', [$course, 'termin' => $e->id]) }}#zapis">{{ $e->isFull() ? 'Zapisz się na listę rezerwową' : 'Wybierz ten termin' }}<span class="sr-only"> ({{ $e->datesLabel() }})</span></a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                @if (filled($course->prerequisites))
                    <section class="cs-sec" aria-labelledby="c-prereq">
                        <h2 id="c-prereq">Wymagania wstępne</h2>
                        <p>{!! nl2br(e($course->prerequisites)) !!}</p>
                    </section>
                @endif

                @if ($trainer)
                    <section id="trener" class="cs-sec" aria-labelledby="c-trainer">
                        <h2 id="c-trainer">Prowadzi</h2>
                        <div class="cs-trainer">
                            @if ($trainer->photoUrl())
                                <img src="{{ $trainer->photoUrl() }}" alt="{{ $trainer->name }}">
                            @else
                                <span class="cs-nophoto" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                            @endif
                            <div>
                                <h3 style="font-size:1.4rem">{{ $trainer->name }}</h3>
                                @if ($trainer->role)<p style="margin:.15rem 0 .75rem;color:#4b5563">{{ $trainer->role }}</p>@endif
                                @if ($trainer->bio)<div class="prose cs-prose">{!! \App\Support\SafeHtml::clean($trainer->bio) !!}</div>@endif
                                @php $links = array_filter(['Strona WWW' => $trainer->website, 'LinkedIn' => $trainer->linkedin, 'Facebook' => $trainer->facebook, 'Instagram' => $trainer->instagram, 'X' => $trainer->twitter]); @endphp
                                @if ($links)
                                    <p style="margin:1rem 0 0">
                                        @foreach ($links as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener" style="margin-right:1.25rem;font-weight:700">{{ $label }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>@endforeach
                                    </p>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @if ($course->price !== null || filled($course->price_note) || filled($course->funding))
                    <section id="cennik" class="cs-sec" aria-labelledby="c-pricing">
                        <h2 id="c-pricing">Cennik</h2>
                        <p class="cs-price">{{ $course->priceLabel() }} <span style="font-size:1rem;font-weight:400">za osobę</span></p>
                        @if (filled($course->price_note))<p style="margin-top:.5rem">{{ $course->price_note }}</p>@endif
                        @if (filled($course->funding))<p><strong>Dofinansowanie:</strong> {{ $course->funding }}</p>@endif
                    </section>
                @endif

                @if (filled($course->terms))
                    <section id="warunki" class="cs-sec" aria-labelledby="c-terms">
                        <h2 id="c-terms">Warunki udziału</h2>
                        <div class="prose cs-prose">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->terms), 1) !!}</div>
                    </section>
                @endif

                @if ($course->faqItems())
                    <section id="faq" class="cs-sec cs-faq" aria-labelledby="c-faq">
                        <h2 id="c-faq">Najczęstsze pytania</h2>
                        @foreach ($course->faqItems() as $row)
                            <details>
                                <summary>{{ $row['question'] }}</summary>
                                <div>{!! nl2br(e($row['answer'])) !!}</div>
                            </details>
                        @endforeach
                    </section>
                @endif

                @if ($related->isNotEmpty())
                    <section id="zobacz-takze" class="cs-sec" aria-labelledby="c-rel">
                        <h2 id="c-rel">Zobacz także</h2>
                        <ul class="cs-rel">
                            @foreach ($related as $r)
                                <li><a href="{{ route('courses.show', $r) }}"><strong>{{ $r->title }}</strong></a><small>{{ $r->categoryLabel() }} · {{ $r->formatLabel() }}</small>@if ($r->lead)<span>{{ \Illuminate\Support\Str::limit($r->lead, 120) }}</span>@endif</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside id="zapis" class="cs-aside" aria-labelledby="c-signup">
                <h2 id="c-signup">{{ $registrationOn ? 'Zapisz się' : 'Chcesz wziąć udział?' }}</h2>
                @if (! $registrationOn)
                    <p>Zapisy online są na razie wyłączone. Napisz do nas, a odpowiemy na pytania i zapiszemy Cię na wybrany termin.</p>
                    <a class="cs-btn" href="{{ route('contact.show') }}">Napisz do nas</a>
                @elseif ($selected)
                    <p>Wybrany termin: <strong>{{ $selected->datesLabel() }}</strong> (<a class="cs-change" href="#terminy">zobacz wszystkie terminy</a>). Możesz go zmienić w polu poniżej.</p>
                    @include('courses.partials.form', ['course' => $course, 'editions' => $editions, 'selected' => $selected])
                @else
                    @if ($canNotify)
                        <p>Rekrutacja jest teraz zamknięta. Zostaw adres e-mail, a damy znać, gdy pojawi się nowy termin.</p>
                        @if ($errors->any())
                            <div role="alert" tabindex="-1" x-data x-init="$el.focus()" class="cs-status" style="margin:0 0 1rem;background:#fff">
                                <ul style="margin:0;padding-left:1.25rem">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('courses.notify', $course) }}" novalidate>
                            @csrf
                            <label for="notify_email" style="display:block;margin-bottom:.25rem;font-weight:700">Adres e-mail <span aria-hidden="true">*</span></label>
                            <input id="notify_email" name="notify_email" type="email" autocomplete="email" required aria-required="true" value="{{ old('notify_email') }}" class="cs-input" @error('notify_email') aria-invalid="true" @enderror>
                            <div style="display:flex;gap:.75rem;align-items:flex-start;margin:1rem 0">
                                <input id="notify_consent" name="notify_consent" type="checkbox" value="1" required aria-required="true" @checked(old('notify_consent')) style="margin-top:.35rem;width:1.25rem;height:1.25rem">
                                <label for="notify_consent" style="font-size:1rem">Zgadzam się na wysłanie jednej wiadomości o nowym terminie tego szkolenia. <span aria-hidden="true">*</span></label>
                            </div>
                            @include('partials.spam-protection', ['spamScope' => 'szkolenie-powiadomienie', 'flat' => false])
                            <button type="submit" class="cs-btn" style="width:100%;justify-content:center;margin-top:.5rem">Powiadom mnie o terminie</button>
                        </form>
                    @else
                        <p>Rekrutacja jest teraz zamknięta. Napisz do nas, a damy znać o następnym terminie.</p>
                        <a class="cs-btn" href="{{ route('contact.show') }}">Napisz do nas</a>
                    @endif
                @endif

                @if (filled($contactPhone) || filled($contactEmail))
                    <p class="cs-contact">Wolisz zapisać się bez formularza?
                        @if (filled($contactPhone))Zadzwoń: <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}">{{ $contactPhone }}</a>@endif
                        @if (filled($contactPhone) && filled($contactEmail)) albo napisz: @elseif (filled($contactEmail)) Napisz: @endif
                        @if (filled($contactEmail))<a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>@endif
                    </p>
                @endif
            </aside>
        </div>
    </div>

    @if ($nextEdition)
        <div class="cs-sticky" id="cs-sticky" role="region" aria-label="Szybki zapis">
            <div><strong>{{ $nextEdition->datesLabel() }}</strong><span>{{ $seatsText($nextEdition) }}</span></div>
            @if ($registrationOn && $nextEdition->acceptsRegistrations())
                <a class="cs-btn" href="#zapis">Zapisz się</a>
            @else
                <a class="cs-btn" href="#terminy">Terminy</a>
            @endif
        </div>
    @endif
</div>

<script>
(function () {
    var main = document.getElementById('cs-main');
    // Rozmiar tekstu (A A A) z zapamiętaniem wyboru; bez localStorage strona działa tak samo.
    var buttons = document.querySelectorAll('.cs-size button');
    function setSize(v) {
        if (main) main.dataset.size = v;
        buttons.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.size === v ? 'true' : 'false'); });
    }
    try { var saved = localStorage.getItem('course-text-size'); if (saved) setSize(saved); } catch (e) {}
    buttons.forEach(function (b) {
        b.addEventListener('click', function () { setSize(b.dataset.size); try { localStorage.setItem('course-text-size', b.dataset.size); } catch (e) {} });
    });

    if (!('IntersectionObserver' in window)) return;
    // Spis sekcji: podświetla bieżącą sekcję (aria-current="location").
    var links = {};
    document.querySelectorAll('.cs-toc a[data-spy]').forEach(function (a) { links[a.dataset.spy] = a; });
    var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            Object.keys(links).forEach(function (k) { links[k].removeAttribute('aria-current'); });
            if (links[en.target.id]) links[en.target.id].setAttribute('aria-current', 'location');
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    Object.keys(links).forEach(function (id) { var el = document.getElementById(id); if (el) spy.observe(el); });

    // Pasek zapisu na telefonie znika, gdy widać już kartę zapisu.
    var bar = document.getElementById('cs-sticky'), aside = document.getElementById('zapis');
    if (bar && aside) {
        new IntersectionObserver(function (e) { bar.classList.toggle('is-hidden', e[0].isIntersecting); }).observe(aside);
    }
})();
</script>
@endsection
