{{--
    Reusable partial: siatka kafelków QuickAction-style.
    Parametry:
      $tiles  – Collection<QuickAction> lub array tablic z kluczami
                label, icon, url, color, is_negative, cols, strip
      $label  – aria-label dla <nav> (opcjonalny, domyślnie "Kafelki")
--}}
@php
    $label ??= 'Kafelki';

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
        @foreach ($tiles as $tile)
            @php
                // Normalizacja — obsługuje zarówno obiekty QuickAction, jak i tablice
                $isObj     = is_object($tile);
                $tLabel    = $isObj ? $tile->label      : ($tile['label'] ?? '');
                $tIcon     = $isObj ? $tile->icon       : ($tile['icon']  ?? 'bi-lightning');
                $tUrl      = $isObj ? $tile->url        : ($tile['url']   ?? '#');
                $tColor    = $isObj ? $tile->color      : ($tile['color'] ?? null);
                $tNeg      = $isObj ? (bool)$tile->is_negative : (bool)($tile['is_negative'] ?? false);
                $tStrip    = $isObj ? (bool)$tile->strip       : (bool)($tile['strip']       ?? false);
                $tCols     = $isObj ? (int)($tile->cols ?? 1)  : (int)($tile['cols']         ?? 1);

                $hasColor  = \App\Support\Color::isValid($tColor);
                $qa        = $hasColor ? \App\Support\Color::button($tColor) : null;
                $bgColor   = $hasColor ? $qa['bg'] : 'var(--color-brand)';
                $fgColor   = $hasColor ? $qa['text'] : '#ffffff';
                $colSpan   = $colSpanFor($tCols);

                $base = 'rounded-lg shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 ' . $colSpan;
            @endphp

            <li class="{{ $colSpan }}">
                @if ($tStrip)
                    {{-- PASEK --}}
                    <a href="{{ $tUrl }}"
                        class="{{ $base }} flex h-full items-center gap-4 px-5 py-4 hover:shadow-md
                            @if ($tNeg) hover:opacity-90 @else border-2 border-gray-200 bg-white hover:border-brand @endif"
                        @if ($tNeg) style="background-color: {{ $bgColor }}; color: {{ $fgColor }};" @endif>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xl"
                            @if ($tNeg) style="background-color: rgba(255,255,255,0.2);"
                            @elseif ($hasColor) style="background-color: {{ $bgColor }}; color: {{ $fgColor }};"
                            @else class="bg-brand-light text-brand" @endif>
                            <i class="bi {{ $tIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-bold @if (!$tNeg) text-ink @endif">{{ $tLabel }}</span>
                        <i class="fa-solid fa-chevron-right ml-auto text-[0.65rem] opacity-40" aria-hidden="true"></i>
                    </a>

                @elseif ($tNeg)
                    {{-- KARTA NEGATYW --}}
                    <a href="{{ $tUrl }}"
                        class="{{ $base }} flex h-full flex-col items-center gap-2 px-4 py-6 text-center hover:opacity-90 hover:shadow-md"
                        style="background-color: {{ $bgColor }}; color: {{ $fgColor }};">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl"
                            style="background-color: rgba(255,255,255,0.2);">
                            <i class="bi {{ $tIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-bold">{{ $tLabel }}</span>
                    </a>

                @elseif ($hasColor)
                    {{-- KARTA Z KOLOREM --}}
                    <a href="{{ $tUrl }}"
                        class="{{ $base }} flex h-full flex-col items-center gap-2 border-2 border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md"
                        onmouseover="this.style.borderColor='{{ $bgColor }}'" onmouseout="this.style.borderColor=''">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl"
                            style="background-color: {{ $bgColor }}; color: {{ $fgColor }};">
                            <i class="bi {{ $tIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-bold text-ink">{{ $tLabel }}</span>
                    </a>

                @else
                    {{-- KARTA DOMYŚLNA --}}
                    <a href="{{ $tUrl }}"
                        class="{{ $base }} flex h-full flex-col items-center gap-2 border-2 border-gray-200 bg-white px-4 py-6 text-center hover:border-brand hover:text-brand hover:shadow-md">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                            <i class="bi {{ $tIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-bold">{{ $tLabel }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@endif
