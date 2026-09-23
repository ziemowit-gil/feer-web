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

    $colSpanFor = function (int $cols): string {
        return match ($cols) {
            2 => 'col-span-2',
            3 => 'col-span-2 sm:col-span-3',
            default => '',
        };
    };
@endphp

@if ($tiles && count($tiles) > 0)
<nav aria-label="{{ $label }}">
    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3" role="list">
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

                // Baza koloru: własny kolor kafelka → kolor marki → paleta awaryjna.
                $base = \App\Support\Color::isValid($tColor) ? $tColor : ($siteSettings->brandColorN(($i % 4) + 1) ?? '');
                if (! \App\Support\Color::isValid($base)) {
                    $base = $tilePalette[$i % count($tilePalette)];
                }

                $pal  = \App\Support\Color::button($base); // {bg, text, hover} — kontrast AA
                $bg   = $pal['bg'];
                $txt  = $pal['text'];
                $grad = 'linear-gradient(135deg, '.$bg.' 0%, '.\App\Support\Color::darken($bg, 0.18).' 100%)';
                $chip = $txt === '#ffffff' ? 'rgba(255,255,255,0.20)' : 'rgba(17,24,39,0.12)';

                $colSpan = $colSpanFor($tCols);
            @endphp

            <li class="{{ $colSpan }}">
                @if ($tStrip)
                    {{-- PASEK (poziomy) --}}
                    <a href="{{ $tUrl }}"
                        class="flex h-full items-center gap-4 rounded-xl px-5 py-4 shadow-sm transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                        style="background: {{ $grad }}; color: {{ $txt }};">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: {{ $chip }};">
                            <i class="bi {{ $tIcon }} text-lg" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-bold leading-tight">{{ $tLabel }}</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-xs opacity-70" aria-hidden="true"></i>
                    </a>
                @else
                    {{-- KARTA (pionowa) --}}
                    <a href="{{ $tUrl }}"
                        class="flex h-full min-h-36 flex-col justify-end gap-3 rounded-2xl p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                        style="background: {{ $grad }}; color: {{ $txt }};">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $chip }};">
                            <i class="bi {{ $tIcon }} text-xl" aria-hidden="true"></i>
                        </span>
                        <span class="block text-lg font-bold leading-tight">{{ $tLabel }}</span>
                        @if (filled($tDesc))
                            <span class="block text-sm opacity-85">{{ $tDesc }}</span>
                        @endif
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@endif
