@extends('layouts.site')

@section('title', 'Sprawdź swoją skrzynkę — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._style')
<div class="np np--narrow">
    <section class="np__hero" aria-labelledby="np-h">
        <span class="np__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg></span>
        <p class="np__eyebrow" style="margin:0">Newsletter {{ $siteSettings->site_name }}</p>
        <h1 id="np-h">Sprawdź swoją skrzynkę</h1>
        <p>{{ $form?->success_message ?? 'Wysłaliśmy e-mail z linkiem potwierdzającym. Kliknij go, aby aktywować zapis.' }}</p>
        <div class="np__box">
            <ol class="np__steps">
                <li>Otwórz wiadomość „Potwierdź zapis na newsletter”.</li>
                <li>Kliknij przycisk „Potwierdzam zapis” — link działa przez {{ (int) ($siteSettings->newsletter_doi_ttl_days ?: 7) }} dni.</li>
                <li>Nie widzisz maila? Sprawdź folder spam lub oferty i dodaj nas do kontaktów.</li>
            </ol>
        </div>
        <div class="np__row">
            <a href="{{ route('home') }}" class="np__btn np__btn--ghost">Wróć na stronę główną</a>
        </div>
    </section>
</div>
@endsection
