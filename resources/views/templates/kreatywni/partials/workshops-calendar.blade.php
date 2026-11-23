{{--
    Strona typu „Kalendarz warsztatów” (App\PageTypes\Types\KreatywniWorkshops) w szablonie „kreatywni”: wstęp (type_data.lead)
    i treść strony, potem nadchodzące terminy z modułu „Warsztaty – Kreatywni” pogrupowane w miesiące (H2) — karta: grafika
    (opis z panelu albo z biblioteki, bez niego dekoracyjna), kafelek daty z pełną datą w <time>, nazwa (H3), termin
    z godzinami, miejsce, liczba uczestników, opis i „Zapytaj o miejsce” (strona kontaktu). Na telefonie jedna kolumna.
    Opcjonalnie (type_data.show_past) lista 12 minionych. Bez tabeli modułu albo bez terminów — zaproszenie do kontaktu.
    Zmienne: $page, $siteSettings.
--}}
@php
    $kcTd = $page->typeData();
    $kcSupported = \App\Models\KreatywniWorkshop::supported();
    $kcUpcoming = $kcSupported
        ? \App\Models\KreatywniWorkshop::forCurrentSite()->where('is_active', true)->upcoming()->orderBy('starts_at')->with('media')->get()
        : collect();
    $kcPast = $kcSupported && ! empty($kcTd['show_past'])
        ? \App\Models\KreatywniWorkshop::forCurrentSite()->where('is_active', true)->past()->orderByDesc('starts_at')->limit(12)->get()
        : collect();
    $kcMonths = $kcUpcoming->groupBy(fn ($w) => $w->startsLocal()->format('Y-m'));
    $kcContact = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcLeadText = trim((string) ($kcTd['lead'] ?? ''));
