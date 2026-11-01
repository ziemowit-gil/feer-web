{{--
    „Jak się zapisać”: trzy kroki zgodne z tym, jak działa zapis w serwisie. Gdy zapisy online są włączone: wybór szkolenia,
    zapis na termin (z opisem potrzebnych dostosowań), potwierdzenie przez koordynatora (lub lista rezerwowa). Gdy wyłączone
    (domyślnie w IS): wybór szkolenia, kontakt, ustalenie terminu. Blok jest tylko przy włączonym module szkoleń.
--}}
@if ($siteSettings->isModuleEnabled('courses') && (($courseFilters['count'] ?? 0) > 0))
    @php
        $online = $siteSettings->coursesRegistrationEnabled();
        $catalogHref = ($trainingCatalogUrl ?? null) ?: route('courses.index');
        $steps = $online
            ? [
                ['Wybierz szkolenie', 'Przejrzyj ofertę i skorzystaj z filtrów tematu oraz formy. Przy każdym szkoleniu są program, cena i terminy.'],
                ['Zapisz się na termin', 'Wypełnij krótki formularz. Napisz, jakich dostosowań potrzebujesz, na przykład czytnika ekranu, większej czcionki albo asystenta.'],
                ['Poczekaj na potwierdzenie', 'Koordynator potwierdzi zgłoszenie. Gdy w terminie zabraknie miejsc, trafisz na listę rezerwową i damy znać, gdy się zwolni.'],
            ]
            : [
                ['Wybierz szkolenie', 'Przejrzyj ofertę i skorzystaj z filtrów tematu oraz formy. Przy każdym szkoleniu są program, cena i terminy.'],
                ['Skontaktuj się z nami', 'Zadzwoń albo napisz. Zapisy prowadzimy bez formularza online, więc wszystko ustalisz z nami bezpośrednio.'],
                ['Ustalimy termin', 'Zapiszemy Cię na wybrany termin i dopytamy, jakich dostosowań potrzebujesz, żeby zajęcia były dla Ciebie dostępne.'],
            ];
    @endphp
    <section class="fis-sec fis-sec--tint" aria-labelledby="fis-steps-h">
        <div class="fis-wrap">
            <h2 id="fis-steps-h">Jak się zapisać</h2>
            <ol class="fis-steps" role="list">
                @foreach ($steps as [$title, $text])
                    <li><h3>{{ $title }}</h3><p>{{ $text }}</p></li>
                @endforeach
            </ol>
            <div class="fis-actions">
                <a class="fis-btn" href="{{ $catalogHref }}">Zobacz szkolenia</a>
                <a class="fis-btn fis-btn--light" href="{{ route('contact.show') }}">Napisz do nas</a>
            </div>
        </div>
    </section>
@endif
