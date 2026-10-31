@extends('layouts.site')

@section('title', 'Zgłoszenie przyjęte — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i zajęcia', 'url' => route('courses.index')],
        ['label' => $course->title, 'url' => route('courses.show', $course)],
        ['label' => 'Zgłoszenie przyjęte', 'url' => null],
    ]])
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-extrabold leading-tight text-ink md:text-4xl" tabindex="-1" x-data x-init="$el.focus()">
            {{ $registration->status === 'waitlist' ? 'Jesteś na liście rezerwowej' : 'Dziękujemy za zgłoszenie' }}
        </h1>
        <p class="mt-4 text-lg leading-relaxed text-ink">
            {{ $registration->first_name }}, zapisaliśmy Cię na szkolenie <strong>{{ $course->title }}</strong>, termin: {{ $registration->edition->datesLabel() }}.
            @if ($registration->status === 'waitlist')
                Termin jest pełny, więc odezwiemy się, gdy zwolni się miejsce.
            @else
                Koordynator potwierdzi udział wiadomością na podany adres e-mail.
            @endif
        </p>
        <div class="mt-6 rounded-md bg-brand-light p-5 text-ink">
            <p>Kod referencyjny (użyj go w tytule przelewu, jeśli szkolenie jest płatne):</p>
            <p class="mt-1 text-2xl font-extrabold tracking-wider">{{ $registration->payment_reference }}</p>
            <p class="mt-2">Cena: {{ $course->priceLabel() }}</p>
        </div>
        <p class="mt-8"><a href="{{ route('courses.index') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">← Wróć do oferty szkoleń</a></p>
    </div>
@endsection
