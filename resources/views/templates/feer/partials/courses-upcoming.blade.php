{{--
    „Najbliższe szkolenia” w wariancie FEER IS: nadchodzące terminy z oferty szkoleń (moduł courses), po jednym na szkolenie.
    Karta: kafelek z dniem i miesiącem startu, temat, tytuł-link (cała karta jest klikalna), zakres dat, forma · czas · cena
    oraz wolne miejsca. Liczba kolumn zależy od liczby kart (4 karty to 2×2, 5–6 to 3 kolumny), żeby nie zostawała
    osierocona karta w ostatnim rzędzie. Link prowadzi do zapisu na ten termin (przy wyłączonych zapisach online do kontaktu).
    Zmienna $courseEditions: kolekcja CourseEdition z załadowanym course. Style: partials/is/styles (prefiks fis-).
--}}
@php
    $courseEditions ??= collect();
    $count = $courseEditions->count();
    $cols = $count >= 5 ? 3 : ($count === 4 ? 2 : max(1, $count));
@endphp
@if ($count > 0)
    @include('templates.feer.partials.is.styles')
    <section class="fis-sec" aria-labelledby="feer-is-courses-heading">
        <div class="fis-wrap">
            <div class="fis-head">
                <h2 id="feer-is-courses-heading">Najbliższe szkolenia</h2>
                <a class="fis-link" href="{{ route('courses.index') }}">Wszystkie szkolenia i zapisy<span aria-hidden="true"> →</span></a>
            </div>
            <ul class="fis-up-grid" data-cols="{{ $cols }}" role="list">
                @foreach ($courseEditions as $edition)
                    @php
                        $course = $edition->course;
                        $start = $edition->starts_on->copy()->locale('pl');
                        $meta = collect([$course->formatLabel(), $course->duration, $course->priceLabel()])->filter()->implode(' · ');
                    @endphp
                    <li class="fis-up-card">
                        <span class="fis-date" aria-hidden="true">
                            <b>{{ $start->format('j') }}</b>
                            <span>{{ $start->isoFormat('MMM') }}</span>
                            @if ($start->year !== now()->year)<small>{{ $start->year }}</small>@endif
                        </span>
                        <div class="fis-up-body">
                            <p class="fis-up-kicker">{{ $course->categoryLabel() }}</p>
                            <h3><a href="{{ route('courses.show', [$course, 'termin' => $edition->id]) }}#zapis">{{ $course->title }}</a></h3>
                            <p class="fis-up-when"><time datetime="{{ $edition->starts_on->toDateString() }}">{{ $edition->datesLabel() }}</time></p>
                            <p>{{ $meta }}</p>
                            <p class="fis-up-seats">{{ $edition->seatsLabel() }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
