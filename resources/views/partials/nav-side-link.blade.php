{{--
    Pojedynczy własny link/przycisk menu rozwijanego (NavItem::megaSideLinks()). Zmienna: $sl, $block (true → pełna szerokość, mobile / zwykłe menu).
    Style: link · button (pigułka) · tile (obwódka 2 px jak kafle „Na skróty") · tile_filled (wypełniony — „negatyw").
--}}
@php
    $style = $sl['style'];
    $block = $block ?? false;
    $isTile = in_array($style, ['tile', 'tile_filled'], true);
    $accent = $isTile ? $siteSettings->contrastSafeColor($siteSettings->brand_color ?: '#1e6dff') : null;
    $cls = match ($style) {
        'button' => ($block ? 'flex justify-center' : 'inline-flex').' min-h-10 items-center gap-2 rounded-full bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2',
        'tile' => 'group flex min-h-12 w-full items-center gap-3 rounded-md bg-white px-4 py-2 text-base font-bold normal-case transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2',
        'tile_filled' => 'group flex min-h-12 w-full items-center gap-3 rounded-md px-4 py-2 text-base font-bold normal-case transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2',
        default => ($block ? 'flex' : 'inline-flex').' min-h-9 items-center gap-1.5 rounded-md px-2 text-sm font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand',
    };
    $inline = match ($style) {
        'tile' => 'border: 2px solid '.$accent.'; color: #1d1d1a',
        'tile_filled' => 'background-color: '.$accent.'; color: #ffffff',
        default => null,
    };
@endphp
<a href="{{ $sl['url'] }}" @if ($sl['new_tab']) target="_blank" rel="noopener" @endif class="{{ $cls }}" @if ($inline) style="{{ $inline }}" @endif>
    <span @class(['min-w-0', 'flex-1 leading-snug' => $isTile])>{{ $sl['label'] }}</span>
    @if ($isTile)
        <span class="flex-none text-xl transition group-hover:translate-x-1" aria-hidden="true">→</span>
    @else
        <i class="fa-solid {{ $sl['new_tab'] ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }} text-xs" aria-hidden="true"></i>
    @endif
</a>
