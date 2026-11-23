{{--
    Blok sprzedażowy oferty (strona główna i góra strony „Oferta”), dla każdej oferty: ciemna karta cenowa — nazwa,
    duża cena z jednostką, dopisek, korzyści, przycisk (domyślnie strona kontaktu) i telefon — oraz warsztaty
    pogrupowane pod nagłówkami (wiersz „Tytuł | grupa | opis”) — jako „pigułki” (same nazwy), zwykła lista (nazwa, pod nią
    opis) albo karty z opisami (KreatywniTexts::OFFER_STYLES).
    Oferty z mikro modułu „Mikrooferta – Kreatywni” (KreatywniOffer::forDisplay), a bez niego — jedna oferta z tekstów
    (KreatywniTexts::offer_*, wygląd warsztatów z Ustawienia → Szablon → „Wygląd listy warsztatów”).
    Zmienne: $texts, $kcHeadingLevel (poziom nagłówków grup: 3 na głównej pod H2 sekcji, 2 na stronie Oferta pod H1),
    opcjonalnie $kcOffers (oferty już policzone przez sekcję strony głównej).
--}}
@php
    $kcHeadingLevel ??= 3;
    $kcOffers ??= \App\Models\KreatywniOffer::forDisplay($texts);
    $kcContact = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcPhone = trim((string) $siteSettings->contact_phone);
    $kcH = 'h'.$kcHeadingLevel;
@endphp
@if ($kcOffers !== [])
    @once
        <style>
            .kc-sales + .kc-sales { margin-top: 3rem; }
            .kc-plain-list { margin: 0; padding: 0; list-style: none; }
            .kc-plain-list li { padding: .3rem 0; font-size: 1.05rem; line-height: 1.45; color: var(--kc-forest); break-inside: avoid; }
            .kc-plain-list .kc-offer-name { font-weight: 700; }
            .kc-plain-list .kc-offer-desc { display: block; margin-top: .1rem; font-size: .95rem; color: var(--kc-ink); }
            .kc-offer-cards { display: grid; gap: .75rem; margin: 0; padding: 0; list-style: none; }
            @media (min-width: 640px) { .kc-offer-cards { grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); } }
            .kc-offer-cards li { display: flex; flex-direction: column; gap: .3rem; padding: 1rem 1.15rem; border-radius: 1rem; background: #fff; line-height: 1.45; }
            .kc-offer-cards .kc-offer-name { font-size: 1.02rem; font-weight: 800; color: var(--kc-forest); }
            .kc-offer-cards .kc-offer-desc { font-size: .95rem; color: var(--kc-ink); }
            @media (min-width: 640px) { .kc-collection.is-main .kc-plain-list { columns: 2; column-gap: 2.5rem; } }
            @media (min-width: 1280px) { .kc-collection.is-main .kc-plain-list { columns: 3; } }
        </style>
    @endonce
@endif
@foreach ($kcOffers as $kcOffer)
    @php
        $kcOfferUrl = $kcOffer['button_url'] ?? $kcContact;
        $kcListClass = match ($kcOffer['style']) { 'list' => 'kc-plain-list', 'cards' => 'kc-offer-cards', default => 'kc-chips' };
    @endphp
    <div class="kc-sales">
        <div class="kc-price-card kc-dark">
            <p class="kc-price-name">{{ $kcOffer['name'] }}</p>
            @if ($kcOffer['price'] !== '')
                <p class="kc-price-big"><strong>{{ $kcOffer['price'] }}</strong>@if ($kcOffer['unit'] !== '') <span>{{ $kcOffer['unit'] }}</span>@endif</p>
            @endif
            @if ($kcOffer['note'] !== '')<p class="kc-price-note">{{ $kcOffer['note'] }}</p>@endif
            @if ($kcOffer['features'] !== [])
                <ul class="kc-price-features">
                    @foreach ($kcOffer['features'] as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($kcOfferUrl && $kcOffer['button'] !== '')
                <a href="{{ $kcOfferUrl }}" class="kc-btn kc-btn-sun kc-btn-block">{{ $kcOffer['button'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @endif
            @if ($kcPhone !== '')
                <p class="kc-price-phone">lub zadzwoń: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $kcPhone) }}">{{ $kcPhone }}</a></p>
            @endif
        </div>

        @if ($kcOffer['groups'] !== [])
            <div class="kc-collections">
                @foreach ($kcOffer['groups'] as $group => $titles)
                    <div @class(['kc-collection', 'is-main' => $loop->first])>
                        <{{ $kcH }} class="kc-collection-head">{{ $group }}</{{ $kcH }}>
                        <ul class="{{ $kcListClass }}">
                            @foreach ($titles as $kcItem)
                                @if ($kcListClass === 'kc-chips' || $kcItem['desc'] === '')
                                    <li>@if ($kcListClass === 'kc-chips'){{ $kcItem['title'] }}@else<span class="kc-offer-name">{{ $kcItem['title'] }}</span>@endif</li>
                                @else
                                    <li><span class="kc-offer-name">{{ $kcItem['title'] }}</span> <span class="kc-offer-desc">{{ $kcItem['desc'] }}</span></li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
