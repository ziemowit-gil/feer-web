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
            .kc-offer-cards { display: grid; gap: 1rem; margin: 0; padding: 0; list-style: none; }
            @media (min-width: 640px) { .kc-offer-cards { grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); } }
            .kc-offer-cards li { position: relative; display: flex; flex-direction: column; overflow: hidden; border-radius: 1rem; background: #fff; line-height: 1.45; }
            .kc-offer-cards li:hover { background: var(--kc-sun); }
            .kc-offer-cards li:focus-within { outline: 3px solid var(--kc-forest); outline-offset: 3px; }
            .kc-offer-img { display: block; width: 100%; aspect-ratio: 3 / 2; object-fit: cover; background: var(--kc-mint); }
            .kc-offer-body { display: flex; flex: 1; flex-direction: column; gap: .35rem; padding: .95rem 1.15rem 1.1rem; }
            .kc-offer-cards .kc-offer-name { margin: 0; font-size: 1.05rem; font-weight: 800; line-height: 1.3; color: var(--kc-forest); }
            .kc-offer-cards .kc-offer-name a { color: inherit; text-decoration: none; }
            .kc-offer-cards .kc-offer-name a::after { content: ''; position: absolute; inset: 0; }
            .kc-offer-cards .kc-offer-name a:focus-visible { outline: none; }
            .kc-offer-cards li:hover .kc-offer-name a { text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 4px; }
            .kc-offer-price { margin: 0; font-weight: 800; color: var(--kc-green); }
            .kc-offer-cards li:hover .kc-offer-price { color: var(--kc-forest); } /* zielony na żółtym ma < 4,5:1 */
            .kc-offer-cards .kc-offer-desc { margin: 0; font-size: .95rem; color: var(--kc-ink); }
            .kc-offer-more { margin-top: auto; padding-top: .35rem; font-size: .9rem; font-weight: 700; color: var(--kc-forest); }
            .kc-chips a, .kc-plain-list a { color: inherit; text-decoration: underline; text-decoration-color: var(--kc-lime); text-decoration-thickness: 2px; text-underline-offset: 4px; }
            .kc-chips a:hover, .kc-plain-list a:hover { text-decoration-color: var(--kc-forest); }
            @media (min-width: 640px) { .kc-collection.is-main .kc-plain-list { columns: 2; column-gap: 2.5rem; } }
            @media (min-width: 1280px) { .kc-collection.is-main .kc-plain-list { columns: 3; } }
        </style>
    @endonce
@endif
@foreach ($kcOffers as $kcOffer)
    @php
        $kcOfferUrl = $kcOffer['button_url'] ?? $kcContact;
        // Grafiki są tylko w kartach: gdy któryś warsztat ma grafikę, blok pokazuje karty niezależnie od wybranego wyglądu.
        $kcListClass = \App\Models\KreatywniOffer::groupsHaveImages($kcOffer['groups']) ? 'kc-offer-cards'
            : match ($kcOffer['style']) { 'list' => 'kc-plain-list', 'cards' => 'kc-offer-cards', default => 'kc-chips' };
        $kcCardH = min(6, $kcHeadingLevel + 1);
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
                                @php $kcUrl = $kcItem['url'] ?? null; @endphp
                                @if ($kcListClass === 'kc-offer-cards')
                                    <li>
                                        @if (! empty($kcItem['image']))
                                            <img class="kc-offer-img" src="{{ $kcItem['image'] }}" alt="{{ $kcItem['alt'] ?? '' }}" loading="lazy">
                                        @endif
                                        <div class="kc-offer-body">
                                            <h{{ $kcCardH }} class="kc-offer-name">@if ($kcUrl)<a href="{{ $kcUrl }}">{{ $kcItem['title'] }}</a>@else{{ $kcItem['title'] }}@endif</h{{ $kcCardH }}>
                                            @if (($kcItem['price'] ?? '') !== '')<p class="kc-offer-price">{{ $kcItem['price'] }}</p>@endif
                                            @if ($kcItem['desc'] !== '')<p class="kc-offer-desc">{{ $kcItem['desc'] }}</p>@endif
                                            @if ($kcUrl)<span class="kc-offer-more" aria-hidden="true">Więcej o warsztacie →</span>@endif
                                        </div>
                                    </li>
                                @elseif ($kcListClass === 'kc-chips')
                                    <li>@if ($kcUrl)<a href="{{ $kcUrl }}">{{ $kcItem['title'] }}</a>@else{{ $kcItem['title'] }}@endif</li>
                                @else
                                    <li><span class="kc-offer-name">@if ($kcUrl)<a href="{{ $kcUrl }}">{{ $kcItem['title'] }}</a>@else{{ $kcItem['title'] }}@endif</span>@if ($kcItem['desc'] !== '') <span class="kc-offer-desc">{{ $kcItem['desc'] }}</span>@endif</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
