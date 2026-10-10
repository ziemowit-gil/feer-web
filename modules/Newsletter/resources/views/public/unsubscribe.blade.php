@extends('layouts.site')

@section('title', 'Wypisanie z newslettera — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._style')
<div class="np np--narrow">
    @if ($done)
        <section class="np__hero" aria-labelledby="np-h">
            <span class="np__icon np__icon--off" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12l5 5L20 6"/></svg></span>
            <h1 id="np-h">Gotowe — wypisaliśmy Cię</h1>
            <p>Na adres <strong>{{ $subscriber->email }}</strong> nie wyślemy już newslettera. Zgody zostały wycofane, a Twoje dane możesz w każdej chwili usunąć.</p>
            <div class="np__row">
                <a href="{{ route('newsletter.show') }}" class="np__btn np__btn--ghost">Zapisz się ponownie</a>
                <a href="{{ route('home') }}" class="np__btn np__btn--primary">Strona główna <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>
            <p class="np__foot" style="margin:0">Chcesz usunąć wszystkie dane? <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="np__link">Przejdź do swoich danych</a>.</p>
        </section>
    @else
        <section class="np__hero" aria-labelledby="np-h">
            <span class="np__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg></span>
            <p class="np__eyebrow" style="margin:0">Newsletter {{ $siteSettings->site_name }}</p>
            <h1 id="np-h">Wypisać Cię z newslettera?</h1>
            <p>Adres: <strong>{{ $subscriber->email }}</strong>. Jedno kliknięcie i przestajemy wysyłać. Jeśli chodzi tylko o zbyt wiele tematów, możesz zamiast tego ograniczyć wybór.</p>
            <form method="post" action="{{ route('newsletter.unsubscribe.post', ['token' => $subscriber->token]) }}" style="width:100%;display:grid;gap:18px">
                @csrf
                <div class="np__box">
                    <label for="reason" class="np__label">Dlaczego odchodzisz? <span style="font-weight:400;color:var(--np-muted)">(opcjonalnie — pomaga nam poprawiać newsletter)</span></label>
                    <select id="reason" name="reason" class="np__select">
                        <option value="">— wolę nie mówić —</option>
                        <option>Za dużo wiadomości</option>
                        <option>Treści mnie nie interesują</option>
                        <option>Nie pamiętam, żebym się zapisywał/-a</option>
                        <option>Inny powód</option>
                    </select>
                </div>
                <div class="np__row">
                    <button type="submit" class="np__btn np__btn--danger">Tak, wypisz mnie</button>
                    <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="np__btn np__btn--primary">Wolę zmienić tematy <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
