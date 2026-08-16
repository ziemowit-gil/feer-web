@extends('layouts.site')

@section('title', 'Błąd serwera')
@section('meta_description', 'Wystąpił nieoczekiwany błąd. Pracujemy nad jego usunięciem.')

@section('content')
    <section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-20 text-center">
        <span class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-brand-light text-3xl text-brand">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        </span>

        <p class="mb-2 text-sm font-bold uppercase tracking-wide text-brand">Błąd 500</p>
        <h1 class="mb-3 text-3xl font-bold text-ink">Coś poszło nie tak</h1>
        <p class="mb-8 max-w-md text-muted">
            Wystąpił nieoczekiwany błąd po stronie serwera. Zostaliśmy o tym powiadomieni i pracujemy nad jego usunięciem.
            Spróbuj ponownie za chwilę.
        </p>

        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            Powrót do głównej
        </a>
    </section>
@endsection
