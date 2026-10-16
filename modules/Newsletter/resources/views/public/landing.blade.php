@extends('layouts.site')

@section('title', $form->heading . ' — ' . $siteSettings->site_name)
@if ($form->lead)
    @section('meta_description', $form->lead)
@endif

@section('content')
@include('newsletter::public._signup-style')
@php
    $benefits = $form->benefits ?? [];
    $issueTitle = ($form->eyebrow ?: 'Newsletter ' . $siteSettings->site_name) . ' — ' . now()->translatedFormat('F Y');
@endphp
<div class="nls nls-landing">
    {{-- 1. Hero na pełny ekran: obietnica + formularz od razu pod ręką --}}
    <section class="nls-hero" aria-labelledby="nls-hero-h">
        <div class="nls-wrap">
            <div class="nls-hero__grid">
                <div class="nls-hero__copy">
                    @if ($form->eyebrow)<p class="nls-eyebrow">{{ $form->eyebrow }}</p>@endif
                    <h1 id="nls-hero-h" class="nls-title">{{ $form->heading }}</h1>
                    @if ($form->lead)<p class="nls-lead">{{ $form->lead }}</p>@endif
                    @if (count($benefits))
                        <ul class="nls-list">@foreach ($benefits as $benefit)<li>{{ $benefit }}</li>@endforeach</ul>
                    @endif
                    <a href="#nls-sample" class="nls-hero__more">{{ $form->landingText('more_link') }} <span aria-hidden="true">↓</span></a>
                </div>
                <div>
                    @include('newsletter::public._signup-form', ['uid' => 'nls-top', 'source' => 'landing_top', 'panelTitle' => $form->landingText('panel_title')])
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Podgląd wydania + jak to działa --}}
    <section id="nls-sample" class="nls-band nls-band--tint" aria-labelledby="nls-sample-h" tabindex="-1">
        <div class="nls-wrap nls-split">
            <div>
                <div class="nls-band__head">
                    <h2 id="nls-sample-h" class="nls-h2">{{ $form->landingText('sample_title') }}</h2>
                    <p>{{ $form->landingText('sample_lead') }}</p>
                </div>
                <h3 class="nls-h2" style="font-size:20px">Jak to działa</h3>
                <ol class="nls-steps">
                    <li><span><strong>Wpisz adres e-mail{{ $form->show_topics ? ' i wybierz tematy' : '' }}.</strong>Imię jest nieobowiązkowe.</span></li>
                    <li><span><strong>Potwierdź zapis w e-mailu.</strong>Link jest ważny {{ $ttlDays }} dni. Bez kliknięcia nic więcej nie wyślemy.</span></li>
                    <li><span><strong>Czytaj kolejne wydania.</strong>Tematy zmienisz, a z newslettera wypiszesz się jednym kliknięciem.</span></li>
                </ol>
            </div>
            <figure class="nls-mail" style="margin:0" aria-labelledby="nls-mail-cap">
                <figcaption id="nls-mail-cap" class="sr-only">Podgląd przykładowego wydania newslettera</figcaption>
                <div class="nls-mail__meta">
                    <span><b>Od:</b> {{ $siteSettings->site_name }}</span>
                    <span><b>Temat:</b> {{ $issueTitle }}</span>
                </div>
                <div class="nls-mail__body">
                    <p class="nls-mail__brand">{{ $form->eyebrow ?: 'Newsletter' }}</p>
                    <p class="nls-mail__title">{{ $form->landingText('sample_greeting') }}</p>
                    <ul class="nls-mail__items">
                        @forelse ($sample['items'] as $item)
                            <li><h4><a href="{{ $item->url }}">{{ html_entity_decode(strip_tags($item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</a></h4>@if ($item->excerpt)<p>{{ \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($item->excerpt), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 140) }}</p>@endif</li>
                        @empty
                            @foreach (array_slice($benefits, 0, 3) as $benefit)<li><h4>{{ $benefit }}</h4></li>@endforeach
                        @endforelse
                    </ul>
                    <p class="nls-mail__foot">W stopce: zmień tematy · wypisz się</p>
                </div>
            </figure>
        </div>
    </section>

    {{-- 4. FAQ --}}
    <section class="nls-band" aria-labelledby="nls-faq-h">
        <div class="nls-wrap" style="max-width:820px">
            <div class="nls-band__head"><h2 id="nls-faq-h" class="nls-h2">{{ $form->landingText('faq_title') }}</h2></div>
            @include('newsletter::public._faq')
        </div>
    </section>

    {{-- 5. Formularz na dole: dla tych, którzy doczytali --}}
    <section class="nls-band nls-cta" aria-labelledby="nls-bottom-h">
        <div class="nls-wrap nls-split">
            <div class="nls-band__head">
                <h2 id="nls-bottom-h" class="nls-h2">{{ $form->landingText('cta_title') }}</h2>
                @if ($cta = $form->landingText('cta_lead') ?: $form->lead)<p>{{ $cta }}</p>@endif
                <p>Wolisz krótszą wersję? <a href="{{ route('newsletter.form', $form) }}">Przejdź do zwykłego formularza</a>.</p>
            </div>
            <div>
                @include('newsletter::public._signup-form', ['uid' => 'nls-bottom', 'source' => 'landing_bottom', 'headingLevel' => 3])
            </div>
        </div>
    </section>
</div>
@include('newsletter::public._signup-script')
@endsection
