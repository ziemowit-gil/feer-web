{{--
    Podstrona warsztatu z oferty (KreatywniOfferItemPageController, /warsztat/{slug}) w szablonie „kreatywni”: kremowy pas
    z grupą (odręcznie), nazwą (H1) i krótkim opisem; zdjęcie (opis z panelu albo z biblioteki, bez niego dekoracyjne),
    cena, dłuższy opis (a bez niego krótki), przyciski „Zamów ten warsztat” (adres przycisku oferty albo kontakt)
    i „Zobacz terminy warsztatów” (strona „Kalendarz warsztatów”), powrót do oferty; niżej inne warsztaty z tej grupy.
    Zmienne: $item, $offer, $offerPage, $orderUrl, $orderLabel, $calendarUrl, $related, $price.
--}}
@extends('layouts.site')

@section('title', $item->seoTitle().' — '.$siteSettings->site_name)
{{-- HtmlString: @section z drugim argumentem sam escapuje, a layout ({{ }}) robi to drugi raz — „&” wyszłoby jako „&amp;amp;”. --}}
@section('meta_description', new \Illuminate\Support\HtmlString($item->seoDescription()))
@if ($item->imageUrl())
    @section('og_image', url($item->imageUrl()))
@endif

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $item->title,
            'serviceType' => $item->group ?: 'Warsztaty',
            'description' => $item->seoDescription() ?: null,
            'url' => url()->current(),
            'image' => $item->imageUrl() ? url($item->imageUrl()) : null,
            'provider' => ['@type' => 'Organization', 'name' => $siteSettings->site_name, 'url' => url('/')],
            'areaServed' => trim((string) $siteSettings->contact_city) ?: null,
            'offers' => $price !== '' ? ['@type' => 'Offer', 'description' => $price] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
