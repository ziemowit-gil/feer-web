{{--
    Siatka miesiąca strony „Kalendarz warsztatów” (szablon „kreatywni”): tabela pon.–niedz. z nagłówkami dni (skrót
    widoczny, pełna nazwa dla czytników), dzisiejszy dzień oznaczony aria-current="date", terminy w kratkach (godzina +
    nazwa; na telefonie sama godzina, nazwa dla czytników) jako linki do kart na tej stronie (#warsztat-{id}), gdy karta
    jest wyświetlona. Przejścia „poprzedni / następny miesiąc” przez ?miesiac=RRRR-MM (bez JavaScriptu), z kotwicą #kalendarz.
    Zmienne: $kcGridMonth (pierwszy dzień miesiąca, czas polski), $kcGridItems (terminy tego miesiąca), $kcAnchors (id kart
    wyświetlonych na stronie), $kcPageUrl (adres strony).
--}}
@php
    $kcTz = \App\Models\KreatywniWorkshop::TZ;
    $kcToday = now()->timezone($kcTz)->toDateString();
    $kcFrom = $kcGridMonth->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $kcTo = $kcGridMonth->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $kcByDay = $kcGridItems->groupBy(fn ($w) => $w->startsLocal()->toDateString());
    $kcPrev = $kcGridMonth->copy()->subMonthNoOverflow();
    $kcNext = $kcGridMonth->copy()->addMonthNoOverflow();
    $kcMonthLabel = fn ($d) => \Illuminate\Support\Str::ucfirst($d->translatedFormat('F Y'));
    $kcDays = [['Pn', 'poniedziałek'], ['Wt', 'wtorek'], ['Śr', 'środa'], ['Cz', 'czwartek'], ['Pt', 'piątek'], ['Sb', 'sobota'], ['Nd', 'niedziela']];
@endphp
<section id="kalendarz" class="kc-cal-sec" aria-labelledby="kc-cal-title">
    <div class="kc-cal-nav">
        <a href="{{ $kcPageUrl }}?miesiac={{ $kcPrev->format('Y-m') }}#kalendarz" rel="prev"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ $kcMonthLabel($kcPrev) }}<span class="kc-sr"> — poprzedni miesiąc</span></a>
        <h2 id="kc-cal-title">{{ $kcMonthLabel($kcGridMonth) }}</h2>
        <a href="{{ $kcPageUrl }}?miesiac={{ $kcNext->format('Y-m') }}#kalendarz" rel="next">{{ $kcMonthLabel($kcNext) }}<span class="kc-sr"> — następny miesiąc</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <table class="kc-cal" aria-labelledby="kc-cal-title">
        <thead>
            <tr>
                @foreach ($kcDays as [$kcShort, $kcFull])
                    <th scope="col"><span aria-hidden="true">{{ $kcShort }}</span><span class="kc-sr">{{ $kcFull }}</span></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for ($kcDay = $kcFrom->copy(); $kcDay->lte($kcTo); $kcDay->addDay())
                @if ($kcDay->dayOfWeekIso === 1)<tr>@endif
                @php
                    $kcKey = $kcDay->toDateString();
                    $kcOut = $kcDay->month !== $kcGridMonth->month;
                    $kcEvents = $kcOut ? collect() : ($kcByDay[$kcKey] ?? collect());
                    $kcIsToday = $kcKey === $kcToday;
                    $kcTdClass = trim(($kcOut ? 'is-out ' : '').($kcIsToday ? 'is-today' : ''));
                @endphp
                <td{!! $kcTdClass !== '' ? ' class="'.$kcTdClass.'"' : '' !!}{!! $kcIsToday ? ' aria-current="date"' : '' !!}>
                    <span class="kc-cal-day"><span aria-hidden="true">{{ $kcDay->day }}</span><span class="kc-sr">{{ $kcDay->translatedFormat('j F') }}{{ $kcIsToday ? ', dziś' : '' }}</span></span>
                    @if ($kcEvents->isNotEmpty())
                        <ul>
                            @foreach ($kcEvents as $kcEv)
                                @php $kcPastEv = $kcEv->isPast(); @endphp
                                <li>
                                    @if (in_array($kcEv->id, $kcAnchors, true))
                                        <a href="#warsztat-{{ $kcEv->id }}" @class(['kc-cal-ev', 'is-past' => $kcPastEv])><span class="kc-cal-time">{{ $kcEv->startsLocal()->format('H:i') }}</span><span class="kc-cal-name">{{ $kcEv->title }}</span></a>
                                    @else
                                        <span @class(['kc-cal-ev', 'is-past' => $kcPastEv])><span class="kc-cal-time">{{ $kcEv->startsLocal()->format('H:i') }}</span><span class="kc-cal-name">{{ $kcEv->title }}@if ($kcPastEv)<span class="kc-sr"> (odbył się)</span>@endif</span></span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                @if ($kcDay->dayOfWeekIso === 7)</tr>@endif
            @endfor
        </tbody>
    </table>
    @if ($kcGridItems->isEmpty())
        <p class="kc-cal-none">W tym miesiącu nie ma zaplanowanych warsztatów.</p>
    @endif
</section>
