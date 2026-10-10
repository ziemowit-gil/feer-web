@extends('layouts.site')

@section('title', 'Dane usunięte — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._style')
<div class="np np--narrow">
    <section class="np__hero" aria-labelledby="np-h">
        <span class="np__icon np__icon--off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
        <h1 id="np-h">Twoje dane zostały usunięte</h1>
        <p>Adres e-mail, imię i telefon zostały zanonimizowane. Przez 30 dni adres pozostaje na liście blokad, żebyśmy przypadkiem nic nie wysłali. Dziękujemy, że byłeś/-aś z nami.</p>
        <div class="np__row">
            <a href="{{ route('home') }}" class="np__btn np__btn--primary">Strona główna <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
    </section>
</div>
@endsection
