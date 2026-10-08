{{--
    Reusable partial: siatka kafelków (styl „solid" — wypełnione kolorem,
    biały/kontrastowy tekst i ikona, jak kafelki huba).
    Parametry:
      $tiles  – Collection<QuickAction> lub array tablic z kluczami
                label, icon, url, color, strip, cols, description (opcjonalny)
      $label  – aria-label dla <nav> (opcjonalny, domyślnie "Kafelki")

    Kolor tła bierze się z pola `color` kafelka (dowolny #hex), a gdy go brak —
    z rotacji kolorów marki. Kolor tekstu dobiera Color::button (kontrast WCAG AA).
--}}
@php
    $label ??= 'Kafelki';

    // Awaryjny kolor, gdy kafelek nie ma własnego i marka nie zwróci hexu.
    $tilePalette = ['#1a56a4', '#166534', '#7e22ce', '#c2410c', '#991b1b', '#374151'];

    // Nazwane klucze kolorów (jak w hubie/„Na skróty" hub_links) → #hex.
    $namedColors = [
        'blue'   => '#2563eb',
        'dark'   => '#374151',
        'green'  => '#16a34a',
        'purple' => '#7e22ce',
        'orange' => '#f97316',
        'red'    => '#ef4444',
    ];

    // Szablon FEER: kolory z brandbooka — nazwane kolory mapują się na paletę (niebieski #1E6DFF, grafit, pomarańcz),
    // a zielony/fioletowy/czerwony i awaryjna paleta też dają kolory marki.
    $feerTiles = ($siteSettings->site_template ?? 'default') === 'feer';
    if ($feerTiles) {
        $namedColors = \App\Support\ThemePalette::named();
        $tilePalette = \App\Support\ThemePalette::tiles();
    }

    $colSpanFor = function (int $cols): string {
        return match ($cols) {
            2 => 'col-span-2',
            3 => 'col-span-2 sm:col-span-3',
            default => '',
        };
    };
@endphp

@if ($tiles && count($tiles) > 0)
{{-- not-prose + list-none: w treści z klasą .prose (typography) kafelki nie dostają punktorów ani marginesów listy. --}}
<nav class="not-prose" @if (! empty($labelledby)) aria-labelledby="{{ $labelledby }}" @else aria-label="{{ $label }}" @endif>
    <ul class="m-0 grid list-none grid-cols-2 gap-4 p-0 sm:grid-cols-3" role="list">
        @foreach ($tiles as $i => $tile)
            @php
                // Normalizacja — obsługuje zarówno obiekty QuickAction, jak i tablice.
                $isObj  = is_object($tile);
                $tLabel = $isObj ? $tile->label       : ($tile['label']       ?? '');
                $tIcon  = $isObj ? $tile->icon        : ($tile['icon']        ?? 'bi-lightning');
                $tUrl   = $isObj ? $tile->url         : ($tile['url']         ?? '#');
                $tColor = $isObj ? $tile->color       : ($tile['color']       ?? null);
                $tStrip = $isObj ? (bool) $tile->strip : (bool) ($tile['strip'] ?? false);
                $tCols  = $isObj ? (int) ($tile->cols ?? 1) : (int) ($tile['cols'] ?? 1);
                $tDesc  = $isObj ? ($tile->description ?? null) : ($tile['description'] ?? null);
                $tImage = ($feerTiles ?? false) ? ($isObj ? ($tile->image ?? null) : ($tile['image'] ?? null)) : null; // zdjęcie w tle (tylko FEER)

                // Baza koloru: #hex kafelka → nazwany klucz → kolor marki → paleta awaryjna.
                $base = \App\Support\Color::isValid($tColor)
                    ? $tColor
                    : ($namedColors[$tColor] ?? ($siteSettings->brandColorN(($i % 4) + 1) ?? ''));
                if (! \App\Support\Color::isValid($base)) {
                    $base = $tilePalette[$i % count($tilePalette)];
                }

                // {bg, text, hover} — kontrast AA (chyba że wyłączono poprawianie do WCAG); kolor główny motywu bez zmian
                $pal  = ($feerTiles ?? false) ? \App\Support\ThemePalette::button($base) : \App\Support\Color::button($base);
                $bg   = $pal['bg'];
                $txt  = $pal['text'];
                $chip = $txt === '#ffffff' ? 'rgba(255,255,255,0.20)' : 'rgba(17,24,39,0.12)';

                // Ikona: pełna klasa FontAwesome („fa-…") użyta wprost; nazwa
                // Bootstrap Icons („bi-…") dostaje prefiks „bi".
                $iconClass = (str_contains((string) $tIcon, 'fa-') || str_starts_with((string) $tIcon, 'bi '))
                    ? $tIcon
                    : 'bi '.$tIcon;

                $colSpan = $colSpanFor($tCols);
            @endphp

            <li class="{{ $colSpan }}">
                @if ($tStrip)
                    {{-- PASEK (poziomy) --}}
                    <a href="{{ $tUrl }}"
                        class="flex h-full items-center gap-4 rounded-xl px-5 py-4 shadow-sm transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                        style="background-color: {{ $bg }}; color: {{ $txt }};">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: {{ $chip }};">
                            {!! icon_html($tIcon, 'text-lg') !!}
                        </span>
                        <span class="text-sm font-bold leading-tight">{{ $tLabel }}</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-xs opacity-70" aria-hidden="true"></i>
                    </a>
                @else
                    {{-- KARTA (pionowa) --}}
                    <a href="{{ $tUrl }}"
                        class="relative flex h-full min-h-36 flex-col justify-end gap-3 overflow-hidden rounded-2xl p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                        style="background-color: {{ $bg }}; color: {{ filled($tImage) ? '#ffffff' : $txt }};">
                        @if (filled($tImage))
                            <span class="pointer-events-none absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $tImage }}')" aria-hidden="true"></span>
                            <span class="pointer-events-none absolute inset-0" style="background-color: rgba(29,29,26,.62)" aria-hidden="true"></span>
                        @endif
                        <span class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ filled($tImage) ? 'rgba(255,255,255,.2)' : $chip }};">
                            {!! icon_html($tIcon, 'text-xl') !!}
                        </span>
                        <span class="relative block text-lg font-bold leading-tight">{{ $tLabel }}</span>
                        @if (filled($tDesc))
                            <span class="relative block text-sm opacity-85">{{ $tDesc }}</span>
                        @endif
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@endif
