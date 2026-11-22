{{--
    Blok sprzedażowy oferty (strona główna i góra strony „Oferta”): ciemna karta cenowa — nazwa, duża cena z jednostką,
    dopisek, korzyści, „Zamów warsztaty” (domyślnie strona kontaktu) i telefon — oraz warsztaty pogrupowane
    (Tytuł | grupa z KreatywniTexts::offer_items) pod nagłówkami, płasko: bez kart, ikon i kolorowych kropek — jako „pigułki”
    albo zwykła lista, zależnie od Ustawienia → Szablon → „Wygląd listy warsztatów” (KreatywniTexts::offerStyle()).
    Zmienne: $texts, $kcHeadingLevel (poziom nagłówków grup: 3 na głównej pod H2 sekcji, 2 na stronie Oferta pod H1).
--}}
@php
    $kcHeadingLevel ??= 3;
    $kcContact = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcOfferUrl = $texts->url('offer_button_url', $kcContact);
    $kcPhone = trim((string) $siteSettings->contact_phone);
    $kcGroups = collect($texts->rows('offer_items', 2))
        ->groupBy(fn ($row) => $row[1] !== '' ? $row[1] : 'Warsztaty', preserveKeys: false)
        ->map(fn ($rows) => $rows->pluck(0)->all());
    $kcH = 'h'.$kcHeadingLevel;
    $kcListClass = $texts->offerStyle() === 'list' ? 'kc-plain-list' : 'kc-chips';
@endphp
@if ($kcListClass === 'kc-plain-list')
    @once
        <style>
            .kc-plain-list { margin: 0; padding: 0; list-style: none; }
            .kc-plain-list li { padding: .3rem 0; font-size: 1.05rem; line-height: 1.45; color: var(--kc-forest); break-inside: avoid; }
            @media (min-width: 640px) { .kc-collection.is-main .kc-plain-list { columns: 2; column-gap: 2.5rem; } }
            @media (min-width: 1280px) { .kc-collection.is-main .kc-plain-list { columns: 3; } }
        </style>
    @endonce
@endif
<div class="kc-sales">
    <div class="kc-price-card kc-dark">
        <p class="kc-price-name">{{ $texts->get('offer_card_title') }}</p>
        <p class="kc-price-big"><strong>{{ $texts->get('offer_price') }}</strong> <span>{{ $texts->get('offer_price_unit') }}</span></p>
        @if ($texts->get('offer_price_note') !== '')<p class="kc-price-note">{{ $texts->get('offer_price_note') }}</p>@endif
        @if ($texts->rows('offer_features') !== [])
            <ul class="kc-price-features">
                @foreach ($texts->rows('offer_features') as [$feature])
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        @endif
        @if ($kcOfferUrl && $texts->get('offer_button') !== '')
            <a href="{{ $kcOfferUrl }}" class="kc-btn kc-btn-sun kc-btn-block">{{ $texts->get('offer_button') }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        @endif
        @if ($kcPhone !== '')
            <p class="kc-price-phone">lub zadzwoń: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $kcPhone) }}">{{ $kcPhone }}</a></p>
        @endif
    </div>

    @if ($kcGroups->isNotEmpty())
        <div class="kc-collections">
            @foreach ($kcGroups as $group => $titles)
                <div @class(['kc-collection', 'is-main' => $loop->first])>
                    <{{ $kcH }} class="kc-collection-head">{{ $group }}</{{ $kcH }}>
                    <ul class="{{ $kcListClass }}">
                        @foreach ($titles as $title)
                            <li>{{ $title }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>
