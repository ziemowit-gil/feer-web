{{--
    „Znajdź szkolenie dla siebie”: dwie grupy przycisków wyboru (temat i forma) prowadzące do katalogu /zajecia z gotowym
    filtrem. Pokazujemy tylko tematy i formy, w których są widoczne szkolenia (HomeController::portalExtras()), a grupę
    tylko wtedy, gdy ma co najmniej dwie opcje. Zwykły formularz GET z polami radio (fieldset i legend), więc działa bez JS
    i z klawiatury (strzałki w grupie, Enter wysyła). Zaznaczenie widać kolorem i znakiem ✓ (nie samym kolorem).
--}}
@php
    $cf = $courseFilters ?? ['count' => 0, 'categories' => [], 'formats' => []];
    $groups = array_filter([
        ['name' => 'kategoria', 'legend' => 'Temat', 'all' => 'Wszystkie', 'options' => $cf['categories'] ?? []],
        ['name' => 'format', 'legend' => 'Forma', 'all' => 'Każda', 'options' => $cf['formats'] ?? []],
    ], fn ($g) => count($g['options']) > 1);
@endphp
@if (($cf['count'] ?? 0) > 0 && $groups)
    @include('templates.feer.partials.is.styles')
    <section class="fis-find" aria-labelledby="fis-find-h">
        <div class="fis-wrap">
            <h2 id="fis-find-h">Znajdź szkolenie dla siebie</h2>
            <form method="GET" action="{{ route('courses.index') }}" class="fis-find-form">
                @foreach ($groups as $group)
                    <fieldset class="fis-chips">
                        <legend>{{ $group['legend'] }}</legend>
                        <div class="fis-chips-list">
                            <label class="fis-chip"><input type="radio" name="{{ $group['name'] }}" value="" checked><span>{{ $group['all'] }}</span></label>
                            @foreach ($group['options'] as $key => $label)
                                <label class="fis-chip"><input type="radio" name="{{ $group['name'] }}" value="{{ $key }}"><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
                <button type="submit" class="fis-btn">Pokaż szkolenia</button>
            </form>
        </div>
    </section>
@endif
