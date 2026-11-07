{{--
    Moduł Kontakt: dojazd, dostępność biura, link do mapy (OpenStreetMap, bez osadzania i śledzenia) i „Zapisz kontakt
    w telefonie” (vCard organizacji). Wszystko włączane w Komunikacja → Kontakt; bez danych blok się nie pokazuje.
    Etykiety jak przy pozostałych danych kontaktowych; cele dotyku ≥ 44 px; linki zewnętrzne zapowiadają nową kartę.
--}}
@php
    $vo = $contactOptions ?? \Modules\Contact\Support\ContactOptions::for($siteSettings);
    $voMap = $vo->mapLink() ? \Modules\Contact\Support\ContactOptions::mapUrl(\Modules\Contact\Support\ContactOptions::visitAddress($siteSettings)) : null;
    $voLabel = 'text-xs font-bold uppercase tracking-widest text-muted';
    $voLink = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($vo->directions() !== '' || $vo->accessibility() !== '' || $voMap || $vo->vcard())
    <div class="contact-visit {{ $class ?? 'mt-6' }} space-y-4 text-ink">
        @if ($vo->directions() !== '')
            <div>
                <p class="{{ $voLabel }}">Jak dojechać</p>
                <p class="mt-1 leading-relaxed">{!! nl2br(e($vo->directions())) !!}</p>
            </div>
        @endif
        @if ($vo->accessibility() !== '')
            <div>
                <p class="{{ $voLabel }}">Dostępność biura</p>
                <p class="mt-1 leading-relaxed">{!! nl2br(e($vo->accessibility())) !!}</p>
            </div>
        @endif
        @if ($voMap || $vo->vcard())
            <p class="flex flex-wrap gap-x-6">
                @if ($voMap)
                    <a href="{{ $voMap }}" target="_blank" rel="noopener" class="{{ $voLink }}"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>Pokaż na mapie<span class="sr-only"> (OpenStreetMap, otwiera się w nowej karcie)</span></a>
                @endif
                @if ($vo->vcard())
                    <a href="{{ url('/kontakt/kontakt.vcf') }}" download class="{{ $voLink }}"><i class="fa-solid fa-address-card" aria-hidden="true"></i>Zapisz kontakt w telefonie<span class="sr-only"> (plik wizytówki .vcf)</span></a>
                @endif
            </p>
        @endif
    </div>
@endif
