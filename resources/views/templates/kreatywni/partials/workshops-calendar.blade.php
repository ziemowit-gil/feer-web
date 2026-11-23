{{--
    Strona typu „Kalendarz warsztatów” (App\PageTypes\Types\KreatywniWorkshops) w szablonie „kreatywni”: wstęp (type_data.lead)
    i treść strony, potem terminy z modułu „Warsztaty – Kreatywni” w wybranym widoku (type_data.view):
      - both (domyślnie): siatka miesiąca (partials/workshops-month) i pod nią lista nadchodzących w miesiącach;
      - calendar: siatka miesiąca i karty terminów tego miesiąca;
      - list: sama lista nadchodzących w miesiącach.
    Miesiąc siatki: ?miesiac=RRRR-MM, a bez niego miesiąc najbliższego terminu (albo bieżący). Karta: partials/workshop-card.
    Opcjonalnie (type_data.show_past) 12 minionych. Bez tabeli modułu albo bez terminów — zaproszenie do kontaktu.
    Zmienne: $page, $siteSettings.
--}}
@php
    $kcTd = $page->typeData();
    $kcView = in_array($kcTd['view'] ?? null, ['both', 'calendar', 'list'], true) ? $kcTd['view'] : 'both';
    $kcTz = \App\Models\KreatywniWorkshop::TZ;
    $kcSupported = \App\Models\KreatywniWorkshop::supported();
    $kcActive = fn () => \App\Models\KreatywniWorkshop::forCurrentSite()->where('is_active', true);
    $kcUpcoming = $kcSupported ? $kcActive()->upcoming()->orderBy('starts_at')->with('media')->get() : collect();
    $kcPast = $kcSupported && ! empty($kcTd['show_past']) ? $kcActive()->past()->orderByDesc('starts_at')->limit(12)->get() : collect();
    $kcContact = $siteSettings->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
    $kcLeadText = trim((string) ($kcTd['lead'] ?? ''));
    $kcPageUrl = $page->publicUrl();

    // Miesiąc siatki: z adresu (?miesiac=2026-12) albo miesiąc najbliższego terminu, a bez terminów — bieżący.
    $kcGridMonth = null;
    $kcGridItems = collect();
    if ($kcView !== 'list') {
        $kcParam = (string) request()->query('miesiac', '');
        if (preg_match('/^(\d{4})-(\d{2})$/', $kcParam, $kcM) && (int) $kcM[1] >= 2000 && (int) $kcM[1] <= 2100 && (int) $kcM[2] >= 1 && (int) $kcM[2] <= 12) {
            $kcGridMonth = \Illuminate\Support\Carbon::create((int) $kcM[1], (int) $kcM[2], 1, 0, 0, 0, $kcTz);
        } else {
            $kcGridMonth = ($kcUpcoming->first()?->startsLocal() ?? now()->timezone($kcTz))->copy()->startOfMonth();
        }
        $kcGridItems = $kcSupported
            ? $kcActive()->whereBetween('starts_at', [$kcGridMonth->copy()->utc(), $kcGridMonth->copy()->endOfMonth()->utc()])->orderBy('starts_at')->with('media')->get()
            : collect();
    }
    $kcCards = $kcView === 'calendar' ? $kcGridItems : $kcUpcoming;
    $kcAnchors = $kcCards->pluck('id')->all();
    $kcMonths = $kcUpcoming->groupBy(fn ($w) => $w->startsLocal()->format('Y-m'));
@endphp
@include('templates.kreatywni.partials.workshops-css')
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

        @if ($kcView === 'both' && $kcUpcoming->isNotEmpty())
            <ul class="kc-ws-jump" aria-label="Na tej stronie">
                <li><a href="#kalendarz"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i>Kalendarz miesiąca</a></li>
                <li><a href="#lista-terminow"><i class="fa-solid fa-list" aria-hidden="true"></i>Lista terminów</a></li>
            </ul>
        @endif

        @if ($kcGridMonth)
            @include('templates.kreatywni.partials.workshops-month')
        @endif

        @if ($kcView === 'calendar')
            @if ($kcGridItems->isNotEmpty())
                <ul class="kc-ws-list" style="margin-top:2rem">
                    @foreach ($kcGridItems as $kcW)
                        @include('templates.kreatywni.partials.workshop-card', ['kcH' => 3])
                    @endforeach
                </ul>
            @endif
        @else
            @php
                // W widoku „kalendarz + lista” lista ma własny nagłówek H2, miesiące H3, karty H4; w samej liście miesiące H2.
                $kcNested = $kcView === 'both';
                $kcMonthH = $kcNested ? 3 : 2;
            @endphp
            <div @if ($kcNested) id="lista-terminow" class="kc-ws-list-sec" @endif>
                @if ($kcNested && $kcUpcoming->isNotEmpty())
                    <h2 class="kc-ws-month">Najbliższe terminy</h2>
                @endif
                @forelse ($kcMonths as $kcMonth => $kcItems)
                    <section class="kc-ws-month-sec" aria-labelledby="kc-ws-{{ $kcMonth }}">
                        <h{{ $kcMonthH }} id="kc-ws-{{ $kcMonth }}" class="kc-ws-month">{{ \Illuminate\Support\Str::ucfirst($kcItems->first()->startsLocal()->translatedFormat('F Y')) }}</h{{ $kcMonthH }}>
                        <ul class="kc-ws-list">
                            @foreach ($kcItems as $kcW)
                                @include('templates.kreatywni.partials.workshop-card', ['kcH' => $kcMonthH + 1])
                            @endforeach
                        </ul>
                    </section>
                @empty
                    <div class="kc-ws-empty" @if ($kcGridMonth) style="margin-top:2rem" @endif>
                        <p><strong>Nowe terminy pojawią się wkrótce.</strong> Organizujemy też warsztaty na zamówienie — w szkołach, przedszkolach, domach kultury i na festynach.</p>
                        @if ($kcContact)
                            <a href="{{ $kcContact }}" class="kc-btn">Zapytaj o termin <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        @endif
                    </div>
                @endforelse
            </div>
        @endif

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
