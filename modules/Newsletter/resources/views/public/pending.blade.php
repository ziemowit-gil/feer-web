@extends('layouts.site')

@section('title', 'Sprawdź swoją skrzynkę — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._signup-style')
@php $ttlDays ??= (int) ($siteSettings->newsletter_doi_ttl_days ?: 7); @endphp
<div class="nls nls-page">
    <div class="nls-wrap" style="max-width:720px">
        <section class="nls-panel nls-done" aria-labelledby="np-h" style="margin-top:24px">
            <span class="nls-done__mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18v12H3z"/><path d="M3 7l9 6 9-6"/></svg></span>
            <p class="nls-eyebrow" style="margin:0">Newsletter {{ $siteSettings->site_name }}</p>
            <h1 id="np-h" class="nls-title" tabindex="-1">Sprawdź swoją skrzynkę</h1>
            <p>{{ $form?->success_message ?? 'Wysłaliśmy e-mail z linkiem potwierdzającym. Kliknij go, aby aktywować zapis.' }}</p>
            <ol class="nls-steps">
                <li><span>Otwórz wiadomość „Potwierdź zapis na newsletter”.</span></li>
                <li><span>Kliknij „Potwierdzam zapis”. Link jest ważny {{ $ttlDays }} {{ $ttlDays === 1 ? 'dzień' : 'dni' }}.</span></li>
                <li><span>Nie ma wiadomości po kilku minutach? Zajrzyj do folderu spam lub oferty i dodaj nas do kontaktów.</span></li>
            </ol>
            <p class="nls-note">Bez potwierdzenia nie wyślemy Ci nic więcej. Pomyłka w adresie? <a href="{{ route('newsletter.show') }}">Zapisz się ponownie</a>.</p>
            <p><a href="{{ route('home') }}" class="nls-btn nls-btn--ghost">Wróć na stronę główną</a></p>
        </section>
    </div>
</div>
@endsection
