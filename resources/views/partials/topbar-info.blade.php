{{--
    Pasek informacyjny: data, imieniny, pogoda + narzędzia dostępności.
    Wspólny dla szablonu „Gmina / urząd" i substylu „Urzędowy" nagłówka FEER.
    Poszczególne elementy wyłącza się w ustawieniach (zakładka Nagłówek).

    Ułatwienia dostępu zwinięte do jednego przełącznika (wzorzec z szablonu "federation") —
    te same data-a11y-* co dotąd, ta sama logika w resources/js/app.js, tylko domyślnie
    schowane pod jednym przyciskiem. Stan zapamiętywany w localStorage.
--}}
@php
    use App\Support\PolishNameDays;
    $nameDays = $siteSettings->infobar_show_nameday ? PolishNameDays::format() : null;

    $weekdays = ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota'];
    $months   = ['', 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca',
                 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    $now = now();
    $dateStr = $weekdays[$now->dayOfWeek] . ', ' . $now->day . ' ' . $months[$now->month] . ' ' . $now->year;
@endphp

<div x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()">

    <div class="bg-brand text-white text-xs">
        <div class="mx-auto max-w-[1400px] flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-1.5">

            {{-- Data --}}
            @if ($siteSettings->infobar_show_date)
                <div class="flex shrink-0 items-center gap-2 border-r border-white/30 pr-4">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <time datetime="{{ $now->toDateString() }}">{{ $dateStr }}</time>
                </div>
            @endif

            {{-- Imieniny --}}
            @if ($siteSettings->infobar_show_nameday && $nameDays)
                <div class="flex shrink-0 items-center gap-2 border-r border-white/30 pr-4">
                    <span class="text-white/70">Imieniny:</span>
                    <span>{{ $nameDays }}</span>
                </div>
            @endif

            {{-- Pogoda (pobierana przez JS z Open-Meteo jeśli skonfigurowane) --}}
            @if ($siteSettings->municipality_weather_lat && $siteSettings->municipality_weather_lon)
                <div id="mun-weather"
                     class="flex shrink-0 items-center gap-2 border-r border-white/30 pr-4"
                     data-lat="{{ $siteSettings->municipality_weather_lat }}"
                     data-lon="{{ $siteSettings->municipality_weather_lon }}"
                     aria-live="polite"
                     aria-label="Aktualna pogoda">
                    <i class="bi bi-cloud text-white/60" aria-hidden="true"></i>
                    <span class="text-white/60">Ładowanie pogody…</span>
                </div>
            @endif

            {{-- Prawa strona: przełącznik ułatwień dostępu --}}
            <div class="ml-auto flex shrink-0 items-center">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="a11y-panel"
                    class="flex h-11 w-11 items-center justify-center rounded-full text-white/90 transition hover:bg-white/20 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand"
                    :class="open ? 'bg-white/25 text-white' : ''"
                    aria-label="Ułatwienia dostępu">
                    <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności"
        class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
        <div class="mx-auto flex max-w-[1400px] flex-wrap items-center gap-4 px-4 py-3">
            <div class="flex items-center gap-1.5" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="up"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Zwiększ czcionkę">A+</button>
                <button type="button" data-a11y-font="up" data-a11y-font-step="2"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Znacznie zwiększ czcionkę">A++</button>
                <button type="button" data-a11y-font="reset"
                    class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-label="Domyślny rozmiar czcionki">A</button>
            </div>

            <button type="button" data-a11y-lh
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Zwiększ odstęp między wierszami">
                <i class="bi bi-list" aria-hidden="true"></i> Odstęp wierszy
            </button>

            <button type="button" data-a11y-ls
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Rozstrzał liter">
                <i class="fa-solid fa-text-width" aria-hidden="true"></i> Odstęp liter
            </button>

            <button type="button" data-a11y-contrast="contrast"
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Kontrast">
                <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> Kontrast
            </button>

            <button type="button" data-a11y-reset class="flex min-h-6 items-center gap-1 text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-label="Przywróć domyślne ustawienia dostępności">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Resetuj
            </button>

            @if ($siteSettings->municipality_show_google_translate ?? false)
                <button type="button" id="mun-translate-btn"
                    class="flex min-h-6 items-center gap-1 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-haspopup="true"
                    aria-expanded="false" aria-controls="google_translate_element"
                    aria-label="Tłumacz stronę (Google Translate)">
                    <i class="bi bi-translate" aria-hidden="true"></i> Translate
                </button>
                <div id="google_translate_element" class="hidden"></div>
            @endif
        </div>
    </div>
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>

@if ($siteSettings->municipality_weather_lat && $siteSettings->municipality_weather_lon)
<script>
(function () {
    var WMO = {
        0: ['Bezchmurnie', 'bi bi-sun'],
        1: ['Przeważnie pogodnie', 'bi bi-sun'],
        2: ['Częściowe zachmurzenie', 'bi bi-cloud-sun'],
        3: ['Pochmurno', 'bi bi-cloud'],
        45: ['Mgła', 'bi bi-cloud-haze'],
        48: ['Mgła z szronem', 'bi bi-cloud-haze'],
        51: ['Mżawka', 'bi bi-cloud-drizzle'],
        53: ['Mżawka', 'bi bi-cloud-drizzle'],
        55: ['Silna mżawka', 'bi bi-cloud-drizzle'],
        61: ['Deszcz', 'bi bi-cloud-rain'],
        63: ['Umiarkowany deszcz', 'bi bi-cloud-rain'],
        65: ['Silny deszcz', 'bi bi-cloud-rain-heavy'],
        71: ['Śnieg', 'bi bi-cloud-snow'],
        73: ['Umiarkowany śnieg', 'bi bi-cloud-snow'],
        75: ['Intensywny śnieg', 'bi bi-cloud-snow'],
        80: ['Przelotne opady', 'bi bi-cloud-rain'],
        81: ['Przelotne opady', 'bi bi-cloud-rain'],
        82: ['Gwałtowne opady', 'bi bi-cloud-rain-heavy'],
        95: ['Burza', 'bi bi-cloud-lightning'],
        96: ['Burza z gradem', 'bi bi-cloud-lightning-rain'],
        99: ['Burza z gradem', 'bi bi-cloud-lightning-rain'],
    };
    var el = document.getElementById('mun-weather');
    if (!el) return;
    var lat = el.dataset.lat, lon = el.dataset.lon;
    fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weather_code&wind_speed_unit=ms&temperature_unit=celsius&forecast_days=1')
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var code = d.current.weather_code;
            var temp = Math.round(d.current.temperature_2m);
            var info = WMO[code] || ['Zmienny', 'bi bi-cloud'];
            el.innerHTML = '<i class="' + info[1] + '" aria-hidden="true"></i><span>' + info[0] + ' ' + temp + '°C</span>';
        })
        .catch(function () { el.innerHTML = ''; });
}());
</script>
@endif

@if ($siteSettings->municipality_show_google_translate ?? false)
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({ pageLanguage: 'pl' }, 'google_translate_element');
}
document.getElementById('mun-translate-btn')?.addEventListener('click', function () {
    var box = document.getElementById('google_translate_element');
    if (!box) return;
    var nowHidden = box.classList.toggle('hidden');
    this.setAttribute('aria-expanded', nowHidden ? 'false' : 'true');
    if (!window._gtLoaded) {
        window._gtLoaded = true;
        var s = document.createElement('script');
        s.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(s);
    }
});
</script>
@endif
