{{--
    Karta terminu warsztatu (moduł „Warsztaty – Kreatywni”): grafika (opis z panelu albo z biblioteki, bez niego
    dekoracyjna), kafelek daty z pełną datą w <time>, nazwa, termin z godzinami, miejsce, liczba uczestników, opis
    i „Zapytaj o miejsce”. Element <li> z id „warsztat-{id}” (cel linków z siatki miesiąca i strony głównej).
    Zmienne: $kcW (KreatywniWorkshop), $kcContact (adres kontaktu albo null), opcjonalnie $kcH (poziom nagłówka nazwy,
    domyślnie 3), $kcCompact (true = bez opisu i przycisku — strona główna), $kcLink (link w nazwie, np. do kalendarza).
--}}
@php
    $kcH = $kcH ?? 3;
    $kcCompact = $kcCompact ?? false;
    $kcLink = $kcLink ?? null;
    $kcStart = $kcW->startsLocal();
    $kcImg = $kcW->imageUrl();
@endphp
<li @class(['kc-ws-card', 'no-photo' => ! $kcImg]) @unless ($kcCompact) id="warsztat-{{ $kcW->id }}" @endunless>
    @if ($kcImg)
        <div class="kc-ws-photo"><img src="{{ $kcImg }}" alt="{{ $kcW->imageAltText() }}" loading="lazy"></div>
    @endif
    <time class="kc-ws-date" datetime="{{ $kcW->starts_at->toIso8601String() }}">
        <span class="kc-ws-day" aria-hidden="true">{{ $kcStart->format('j') }}</span>
        <span class="kc-ws-mon" aria-hidden="true">{{ $kcStart->translatedFormat('M') }}</span>
        <span class="kc-sr">{{ $kcStart->translatedFormat('j F Y') }}</span>
    </time>
    <div class="kc-ws-body">
        <h{{ $kcH }} class="kc-ws-name">@if ($kcLink)<a href="{{ $kcLink }}">{{ $kcW->title }}</a>@else{{ $kcW->title }}@endif</h{{ $kcH }}>
        <ul class="kc-ws-meta">
            <li><i class="fa-solid fa-clock" aria-hidden="true"></i><span><span class="kc-sr">Termin: </span>{{ $kcW->whenLabel() }}</span></li>
            @if ($kcW->place)
                <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><span class="kc-sr">Miejsce: </span>{{ $kcW->place }}</span></li>
            @endif
            @if ($kcW->participants)
                <li><i class="fa-solid fa-user-group" aria-hidden="true"></i><span><span class="kc-sr">Liczba uczestników: </span>{{ $kcW->participants }}</span></li>
            @endif
        </ul>
        @if (! $kcCompact && $kcW->descriptionParagraphs() !== [])
            <div class="kc-ws-desc">
                @foreach ($kcW->descriptionParagraphs() as $kcP)
                    <p>{{ $kcP }}</p>
                @endforeach
            </div>
        @endif
        @if (! $kcCompact && $kcContact && ! $kcW->isPast())
            <div class="kc-ws-cta">
                <a href="{{ $kcContact }}" class="kc-btn">Zapytaj o miejsce<span class="kc-sr"> — {{ $kcW->title }}, {{ $kcStart->translatedFormat('j F Y') }}</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        @endif
    </div>
</li>
