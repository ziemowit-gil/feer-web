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
            <p class="mt-6 inline-block rounded-md bg-white px-4 py-2 font-bold text-ink">Cena: {{ $course->priceLabel() }}</p>
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