@endphp
@once
    <style>
        .kc-ws-intro { max-width: 52rem; margin-bottom: 2.5rem; }
        .kc-ws-month { margin: 0 0 1.25rem; padding-bottom: .45rem; border-bottom: 3px solid var(--kc-lime); font-size: clamp(1.35rem, 1.1rem + .8vw, 1.75rem); font-weight: 800; color: var(--kc-forest); }
        .kc-ws-month-sec + .kc-ws-month-sec { margin-top: 3rem; }
        .kc-ws-list { display: grid; gap: 1.5rem; margin: 0; padding: 0; list-style: none; }
        @media (min-width: 900px) { .kc-ws-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .kc-ws-card { position: relative; display: flex; flex-direction: column; overflow: hidden; border-radius: var(--kc-radius); background: #fff; }
        .kc-ws-photo { aspect-ratio: 2 / 1; background: var(--kc-mint); }
        .kc-ws-photo img { width: 100%; height: 100%; object-fit: cover; }
        .kc-ws-date { position: absolute; top: 1rem; left: 1rem; display: flex; flex-direction: column; align-items: center; min-width: 4.25rem;
            padding: .6rem .65rem .5rem; border-radius: 1rem; background: var(--kc-sun); color: var(--kc-forest); line-height: 1; }
        .kc-ws-card.no-photo .kc-ws-date { position: static; align-self: flex-start; margin: 1.35rem 1.5rem 0; }
        .kc-ws-day { font-size: 1.9rem; font-weight: 800; }
        .kc-ws-mon { margin-top: .2rem; font-size: .9rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .kc-ws-body { display: flex; flex: 1; flex-direction: column; gap: .6rem; padding: 1.35rem 1.5rem 1.5rem; }
        .kc-ws-body h3 { margin: 0; font-size: 1.3rem; font-weight: 800; line-height: 1.3; color: var(--kc-forest); }
        .kc-ws-meta { display: grid; gap: .35rem; margin: 0; padding: 0; list-style: none; font-weight: 600; }
        .kc-ws-meta li { display: flex; gap: .6rem; align-items: flex-start; line-height: 1.45; }
        .kc-ws-meta i { flex: none; width: 1.1rem; margin-top: .2rem; color: var(--kc-green); text-align: center; }
        .kc-ws-desc p { margin: 0 0 .6rem; line-height: 1.65; }
        .kc-ws-desc p:last-child { margin-bottom: 0; }
        .kc-ws-cta { margin-top: auto; padding-top: .5rem; }
        .kc-ws-empty { max-width: 44rem; padding: 2rem; border-radius: var(--kc-radius); background: #fff; }
        .kc-ws-empty p { margin: 0 0 1rem; line-height: 1.6; }
        .kc-ws-past { margin-top: 4rem; }
        .kc-ws-past ul { margin: 0; padding: 0; list-style: none; }
        .kc-ws-past li { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; padding: .75rem 0; border-bottom: 1px solid var(--kc-line); line-height: 1.5; }
        .kc-ws-past time { min-width: 9rem; font-weight: 700; color: var(--kc-forest); }
        .kc-ws-past-place { color: var(--kc-muted); }
        @media (forced-colors: active) { .kc-ws-date { border: 1px solid CanvasText; } }
    </style>
@endonce
<section class="kc-sec kc-sec-cream" aria-label="Kalendarz warsztatów">
    <div class="kc-wrap">
        @if ($kcLeadText !== '' || filled($page->content))
            <div class="kc-ws-intro">
                @if ($kcLeadText !== '')<p class="kc-lead">{{ $kcLeadText }}</p>@endif
                @if (filled($page->content))
                    <div class="kc-prose prose max-w-none">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
                @endif
            </div>
        @endif

        @forelse ($kcMonths as $kcMonth => $kcItems)
            @php $kcMonthDate = $kcItems->first()->startsLocal(); @endphp
            <section class="kc-ws-month-sec" aria-labelledby="kc-ws-{{ $kcMonth }}">
                <h2 id="kc-ws-{{ $kcMonth }}" class="kc-ws-month">{{ \Illuminate\Support\Str::ucfirst($kcMonthDate->translatedFormat('F Y')) }}</h2>
                <ul class="kc-ws-list">
                    @foreach ($kcItems as $kcW)
                        @php
                            $kcStart = $kcW->startsLocal();
                            $kcEnd = $kcW->endsLocal();
                            $kcImg = $kcW->imageUrl();
                        @endphp
                        <li @class(['kc-ws-card', 'no-photo' => ! $kcImg])>
                            @if ($kcImg)
                                <div class="kc-ws-photo"><img src="{{ $kcImg }}" alt="{{ $kcW->imageAltText() }}" loading="lazy"></div>
                            @endif
                            <time class="kc-ws-date" datetime="{{ $kcW->starts_at->toIso8601String() }}">
                                <span class="kc-ws-day" aria-hidden="true">{{ $kcStart->format('j') }}</span>
                                <span class="kc-ws-mon" aria-hidden="true">{{ $kcStart->translatedFormat('M') }}</span>
                                <span class="kc-sr">{{ $kcStart->translatedFormat('j F Y') }}</span>
                            </time>
                            <div class="kc-ws-body">
                                <h3>{{ $kcW->title }}</h3>
                                <ul class="kc-ws-meta">
                                    <li><i class="fa-solid fa-clock" aria-hidden="true"></i><span><span class="kc-sr">Termin: </span>{{ $kcW->whenLabel() }}</span></li>
                                    @if ($kcW->place)
                                        <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><span class="kc-sr">Miejsce: </span>{{ $kcW->place }}</span></li>
                                    @endif
                                    @if ($kcW->participants)
                                        <li><i class="fa-solid fa-user-group" aria-hidden="true"></i><span><span class="kc-sr">Liczba uczestników: </span>{{ $kcW->participants }}</span></li>
                                    @endif
                                </ul>
                                @if ($kcW->descriptionParagraphs() !== [])
                                    <div class="kc-ws-desc">
                                        @foreach ($kcW->descriptionParagraphs() as $kcP)
                                            <p>{{ $kcP }}</p>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($kcContact)
                                    <div class="kc-ws-cta">
                                        <a href="{{ $kcContact }}" class="kc-btn">Zapytaj o miejsce<span class="kc-sr"> — {{ $kcW->title }}, {{ $kcStart->translatedFormat('j F Y') }}</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="kc-ws-empty">
                <p><strong>Nowe terminy pojawią się wkrótce.</strong> Organizujemy też warsztaty na zamówienie — w szkołach, przedszkolach, domach kultury i na festynach.</p>
                @if ($kcContact)
                    <a href="{{ $kcContact }}" class="kc-btn">Zapytaj o termin <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
        @endforelse

        @if ($kcPast->isNotEmpty())
            <section class="kc-ws-past" aria-labelledby="kc-ws-past">
                <h2 id="kc-ws-past" class="kc-ws-month">Minione warsztaty</h2>
                <ul>
                    @foreach ($kcPast as $kcW)
                        <li>
                            <time datetime="{{ $kcW->starts_at->toIso8601String() }}">{{ $kcW->startsLocal()->translatedFormat('j F Y') }}</time>
                            <span><strong>{{ $kcW->title }}</strong>@if ($kcW->place) <span class="kc-ws-past-place">· {{ $kcW->place }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</section>
