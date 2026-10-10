@extends('layouts.site')

@section('title', ($expired ? 'Link wygasł' : 'Zapis potwierdzony') . ' — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._style')
<div class="np np--narrow">
    @if ($expired)
        <section class="np__hero" aria-labelledby="np-h">
            <span class="np__icon np__icon--warn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <h1 id="np-h">Ten link już wygasł</h1>
            <p>Linki potwierdzające są ważne przez {{ (int) ($siteSettings->newsletter_doi_ttl_days ?: 7) }} dni. Nic straconego — zapisz się ponownie, a od razu wyślemy nowy.</p>
            <div class="np__row">
                <a href="{{ route('newsletter.show') }}" class="np__btn np__btn--primary">Zapisz się ponownie <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                <a href="{{ route('home') }}" class="np__btn np__btn--ghost">Strona główna</a>
            </div>
        </section>
    @else
        <section class="np__hero" aria-labelledby="np-h">
            <span class="np__icon np__icon--ok" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12l5 5L20 6"/></svg></span>
            <p class="np__eyebrow" style="margin:0">Newsletter {{ $siteSettings->site_name }}</p>
            <h1 id="np-h">Dziękujemy{{ $subscriber->firstName() ? ', ' . $subscriber->firstName() : '' }}! Zapis potwierdzony</h1>
            <p>Adres <strong>{{ $subscriber->email }}</strong> jest aktywny. Będziesz otrzymywać wiadomości na temat:</p>
            <ul class="np__chips" aria-label="Wybrane tematy">
                @forelse ($subscriber->topicLabels() as $t)<li>{{ $t }}</li>@empty<li>Aktualności</li>@endforelse
            </ul>
            <div class="np__box">
                <ol class="np__steps">
                    <li>Pierwszy newsletter dotrze przy najbliższej wysyłce.</li>
                    <li>Tematy i kanały zmienisz w każdej chwili na stronie preferencji.</li>
                    <li>Link do wypisu jednym kliknięciem jest w stopce każdej wiadomości.</li>
                </ol>
            </div>
            <div class="np__row">
                <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="np__btn np__btn--primary">Ustaw preferencje <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                <a href="{{ route('home') }}" class="np__btn np__btn--ghost">Przejdź na stronę główną</a>
            </div>
        </section>
        <p class="np__foot">Wskazówka: dodaj adres nadawcy do kontaktów, żeby newsletter nie trafiał do spamu.</p>
    @endif
</div>
@endsection
