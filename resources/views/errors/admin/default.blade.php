@extends('layouts.site')

@php
    $adminErrorMeta = [
        400 => ['title' => 'Nieprawidłowe żądanie', 'desc' => 'Serwer nie mógł zrozumieć tego żądania. Sprawdź, czy adres lub dane formularza są poprawne.'],
        401 => ['title' => 'Wymagane logowanie', 'desc' => 'Ta strona panelu wymaga zalogowania. Zaloguj się, aby kontynuować.'],
        402 => ['title' => 'Płatność wymagana', 'desc' => 'Dostęp do tej funkcji panelu wymaga aktywnej płatności lub subskrypcji.'],
        403 => ['title' => 'Brak dostępu', 'desc' => 'Nie masz uprawnień do wyświetlenia tej strony panelu. Jeśli uważasz, że to pomyłka, skontaktuj się z administratorem.'],
        404 => ['title' => 'Nie znaleziono strony', 'desc' => 'Strona panelu, której szukasz, mogła zostać usunięta, przeniesiona albo nigdy nie istniała pod tym adresem.'],
        405 => ['title' => 'Niedozwolona metoda', 'desc' => 'Ta akcja panelu nie obsługuje takiego żądania. Wróć i spróbuj ponownie z odpowiedniego formularza lub linku.'],
        419 => ['title' => 'Sesja wygasła', 'desc' => 'Twoja sesja wygasła ze względów bezpieczeństwa. Odśwież stronę i zaloguj się ponownie.'],
        422 => ['title' => 'Nieprawidłowe dane', 'desc' => 'Przesłane dane nie przeszły walidacji. Wróć do formularza i popraw zaznaczone pola.'],
        429 => ['title' => 'Zbyt wiele żądań', 'desc' => 'Wysłano zbyt wiele żądań w krótkim czasie. Odczekaj chwilę i spróbuj ponownie.'],
        500 => ['title' => 'Coś poszło nie tak', 'desc' => 'Wystąpił nieoczekiwany błąd po stronie serwera. Zostaliśmy o tym powiadomieni i pracujemy nad jego usunięciem.'],
        502 => ['title' => 'Błąd bramy', 'desc' => 'Serwer pośredniczący otrzymał nieprawidłową odpowiedź. Spróbuj ponownie za chwilę.'],
        503 => ['title' => 'Przerwa techniczna', 'desc' => 'Panel jest chwilowo niedostępny z powodu prac serwisowych. Spróbuj ponownie za kilka minut.'],
        504 => ['title' => 'Przekroczono czas oczekiwania', 'desc' => 'Serwer nie odpowiedział w oczekiwanym czasie. Spróbuj ponownie za chwilę.'],
    ];

    $meta = $adminErrorMeta[$status] ?? [
        'title' => 'Wystąpił błąd',
        'desc' => 'Coś poszło nie tak podczas przetwarzania żądania w panelu administracyjnym.',
    ];
@endphp

@section('title', $meta['title'] . ' — Panel administracyjny — ' . $siteSettings->site_name)
@section('meta_description', $meta['desc'])

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        ['label' => 'Panel administracyjny', 'url' => auth()->check() ? route('admin.dashboard') : null],
        ['label' => 'Błąd ' . $status, 'url' => null],
    ])])
@endsection

@section('content')
    <header class="relative overflow-hidden bg-gradient-to-r from-brand to-brand-dark text-white">
        <i class="fa-solid fa-quote-right pointer-events-none absolute -top-6 -right-4 text-[10rem] text-white/10" aria-hidden="true"></i>
        <div class="relative mx-auto max-w-5xl px-4 py-14 md:py-20">
            <p class="mb-2 text-sm font-bold uppercase tracking-wide text-white/80">Błąd {{ $status }}</p>
            <h1 class="max-w-2xl text-3xl font-bold leading-tight md:text-4xl">{{ $meta['title'] }}</h1>
        </div>
    </header>

    <section class="mx-auto max-w-2xl px-4 py-16 text-center">
        <p class="mb-8 text-muted">{{ $meta['desc'] }}</p>

        <div class="flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark">
                    <i class="fa-solid fa-gauge" aria-hidden="true"></i>
                    Wróć do panelu
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Zaloguj się
                </a>
            @endauth
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded border border-gray-300 px-6 py-3 text-sm font-bold text-ink transition hover:bg-gray-50">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                Strona główna
            </a>
        </div>
    </section>
@endsection
