@php
    use App\Support\PolishNameDays;
    $nameDays = PolishNameDays::format();

    $weekdays = ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota'];
    $months   = ['', 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca',
                 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
    $now = now();
    $dateStr = $weekdays[$now->dayOfWeek] . ', ' . $now->day . ' ' . $months[$now->month] . ' ' . $now->year;
@endphp

<div class="bg-brand text-white text-xs" role="region" aria-label="Pasek informacyjny i dostępność">
    <div class="mx-auto max-w-[1400px] flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-1.5">

        {{-- Data i imieniny --}}
        <div class="flex shrink-0 items-center gap-2 border-r border-white/30 pr-4">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <time datetime="{{ $now->toDateString() }}">{{ $dateStr }}</time>
        </div>

        @if ($nameDays)
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

        {{-- Prawa strona: dostępność + Google Translate --}}
        <div class="ml-auto flex shrink-0 flex-wrap items-center gap-3">

            {{-- Rozmiar czcionki --}}
            <div class="flex items-center gap-1" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="up"
                    class="mun-a11y-btn" aria-label="Zwiększ czcionkę">A+</button>
                <button type="button" data-a11y-font="up" data-a11y-font-step="2"
                    class="mun-a11y-btn" aria-label="Znacznie zwiększ czcionkę">A++</button>
                <button type="button" data-a11y-font="reset"
                    class="mun-a11y-btn" aria-label="Domyślny rozmiar czcionki">A</button>
            </div>

            <span class="h-4 border-l border-white/30" aria-hidden="true"></span>

            {{-- Odstęp między wierszami --}}
            <button type="button" data-a11y-lh
                class="mun-a11y-btn aria-pressed:ring-1 aria-pressed:ring-white" aria-pressed="false"
                aria-label="Zwiększ odstęp między wierszami" title="Odstęp między wierszami">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            {{-- Rozstrzał liter --}}
            <button type="button" data-a11y-ls
                class="mun-a11y-btn aria-pressed:ring-1 aria-pressed:ring-white" aria-pressed="false"
                aria-label="Rozstrzał liter" title="Rozstrzał liter">
                <i class="fa-solid fa-text-width" aria-hidden="true"></i>
            </button>

            <span class="h-4 border-l border-white/30" aria-hidden="true"></span>

            {{-- Kontrast --}}
            <button type="button" data-a11y-contrast="contrast"
                class="mun-a11y-btn aria-pressed:ring-1 aria-pressed:ring-white" aria-pressed="false"
                aria-label="Kontrast" title="Tryb wysokiego kontrastu">
                <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
            </button>

            @if ($siteSettings->municipality_show_google_translate ?? false)
                <span class="h-4 border-l border-white/30" aria-hidden="true"></span>
                {{-- Google Translate trigger --}}
                <button type="button" id="mun-translate-btn"
                    class="mun-a11y-btn flex items-center gap-1" aria-haspopup="true"
                    aria-label="Tłumacz stronę (Google Translate)">
                    <span class="hidden sm:inline">Translate</span>
                    <i class="bi bi-translate" aria-hidden="true"></i>
                </button>
                <div id="google_translate_element" class="hidden"></div>
            @endif
        </div>
    </div>
</div>

<style>
    .mun-a11y-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.25rem;
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        color: rgba(255,255,255,0.8);
        transition: color 0.15s, background 0.15s;
        text-decoration: none;
        cursor: pointer;
        background: transparent;
        border: none;
    }
    .mun-a11y-btn:hover { color: #fff; background: rgba(255,255,255,0.2); }
    .mun-a11y-btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
    .mun-a11y-btn[aria-pressed="true"] { font-weight: 700; color: #fff; }
</style>
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
    box.classList.toggle('hidden');
    if (!window._gtLoaded) {
        window._gtLoaded = true;
        var s = document.createElement('script');
        s.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(s);
    }
});
</script>
@endif
