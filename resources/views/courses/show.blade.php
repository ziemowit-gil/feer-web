@extends('layouts.site')

@section('title', $course->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $course->lead)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i zajęcia', 'url' => route('courses.index')],
        ['label' => $course->title, 'url' => null],
    ]])
@endsection

@php $isPortal = $siteSettings->themeVariant() === 'feer_is'; @endphp

@section('content')
    <section class="{{ $isPortal ? 'bg-brand-dark' : 'bg-gray-50' }}">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <p class="text-xs font-bold uppercase tracking-widest {{ $isPortal ? 'text-white' : 'text-muted' }}">{{ $course->categoryLabel() }} · {{ $course->formatLabel() }}</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight md:text-5xl {{ $isPortal ? 'text-white' : 'text-ink' }}">{{ $course->title }}</h1>
            @if ($course->lead)<p class="mt-4 max-w-2xl text-lg leading-relaxed {{ $isPortal ? 'text-white' : 'text-ink' }}">{{ $course->lead }}</p>@endif
            <ul class="mt-6 flex flex-wrap gap-2 text-sm font-bold text-ink" role="list" aria-label="Najważniejsze informacje">
                <li class="rounded-md bg-white px-4 py-2">Cena: {{ $course->priceLabel() }}</li>
                @if (filled($course->duration))<li class="rounded-md bg-white px-4 py-2"><i class="fa-solid fa-clock mr-1 text-brand-dark" aria-hidden="true"></i>{{ $course->duration }}</li>@endif
                @if (filled($course->level))<li class="rounded-md bg-white px-4 py-2"><i class="fa-solid fa-signal mr-1 text-brand-dark" aria-hidden="true"></i>Poziom: {{ $course->level }}</li>@endif
                @if (filled($course->certificate))<li class="rounded-md bg-white px-4 py-2"><i class="fa-solid fa-award mr-1 text-brand-dark" aria-hidden="true"></i>{{ $course->certificate }}</li>@endif
            </ul>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if (session('status'))
            <p role="status" class="mb-8 rounded-md bg-brand-light p-4 font-bold text-ink">{{ session('status') }}</p>
        @endif

        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
            <div class="min-w-0 space-y-12">
                @if (filled($course->description))
                    <section aria-labelledby="c-desc">
                        <h2 id="c-desc" class="text-2xl font-bold text-ink">O szkoleniu</h2>
                        <div class="prose mt-3 max-w-none text-lg leading-relaxed text-ink">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->description), 1) !!}</div>
                    </section>
                @endif

                @if ($course->listOf('audience'))
                    <section aria-labelledby="c-audience">
                        <h2 id="c-audience" class="text-2xl font-bold text-ink">Dla kogo</h2>
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-lg text-ink">@foreach ($course->listOf('audience') as $row)<li>{{ $row }}</li>@endforeach</ul>
                    </section>
                @endif

                @if ($course->listOf('outcomes'))
                    <section aria-labelledby="c-outcomes">
                        <h2 id="c-outcomes" class="text-2xl font-bold text-ink">Czego się nauczysz</h2>
                        <ul class="mt-3 space-y-2 text-lg">
                            @foreach ($course->listOf('outcomes') as $row)
                                <li class="flex gap-3 text-ink"><i class="fa-solid fa-check mt-1.5 text-brand-dark" aria-hidden="true"></i><span>{{ $row }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($course->programModules())
                    <section aria-labelledby="c-program">
                        <h2 id="c-program" class="text-2xl font-bold text-ink">Program</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach ($course->programModules() as $i => $row)
                                <li class="rounded-md bg-gray-50 p-5">
                                    <h3 class="flex flex-wrap items-baseline justify-between gap-2 text-lg font-bold text-ink">
                                        <span>{{ $i + 1 }}. {{ $row['title'] }}</span>
                                        @if ($row['hours'] !== '')<span class="text-sm font-semibold text-ink">{{ $row['hours'] }} godz.</span>@endif
                                    </h3>
                                    @if ($row['text'] !== '')<p class="mt-2 leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <section aria-labelledby="c-editions">
                    <h2 id="c-editions" class="text-2xl font-bold text-ink">Nadchodzące terminy</h2>
                    @if ($editions->isEmpty())
                        <p class="mt-3 rounded-md bg-gray-50 p-5 text-ink">Nie ma jeszcze zaplanowanych terminów. Zajrzyj tu wkrótce albo napisz do nas przez <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">formularz kontaktowy</a>.</p>
                    @else
                        <ul class="mt-4 space-y-3" role="list">
                            @foreach ($editions as $e)
                                <li class="rounded-md bg-gray-50 p-5">
                                    <h3 class="text-lg font-bold text-ink">{{ $e->datesLabel() }}</h3>
                                    <dl class="mt-2 grid gap-x-6 gap-y-1 text-ink sm:grid-cols-2">
                                        @if ($e->schedule)<div><dt class="inline font-bold">Harmonogram:</dt> <dd class="inline">{{ $e->schedule }}</dd></div>@endif
                                        @if ($e->location)<div><dt class="inline font-bold">Miejsce:</dt> <dd class="inline">{{ $e->location }}</dd></div>@endif
                                        @if ($e->instructor)<div><dt class="inline font-bold">Prowadzący:</dt> <dd class="inline">{{ $e->instructor }}</dd></div>@endif
                                        <div><dt class="inline font-bold">Miejsca:</dt> <dd class="inline">
                                            @if ($e->enrollment_status !== 'open') {{ $e->enrollmentLabel() }}
                                            @elseif ($e->isFull()) brak, zapisy na listę rezerwową
                                            @else wolne {{ $e->free_seats }} z {{ $e->capacity }}
                                            @endif
                                        </dd></div>
                                    </dl>
                                    @if ($e->acceptsRegistrations())
                                        <a href="{{ route('courses.show', [$course, 'termin' => $e->id]) }}#zapis" class="mt-3 inline-block text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            {{ $e->isFull() ? 'Zapisz się na listę rezerwową' : 'Wybierz ten termin' }}<span class="sr-only"> ({{ $e->datesLabel() }})</span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if (filled($course->prerequisites))
                    <section aria-labelledby="c-prereq">
                        <h2 id="c-prereq" class="text-2xl font-bold text-ink">Wymagania wstępne</h2>
                        <p class="mt-3 text-lg leading-relaxed text-ink">{!! nl2br(e($course->prerequisites)) !!}</p>
                    </section>
                @endif

                @if ($course->facilitator)
                    @php $trainer = $course->facilitator; @endphp
                    <section aria-labelledby="c-trainer">
                        <h2 id="c-trainer" class="text-2xl font-bold text-ink">Prowadzi</h2>
                        <div class="mt-4 flex flex-col gap-6 rounded-md bg-gray-50 p-6 sm:flex-row">
                            @if ($trainer->photoUrl())
                                <img src="{{ $trainer->photoUrl() }}" alt="{{ $trainer->name }}" class="h-32 w-32 shrink-0 rounded-md object-cover">
                            @else
                                <span class="flex h-32 w-32 shrink-0 items-center justify-center rounded-md bg-brand-light text-4xl text-brand-dark" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                            @endif
                            <div class="min-w-0">
                                <h3 class="text-xl font-bold text-ink">{{ $trainer->name }}</h3>
                                @if ($trainer->role)<p class="text-ink">{{ $trainer->role }}</p>@endif
                                @if ($trainer->bio)<div class="prose mt-3 max-w-none text-ink">{!! \App\Support\SafeHtml::clean($trainer->bio) !!}</div>@endif
                                @php $links = array_filter(['Strona WWW' => $trainer->website, 'LinkedIn' => $trainer->linkedin, 'Facebook' => $trainer->facebook, 'Instagram' => $trainer->instagram, 'X' => $trainer->twitter]); @endphp
                                @if ($links)
                                    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm" role="list">
                                        @foreach ($links as $label => $url)
                                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $label }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @if ($course->price !== null || filled($course->price_note) || filled($course->funding))
                    <section aria-labelledby="c-pricing">
                        <h2 id="c-pricing" class="text-2xl font-bold text-ink">Cennik</h2>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-md bg-gray-50 p-5"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Cena za osobę</dt><dd class="mt-1 text-2xl font-bold text-ink">{{ $course->priceLabel() }}</dd>@if (filled($course->price_note))<dd class="mt-1 text-sm text-ink">{{ $course->price_note }}</dd>@endif</div>
                            @if (filled($course->funding))<div class="rounded-md bg-gray-50 p-5"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Dofinansowanie</dt><dd class="mt-1 font-semibold text-ink">{{ $course->funding }}</dd></div>@endif
                        </dl>
                    </section>
                @endif

                @if (filled($course->terms))
                    <section aria-labelledby="c-terms">
                        <h2 id="c-terms" class="text-2xl font-bold text-ink">Warunki udziału</h2>
                        <div class="prose mt-3 max-w-none text-ink">{!! \App\Support\SafeHtml::demoteHeadings(\App\Support\SafeHtml::clean($course->terms), 1) !!}</div>
                    </section>
                @endif

                @if ($course->faqItems())
                    <section aria-labelledby="c-faq">
                        <h2 id="c-faq" class="text-2xl font-bold text-ink">Najczęstsze pytania</h2>
                        <div class="mt-4 space-y-2">
                            @foreach ($course->faqItems() as $row)
                                <details class="group rounded-md bg-gray-50">
                                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark [&::-webkit-details-marker]:hidden">
                                        <span>{{ $row['question'] }}</span>
                                        <i class="fa-solid fa-chevron-down text-sm text-brand-dark transition-transform group-open:rotate-180" aria-hidden="true"></i>
                                    </summary>
                                    <div class="px-5 pb-4 leading-relaxed text-ink">{!! nl2br(e($row['answer'])) !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside id="zapis" class="scroll-mt-6 rounded-lg bg-brand-light p-6 lg:sticky lg:top-6" aria-labelledby="c-signup">
                <h2 id="c-signup" class="text-xl font-bold text-ink">Zapisz się</h2>
                @if ($selected)
                    @include('courses.partials.form', ['course' => $course, 'editions' => $editions, 'selected' => $selected])
                @else
                    <p class="mt-3 leading-relaxed text-ink">Rekrutacja jest teraz zamknięta. Napisz do nas, a damy znać o następnym terminie.</p>
                @endif
            </aside>
        </div>
    </div>
@endsection
