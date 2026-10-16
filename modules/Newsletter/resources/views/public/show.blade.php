@extends('layouts.site')

@section('title', ($form?->heading ?? 'Newsletter') . ' — ' . $siteSettings->site_name)
@if ($form?->lead)
    @section('meta_description', $form->lead)
@endif

@section('content')
    @if ($form)
        @include('newsletter::public._signup-style')
        <div class="nls nls-page">
            <div class="nls-wrap">
                <nav aria-label="Okruszki" class="nls-crumbs"><a href="{{ route('home') }}">Strona główna</a> › <span aria-current="page">Newsletter</span></nav>

                <div class="nls-page__grid">
                    <div>
                        @if ($form->eyebrow)<p class="nls-eyebrow">{{ $form->eyebrow }}</p>@endif
                        <h1 class="nls-title">{{ $form->heading }}</h1>
                        @if ($form->lead)<p class="nls-lead">{{ $form->lead }}</p>@endif

                        @if (count($form->benefits ?? []))
                            <h2 class="sr-only">Co dostaniesz</h2>
                            <ul class="nls-list">@foreach ($form->benefits as $benefit)<li>{{ $benefit }}</li>@endforeach</ul>
                        @endif


                        @if ($form->proof)<p class="nls-note" style="margin-top:20px;font-weight:700;color:var(--ink)">{{ $form->proof }}</p>@endif

                        <section class="nls-section" aria-labelledby="nls-how">
                            <h2 id="nls-how" class="nls-h2">Jak to działa</h2>
                            <ol class="nls-steps">
                                <li><span><strong>Wpisz adres e-mail{{ $form->show_topics ? ' i wybierz tematy' : '' }}.</strong>Imię jest nieobowiązkowe.</span></li>
                                <li><span><strong>Potwierdź zapis w e-mailu.</strong>Link jest ważny {{ $ttlDays }} dni. Bez kliknięcia nic więcej nie wyślemy.</span></li>
                                <li><span><strong>Czytaj kolejne wydania.</strong>W stopce każdego są linki do zmiany tematów i wypisu.</span></li>
                            </ol>
                        </section>

                        <section class="nls-section" aria-labelledby="nls-faq-h">
                            <h2 id="nls-faq-h" class="nls-h2" style="margin-bottom:16px">Pytania i odpowiedzi</h2>
                            @include('newsletter::public._faq')
                        </section>

                        <p class="nls-note nls-section">Masz już subskrypcję? Link do zmiany tematów i wypisu znajdziesz w stopce każdej wiadomości.@if (Route::has('newsletter.landing')) <a href="{{ route('newsletter.landing', $form) }}">Zobacz, jak wygląda wydanie</a>.@endif</p>
                    </div>

                    <div class="nls-page__form">
                        @include('newsletter::public._signup-form', ['uid' => 'nls-main', 'source' => 'page_newsletter'])
                    </div>
                </div>
            </div>
        </div>
        @include('newsletter::public._signup-script')
    @elseif ($embedCode)
        <section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="newsletter-heading">
            <h1 id="newsletter-heading" class="mb-2 text-2xl font-extrabold text-ink md:text-3xl">Newsletter</h1>
            <div class="newsletter-embed">{!! $embedCode !!}</div>
        </section>
    @else
        <section class="mx-auto max-w-2xl px-4 py-16 text-center">
            <h1 class="mb-2 text-2xl font-extrabold text-ink">Newsletter</h1>
            <p class="text-muted">Zapisy na newsletter nie są obecnie dostępne.</p>
        </section>
    @endif
@endsection
