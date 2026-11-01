{{--
    Pas liczb na zdjęciu (jako tło pierwsze zdjęcie slidera, pod ciemną przesłoną dla czytelności tekstu): opis (hasło witryny),
    do czterech liczb i przycisk do strony „O nas”. Liczby z pola „statystyki” strony typu „O organizacji” albo z ustawień
    szablonu NGO 3; domyślnych, przykładowych liczb nie pokazujemy, więc bez danych sekcji nie ma.
    Dane: $stats (wiersze value/label), $statsBg, $statsText, $aboutUrl.
--}}
@php $rows = collect($stats ?? [])->filter(fn ($s) => filled($s['value'] ?? null))->take(4)->values(); @endphp
@include('templates.test3.partials.home.css')
@if ($rows->isNotEmpty())
<section class="z3 z3-stats" aria-labelledby="z3-stats-h" @if (! empty($statsBg)) style="background-image: url('{{ $statsBg }}')" @endif>
    <div class="z3-stats-in">
        <h2 id="z3-stats-h" class="sr-only">W liczbach</h2>
        @if (filled($statsText ?? null))<p>{{ $statsText }}</p>@endif
        <dl class="z3-stats-grid">
            @foreach ($rows as $row)
                <div class="z3-stat"><dt>{{ $row['label'] ?? '' }}</dt><dd>{{ $row['value'] }}</dd></div>
            @endforeach
        </dl>
        @if (! empty($aboutUrl))<a class="z3-btn" href="{{ $aboutUrl }}">Zobacz więcej</a>@endif
    </div>
</section>
@endif
