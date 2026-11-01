@extends('layouts.site')

@section('title', 'Szkolenia i zajęcia — ' . $siteSettings->site_name)
@section('meta_description', 'Oferta szkoleń i zajęć: terminy, formy, ceny i zapisy online.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => 'Szkolenia i zajęcia', 'url' => null]]])
@endsection

{{--
    Oferta szkoleń. W wariancie FEER IS nagłówek jest w kolorze zakładek portalu usług (jak na stronie głównej),
    w pozostałych szablonach jasnoszary. Filtry to zwykłe linki GET (działają bez JS), aktywny filtr ma aria-current.
--}}
@php
    $isPortal = $siteSettings->themeVariant() === 'feer_is';
    $chip = 'inline-flex min-h-10 items-center rounded-md px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $url = fn (array $q) => route('courses.index', array_filter($q));
@endphp

@section('content')
    <section class="{{ $isPortal ? 'bg-brand-dark' : 'bg-gray-50' }}">
        <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-5xl {{ $isPortal ? 'text-white' : 'text-ink' }}">Szkolenia i zajęcia</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed {{ $isPortal ? 'text-white' : 'text-ink' }}">Wybierz szkolenie i termin, który Ci pasuje. Przy zapisie możesz opisać potrzebne dostosowania, na przykład czytnik ekranu, większą czcionkę albo asystenta.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 pb-12" style="padding-top:2rem">
        <nav aria-label="Filtry szkoleń" class="mb-8 space-y-4">
            <div>
                <p id="f-cat" class="mb-2 text-sm font-bold text-ink">Kategoria</p>
                <ul class="flex flex-wrap gap-2" role="list" aria-labelledby="f-cat">
                    <li><a href="{{ $url(['format' => $format]) }}" @if (! $category) aria-current="true" @endif class="{{ $chip }} {{ ! $category ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-gray-100' }}">Wszystkie</a></li>
                    @foreach (\App\Models\Course::CATEGORIES as $key => $label)
                        {{-- W FEER IS bez filtra „Inne” (takie szkolenia widać pod „Wszystkie”). --}}
                        @continue($isPortal && $key === 'other')
                        <li><a href="{{ $url(['kategoria' => $key, 'format' => $format]) }}" @if ($category === $key) aria-current="true" @endif class="{{ $chip }} {{ $category === $key ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-gray-100' }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p id="f-fmt" class="mb-2 text-sm font-bold text-ink">Forma</p>
                <ul class="flex flex-wrap gap-2" role="list" aria-labelledby="f-fmt">
                    <li><a href="{{ $url(['kategoria' => $category]) }}" @if (! $format) aria-current="true" @endif class="{{ $chip }} {{ ! $format ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-gray-100' }}">Każda</a></li>
                    @foreach (\App\Models\Course::FORMATS as $key => $label)
                        <li><a href="{{ $url(['kategoria' => $category, 'format' => $key]) }}" @if ($format === $key) aria-current="true" @endif class="{{ $chip }} {{ $format === $key ? 'bg-brand-dark text-white' : 'bg-gray-50 text-ink hover:bg-gray-100' }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <p class="sr-only" role="status">Liczba szkoleń: {{ $courses->count() }}</p>

        @if ($courses->isEmpty())
            <div class="rounded-md bg-gray-50 p-8 text-center text-ink">
                Nie znaleźliśmy szkoleń dla wybranych filtrów.
                <a href="{{ route('courses.index') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">Pokaż wszystkie</a>.
            </div>
        @else
            <ul class="grid gap-6 md:grid-cols-2" role="list">
                @foreach ($courses as $course)
                    @php $next = $course->nextEdition(); @endphp
                    <li>
                        <article class="feer-card group relative flex h-full flex-col rounded-lg bg-gray-50 p-6 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand">
                            <p class="text-xs font-bold uppercase tracking-widest text-muted">
                                {{ $course->categoryLabel() }} · {{ $course->formatLabel() }}@if ($course->is_featured) · polecane @endif
                            </p>
                            <h2 class="mt-2 text-xl font-bold leading-snug text-ink group-hover:text-brand-dark">
                                <a href="{{ route('courses.show', $course) }}" class="stretched-link focus-visible:outline-none">{{ $course->title }}</a>
                            </h2>
                            @if ($course->lead)<p class="mt-2 flex-1 leading-relaxed text-ink">{{ $course->lead }}</p>@endif
                            <p class="mt-4 text-sm text-ink">
                                <strong>{{ $course->priceLabel() }}</strong>
                                @if ($next)
                                    · najbliższy termin: {{ $next->datesLabel() }}
                                    @if ($next->acceptsRegistrations())
                                        ({{ $next->isFull() ? 'lista rezerwowa' : 'wolne miejsca: '.$next->free_seats }})
                                    @endif
                                @else
                                    · terminy wkrótce
                                @endif
                            </p>
                            <p class="mt-3 text-sm font-bold text-brand-dark" aria-hidden="true">{{ $siteSettings->coursesRegistrationEnabled() ? 'Zobacz terminy i zapisz się' : 'Zobacz program i terminy' }}</p>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
