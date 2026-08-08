@extends('layouts.site')

@section('title', 'Sprawdź swoją skrzynkę — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <div class="mb-6 flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-3xl text-brand" aria-hidden="true">
                <i class="fa-solid fa-envelope-open-text"></i>
            </span>
        </div>

        @if ($updated)
            <h1 class="mb-3 text-2xl font-bold text-ink">Preferencje zaktualizowane</h1>
            <p class="text-muted">Twoje tematy powiadomień zostały zmienione. Zmiany są aktywne od razu.</p>
        @else
            <h1 class="mb-3 text-2xl font-bold text-ink">Sprawdź swoją skrzynkę</h1>
            <p class="mb-2 text-muted">Wysłaliśmy e-mail z&nbsp;linkiem potwierdzającym.</p>
            <p class="text-sm text-muted">Jeśli wiadomość nie dotarła, sprawdź folder spam.</p>
        @endif

        <a href="{{ route('home') }}" class="mt-8 inline-block text-sm font-bold text-brand hover:text-brand-dark">
            ← Wróć na stronę główną
        </a>
    </section>
@endsection
