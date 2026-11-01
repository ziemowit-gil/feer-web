{{--
    „Znajdź szkolenie”: dwa filtry (temat i forma) prowadzące do katalogu /zajecia z gotowym filtrem. Pokazujemy tylko
    tematy i formy, w których są widoczne szkolenia (HomeController::portalExtras()). Zwykły formularz GET, działa bez JS.
--}}
@php $cf = $courseFilters ?? ['count' => 0, 'categories' => [], 'formats' => []]; @endphp
@if (($cf['count'] ?? 0) > 0)
    <section class="fis-find" aria-labelledby="fis-find-h">
        <div class="fis-wrap">
            <h2 id="fis-find-h">Znajdź szkolenie dla siebie</h2>
            <form method="GET" action="{{ route('courses.index') }}" class="fis-find-form">
                @if (count($cf['categories']) > 1)
                    <div>
                        <label for="fis-cat">Temat</label>
                        <select id="fis-cat" name="kategoria">
                            <option value="">Wszystkie tematy</option>
                            @foreach ($cf['categories'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                @endif
                @if (count($cf['formats']) > 1)
                    <div>
                        <label for="fis-fmt">Forma</label>
                        <select id="fis-fmt" name="format">
                            <option value="">Każda forma</option>
                            @foreach ($cf['formats'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="fis-btn">Pokaż szkolenia ({{ $cf['count'] }})</button>
            </form>
            <p>Wolisz przeglądać? <a class="fis-link" href="{{ route('courses.index') }}">Zobacz całą ofertę szkoleń i zajęć</a>.</p>
        </div>
    </section>
@endif