@php $kcFaq = $item->faqItems(); @endphp
@if ($kcFaq !== [])
    @push('structured_data')
        <script type="application/ld+json">
            {!! json_encode([
                '@@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                ], $kcFaq),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endpush
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_values(array_filter([
        $offerPage ? ['label' => $offerPage->navLabel(), 'url' => $offerPage->publicUrl()] : null,
        ['label' => $item->title, 'url' => null],
    ]))])
@endsection

@php
    $kcImg = $item->imageUrl();
    // Opisy z edytora (bezpieczny HTML: SafeHtml); bez dłuższego opisu krótki trafia do treści zamiast pod tytuł.
    $kcDetails = $item->detailsHtml();
    $kcLead = $item->descriptionHtml();
    if ($kcDetails === '' && $kcLead !== '') {
        $kcDetails = '<p>'.$kcLead.'</p>';
        $kcLead = '';
    }
@endphp

@section('content')
    @include('templates.kreatywni.partials.base-css')
    @once
        <style>
            .kc-oi { display: grid; gap: 2.5rem; align-items: start; }
            @media (min-width: 900px) { .kc-oi.has-photo { grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); } }
            .kc-oi-photo { margin: 0; overflow: hidden; border-radius: var(--kc-radius); background: var(--kc-mint); }
            .kc-oi-photo img { display: block; width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover; }
            .kc-oi-price { display: inline-flex; flex-direction: column; margin: 0 0 1.5rem; padding: 1rem 1.35rem; border-radius: 1rem; background: var(--kc-forest); color: #fff; }
            .kc-oi-price span { font-size: .85rem; font-weight: 700; color: var(--kc-sun); }
            .kc-oi-price strong { font-size: 1.6rem; font-weight: 800; line-height: 1.2; }
            .kc-oi-text p, .kc-oi-text ul, .kc-oi-text ol, .kc-oi-text blockquote { margin: 0 0 1rem; font-size: 1.08rem; line-height: 1.7; }
            .kc-oi-text ul, .kc-oi-text ol { padding-left: 1.4rem; }
            .kc-oi-text ul { list-style: disc; }
            .kc-oi-text ol { list-style: decimal; }
            .kc-oi-text li { margin-bottom: .35rem; }
            .kc-oi-text h2, .kc-oi-text h3 { margin: 1.6rem 0 .6rem; font-weight: 800; line-height: 1.25; color: var(--kc-forest); }
            .kc-oi-text h2 { font-size: 1.45rem; }
            .kc-oi-text h3 { font-size: 1.2rem; }
            .kc-oi-text strong, .kc-lead strong { font-weight: 800; }
            .kc-oi-text a { font-weight: 700; color: var(--kc-forest); text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 3px; }
            .kc-oi-text a:hover { text-decoration-color: var(--kc-lime); }
            .kc-oi-text a:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 2px; }
            .kc-oi-faq-wrap { max-width: 52rem; }
            .kc-oi-faq { display: grid; gap: .75rem; }
            .kc-oi-faq details { border-radius: 1rem; background: var(--kc-mint); }
            .kc-oi-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 2.75rem; padding: 1rem 1.25rem; font-size: 1.08rem; font-weight: 800; color: var(--kc-forest); cursor: pointer; list-style: none; border-radius: 1rem; }
            .kc-oi-faq summary::-webkit-details-marker { display: none; }
            .kc-oi-faq summary::after { content: '+'; flex: none; font-size: 1.5rem; line-height: 1; }
            .kc-oi-faq details[open] summary::after { content: '−'; }
            .kc-oi-faq summary:hover { background: var(--kc-sun); color: var(--kc-ink); }
            .kc-oi-faq summary:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 2px; }
            .kc-oi-faq-a { padding: 0 1.25rem 1.1rem; }
            .kc-oi-faq-a p { margin: 0 0 .75rem; line-height: 1.7; }
            .kc-oi-faq-a p:last-child { margin-bottom: 0; }
            .kc-oi-text blockquote { padding-left: 1rem; border-left: 4px solid var(--kc-lime); }
            .kc-oi-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
            .kc-oi-back { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.75rem; margin-top: 1.25rem; font-weight: 700; color: var(--kc-forest); text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
            .kc-oi-related { display: grid; gap: 1.25rem; margin: 0; padding: 0; list-style: none; }
            @media (min-width: 700px) { .kc-oi-related { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            .kc-oi-related a { display: flex; height: 100%; flex-direction: column; overflow: hidden; border-radius: var(--kc-radius); background: #fff; color: var(--kc-ink); text-decoration: none; }
            .kc-oi-related a:hover { background: var(--kc-sun); }
            .kc-oi-related a:focus-visible { outline: 3px solid var(--kc-forest); outline-offset: 3px; }
            .kc-oi-related img { width: 100%; aspect-ratio: 3 / 2; object-fit: cover; }
            .kc-oi-related span { display: block; padding: 1rem 1.15rem 1.15rem; }
            .kc-oi-related strong { display: block; font-size: 1.05rem; color: var(--kc-forest); }
        </style>
    @endonce
    <div class="kc">
        <section class="kc-page-head" aria-labelledby="kc-page-title">
            <div class="kc-wrap">
                <p class="kc-eyebrow kc-hand">{{ $item->group ?: 'Warsztaty' }}</p>
                <h1 id="kc-page-title">{{ $item->title }}</h1>
                @if ($kcLead !== '')<p class="kc-lead">{!! $kcLead !!}</p>@endif
            </div>
        </section>

        <section class="kc-sec" aria-label="O warsztacie">
            <div @class(['kc-wrap', 'kc-oi', 'has-photo' => (bool) $kcImg])>
                @if ($kcImg)
                    <figure class="kc-oi-photo"><img src="{{ $kcImg }}" alt="{{ $item->imageAltText() }}"></figure>
                @endif
                <div>
                    @if ($price !== '')
                        <p class="kc-oi-price"><span>Cena</span><strong>{{ $price }}</strong></p>
                    @endif
                    <div class="kc-oi-text">
                        {!! $kcDetails !!}
                    </div>
                    <div class="kc-oi-actions">
                        @if ($orderUrl)
                            <a href="{{ $orderUrl }}" class="kc-btn kc-btn-sun">{{ $orderLabel }}<span class="kc-sr"> — {{ $item->title }}</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        @endif
                        @if ($calendarUrl)
                            <a href="{{ $calendarUrl }}" class="kc-btn kc-btn-ghost"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i>Zobacz terminy warsztatów</a>
                        @endif
                    </div>
                    @if ($offerPage)
                        <a href="{{ $offerPage->publicUrl() }}" class="kc-oi-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Wróć do oferty warsztatów</a>
                    @endif
                </div>
            </div>
        </section>

        @if ($kcFaq !== [])
            <section class="kc-sec" aria-labelledby="kc-oi-faq">
                <div class="kc-wrap kc-oi-faq-wrap">
                    <h2 id="kc-oi-faq" class="kc-h2 kc-head">Najczęstsze pytania</h2>
                    <div class="kc-oi-faq">
                        @foreach ($kcFaq as $kcF)
                            <details>
                                <summary>{{ $kcF['q'] }}</summary>
                                <div class="kc-oi-faq-a">
                                    @foreach (preg_split('/\R\s*\R/u', $kcF['a']) as $kcP)
                                        <p>{!! nl2br(e(trim($kcP)), false) !!}</p>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="kc-sec kc-sec-mint" aria-labelledby="kc-oi-related">
                <div class="kc-wrap">
                    <h2 id="kc-oi-related" class="kc-h2 kc-head">{{ $item->group ? 'Inne warsztaty: '.$item->group : 'Inne warsztaty' }}</h2>
                    <ul class="kc-oi-related">
                        @foreach ($related as $kcR)
                            <li>
                                <a href="{{ $kcR->publicUrl() }}">
                                    @if ($kcR->imageUrl())<img src="{{ $kcR->imageUrl() }}" alt="" loading="lazy">@endif
                                    <span><strong>{{ $kcR->title }}</strong>@if ($kcR->descriptionText() !== '') {{ \Illuminate\Support\Str::limit($kcR->descriptionText(), 110) }}@endif</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </div>
@endsection
