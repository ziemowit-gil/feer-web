{{--
    Sekcja „Kontakt” strony głównej BU2: logo witryny, dane teleadresowe z ustawień (adres biura albo rejestrowy,
    telefony, e-mail) i duża pinezka „Mapa” prowadząca do OpenStreetMap (nowa karta, zapowiedziane w nazwie linku).
--}}
@php
    $hcLines = $siteSettings->hasOfficeAddress()
        ? [$siteSettings->contact_office_address, $siteSettings->contact_office_city, $siteSettings->contact_office_building]
        : [$siteSettings->contact_address, $siteSettings->contact_city];
    $hcLines = array_values(array_filter(array_map(fn ($l) => trim((string) $l), $hcLines), 'strlen'));
    $hcPhones = collect(preg_split('/\s*[;,\n]\s*/u', (string) $siteSettings->contact_phone) ?: [])->map(fn ($p) => trim($p))->filter()->values();
    $hcEmail = trim((string) $siteSettings->contact_email);
    $hcMap = class_exists(\Modules\Contact\Support\ContactOptions::class)
        ? \Modules\Contact\Support\ContactOptions::mapUrl(\Modules\Contact\Support\ContactOptions::visitAddress($siteSettings))
        : null;
    $hcLogo = $siteSettings->logoUrl();
@endphp
@if ($hcLines !== [] || $hcPhones->isNotEmpty() || $hcEmail !== '')
    <section class="bu2-sec" aria-labelledby="bu2-contact-h">
        <div class="bu2-wrap">
            <h2 id="bu2-contact-h" class="bu2-sec-title">Kontakt</h2>
            <div class="bu2-home-contact">
                <div>@if ($hcLogo)<img src="{{ $hcLogo }}" alt="">@endif</div>
                <address>
                    <strong>{{ $siteSettings->site_name }}</strong>
                    @foreach ($hcLines as $line){{ $line }}<br>@endforeach
                    @if ($hcPhones->isNotEmpty() || $hcEmail !== '')
                        <ul role="list">
                            @foreach ($hcPhones as $phone)
                                <li>
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"><span class="bu2-sr">Telefon: </span>{{ $phone }}</a>
                                </li>
                            @endforeach
                            @if ($hcEmail !== '')
                                <li class="bu2-mail">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1 2.2V17h16V7.2l-8 5.3zM5.4 7 12 11.4 18.6 7z"/></svg>
                                    <a href="mailto:{{ $hcEmail }}"><span class="bu2-sr">E-mail: </span>{{ $hcEmail }}</a>
                                </li>
                            @endif
                        </ul>
                    @endif
                </address>
                <div>
                    @if ($hcMap)
                        <a class="bu2-map" href="{{ $hcMap }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 32" aria-hidden="true" focusable="false"><path d="M12 0a12 12 0 0 0-12 12c0 9 12 20 12 20s12-11 12-20A12 12 0 0 0 12 0zm0 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
                            Mapa<span class="bu2-sr"> — dojazd w OpenStreetMap (otwiera się w nowej karcie)</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
