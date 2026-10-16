{{--
    Blok „Ramka informacyjna" ([blok:ID]): płasko jak komunikat „Treść archiwalna" — bez obramowania, ikona po lewej,
    w wariantach kolorystycznych (niebieska, złota, czerwona, zielona) na jasnym tle albo w pełnym kolorze (negatyw).
    Kontrast tekstu ≥ 4,5:1 w każdym wariancie; ikona jest dekoracją (aria-hidden).
    Zmienne: $block (ContentBlock).
--}}
@if (! request()->attributes->get('cx_css'))
    @php request()->attributes->set('cx_css', true); @endphp
    <style>
        .cx-frame { --cx: var(--color-brand-dark); --cx-bg: var(--color-brand-light); --cx-neg: var(--color-brand-dark); --cx-neg-text: #fff; display: flex; align-items: flex-start; gap: .75rem; margin: 1.5rem 0; padding: .75rem 1rem; border-radius: .5rem; background: var(--cx-bg); color: var(--color-ink, #1d1d1a); line-height: 1.6; }
        .cx-frame.cx-gold { --cx: #a16207; --cx-bg: #fdf3e1; --cx-neg: #f2b705; --cx-neg-text: #1d1d1a; }
        .cx-frame.cx-red { --cx: #b91c1c; --cx-bg: #fdecec; --cx-neg: #b91c1c; --cx-neg-text: #fff; }
        .cx-frame.cx-green { --cx: #166534; --cx-bg: #e9f5ee; --cx-neg: #166534; --cx-neg-text: #fff; }
        .cx-frame.is-neg { background: var(--cx-neg); color: var(--cx-neg-text); }
        .cx-body { min-width: 0; }
        .cx-title { margin: 0 0 .25rem; font-size: 1.15rem; font-weight: 800; }
        .cx-text { margin: 0; }
        .cx-ico { flex-shrink: 0; margin-top: .3rem; color: var(--cx); }
        .cx-frame.is-neg .cx-ico { color: var(--cx-neg-text); }
    </style>
@endif
@php
    $d = $block->data ?? [];
    $variant = array_key_exists($d['variant'] ?? '', \App\Models\ContentBlock::CALLOUT_VARIANTS) ? $d['variant'] : 'blue';
    $icon = \App\Models\ContentBlock::CALLOUT_ICONS[$d['icon'] ?? 'exclamation'] ?? 'fa-exclamation';
@endphp
<div class="cx-frame cx-{{ $variant }} {{ ! empty($d['negative']) ? 'is-neg' : '' }} not-prose" role="note">
    <i class="cx-ico fa-solid {{ $icon }}" aria-hidden="true"></i>
    <div class="cx-body">
        @if (! empty($d['title']))<p class="cx-title">{{ $d['title'] }}</p>@endif
        <p class="cx-text">{!! nl2br(e($d['text'] ?? '')) !!}</p>
    </div>
</div>
