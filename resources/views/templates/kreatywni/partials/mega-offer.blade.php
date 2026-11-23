{{--
    Karta oferty w rozwijanym menu pozycji „Oferta” (szablon „kreatywni”, KreatywniMegaMenu::withOffer): ciemnozielona
    karta (kc-dark — żółty obrys fokusu) z nazwą i ceną ofert z modułu „Mikrooferta – Kreatywni” (do 3), przyciskiem
    zamówienia (adres przycisku oferty, a bez niego strona kontaktu) i — gdy warsztatów jest więcej niż mieści menu —
    „Cała oferta warsztatów (+N)”. Warsztaty z podstronami są w kolumnach obok (offerGroups).
    Zmienne: $item (offerCard: prices [{name, price, unit}], button, buttonUrl, allUrl, more), $kcMegaIndex.
--}}
@php
    $kcMo = $item->offerCard;
@endphp
@once
    <style>
        @media (min-width: 1024px) { .kc-mega-in.has-offer { grid-template-columns: 17rem minmax(0, 1fr) 18rem; } }
        .kc-mega-offer { align-self: start; padding: 1.35rem 1.5rem 1.5rem; border-radius: 1.25rem; }
        .kc-mega-offer-row { margin: 0 0 1rem; }
        .kc-mega-offer-name { display: block; font-size: 1rem; font-weight: 800; color: var(--kc-sun); }
        .kc-mega-offer-price { display: block; font-size: 1.75rem; font-weight: 800; line-height: 1.15; color: #fff; }
        .kc-mega-offer-unit { display: block; font-size: .95rem; font-weight: 700; color: #fff; }
        .kc-mega-offer .kc-btn { width: 100%; margin-top: .25rem; }
        .kc-mega-offer-all { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.75rem; margin-top: .5rem; font-weight: 800; color: #fff; text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .kc-mega-offer-all:hover { text-decoration-color: var(--kc-sun); }
    </style>
@endonce
<div class="kc-mega-offer kc-dark">
    @foreach ($kcMo['prices'] as $kcP)
        <p class="kc-mega-offer-row">
            <span class="kc-mega-offer-name">{{ $kcP['name'] }}</span>
            <span class="kc-mega-offer-price">{{ $kcP['price'] }}</span>
            @if ($kcP['unit'] !== '')<span class="kc-mega-offer-unit">{{ $kcP['unit'] }}</span>@endif
        </p>
    @endforeach
    @if ($kcMo['buttonUrl'])
        <a href="{{ $kcMo['buttonUrl'] }}" class="kc-btn kc-btn-sun">{{ $kcMo['button'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    @endif
    @if ($kcMo['more'] > 0)
        <a href="{{ $kcMo['allUrl'] }}" class="kc-mega-offer-all">Cała oferta warsztatów (+{{ $kcMo['more'] }}) <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    @endif
</div>
