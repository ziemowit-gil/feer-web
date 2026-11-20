{{-- Oferta: wstęp, cena, karty warsztatów i przycisk „Zapytaj o termin” (domyślnie do strony kontaktu). --}}
@php
    $kcOffer = $texts->rows('offer_items', 2);
    $kcContact = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcOfferUrl = $texts->url('offer_button_url', $kcContact);
    $kcBadges = ['', 'kc-badge-lime', 'kc-badge-mint'];
@endphp
@if ($kcOffer !== [])
<section id="oferta" class="kc-sec kc-sec-mint" aria-labelledby="kc-offer-title">
    <div class="kc-wrap">
        <div class="kc-head">
            <h2 id="kc-offer-title" class="kc-h2">{{ $texts->get('offer_title') }}</h2>
            @foreach ($texts->paragraphs('offer_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
            @if ($texts->get('offer_price') !== '')
                <p class="kc-price"><strong>{{ $texts->get('offer_price') }}</strong>@if ($texts->get('offer_price_note') !== '')<span>{{ $texts->get('offer_price_note') }}</span>@endif</p>
            @endif
        </div>
        <ul class="kc-grid kc-grid-4">
            @foreach ($kcOffer as $i => [$title, $text])
                <li class="kc-card kc-offer-card">
                    <span class="kc-badge {{ $kcBadges[$i % 3] }}" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $title }}</h3>
                    @if ($text !== '')<p>{{ $text }}</p>@endif
                </li>
            @endforeach
        </ul>
        @if ($kcOfferUrl && $texts->get('offer_button') !== '')
            <div class="kc-more">
                <a href="{{ $kcOfferUrl }}" class="kc-btn">{{ $texts->get('offer_button') }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        @endif
    </div>
</section>
@endif
