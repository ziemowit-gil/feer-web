@extends('layouts.site')

@section('title', 'Sprawdź swoją skrzynkę — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <div class="mb-6 flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-3xl text-brand-dark" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></span>
        </div>
        <h1 class="mb-3 text-2xl font-bold text-ink">Sprawdź swoją skrzynkę</h1>
        <p class="mb-2 text-ink">{{ $form?->success_message ?? 'Wysłaliśmy e-mail z linkiem potwierdzającym. Kliknij go, aby aktywować zapis.' }}</p>
        <p class="text-sm text-muted">Link jest ważny przez {{ (int) ($siteSettings->newsletter_doi_ttl_days ?: 7) }} dni. Jeśli wiadomość nie dotarła, sprawdź folder spam.</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink">← Wróć na stronę główną</a>
    </section>
@endsection
