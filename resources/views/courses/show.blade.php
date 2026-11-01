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
    .cs-status { margin: 1.5rem 0 0; padding: 1rem 1.25rem; border: 2px solid #1d1d1a; border-radius: .375rem; font-weight: 700; }
</style>

<div class="cs">
    <header class="cs-head">
        <div class="cs-wrap">
            <p class="cs-kicker">{{ $course->categoryLabel() }} · {{ $course->formatLabel() }}</p>
            <h1 class="cs-title">{{ $course->title }}</h1>
            @if ($course->lead)<p class="cs-lead">{{ $course->lead }}</p>@endif

            <div class="cs-actions">
                @if ($openEditions->isNotEmpty())
                    <a class="cs-btn" href="#zapis">Zapisz się</a>
                @endif
                <a class="cs-btn cs-btn-o" href="#terminy">Zobacz terminy</a>
            </div>

            @if ($facts)
                <dl class="cs-facts" aria-label="Najważniejsze informacje">
                    @foreach ($facts as [$icon, $label, $value])
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </div>
    </header>

    <div class="cs-wrap">
        @if (session('status'))
            <p role="status" class="cs-status">{{ session('status') }}</p>
        @endif

        <div class="cs-layout">
            <div class="cs-main">
                <nav class="cs-toc" aria-label="Na tej stronie">
                    <p>Na tej stronie</p>
                    <ul>@foreach ($sections as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach</ul>
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
                                            @if ($e->enrollment_status !== 'open')
                                                {{ $e->enrollmentLabel() }}
                                            @else
                                                {{ $e->isFull() ? 'Brak miejsc, lista rezerwowa' : 'Wolne miejsca: '.$e->free_seats.' z '.$e->capacity }}
                                            @endif
                                        </td>
                                        <td>
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
            </div>

            <aside id="zapis" class="cs-aside" aria-labelledby="c-signup">
                <h2 id="c-signup">{{ $registrationOn ? 'Zapisz się' : 'Chcesz wziąć udział?' }}</h2>
                @if (! $registrationOn)
                    <p>Zapisy online są na razie wyłączone. Napisz do nas, a odpowiemy na pytania i zapiszemy Cię na wybrany termin.</p>
                    <a class="cs-btn" href="{{ route('contact.show') }}">Napisz do nas</a>
                @elseif ($selected)
                    <p>Wybrany termin: <strong>{{ $selected->datesLabel() }}</strong>. Możesz go zmienić w polu poniżej.</p>
                    @include('courses.partials.form', ['course' => $course, 'editions' => $editions, 'selected' => $selected])
                @else
                    <p>Rekrutacja jest teraz zamknięta. Napisz do nas, a damy znać o następnym terminie.</p>
                    <a class="cs-btn" href="{{ route('contact.show') }}">Napisz do nas</a>
                @endif
            </aside>
        </div>
    </div>
</div>
@endsection
