@extends('layouts.site')

@section('title', 'Nie znaleziono strony')
@section('meta_description', 'Strona, której szukasz, nie istnieje lub została przeniesiona.')

@section('content')
    <section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-20 text-center">
        <span class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-brand-light text-3xl text-brand">
            <i class="fa-solid fa-map-signs" aria-hidden="true"></i>
        </span>

        <p class="mb-2 text-sm font-bold uppercase tracking-wide text-brand">Błąd 404</p>
        <h1 class="mb-3 text-3xl font-bold text-ink">Nie znaleziono strony</h1>
        <p class="mb-8 max-w-md text-muted">
            Strona, której szukasz, mogła zostać usunięta, przeniesiona albo nigdy nie istniała pod tym adresem.
        </p>

        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            Powrót do głównej
        </a>
    </section>
@endsection
