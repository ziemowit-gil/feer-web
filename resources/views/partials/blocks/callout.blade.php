{{--
    Blok „Ramka informacyjna" ([blok:ID]): ramka z ikoną po prawej w wariantach kolorystycznych (niebieska, złota, czerwona, zielona)
    — w wersji z obwódką albo negatywowej (wypełnionej). Kontrast tekstu ≥ 4,5:1 w każdym wariancie; ikona jest dekoracją (aria-hidden).
    Zmienne: $block (ContentBlock).
--}}
@if (! request()->attributes->get('cx_css'))
    @php request()->attributes->set('cx_css', true); @endphp
    <style>
        .cx-frame { --cx: #1e6dff; --cx-text: #1d1d1a; --cx-neg: #1e6dff; --cx-neg-text: #fff; position: relative; margin: 1.5rem 0; padding: 1rem 4.75rem 1rem 1.25rem; border: 2px solid var(--cx); border-radius: .5rem; background: #fff; color: var(--cx-text); line-height: 1.6; }
        .cx-frame.cx-gold { --cx: #a16207; --cx-neg: #f2b705; --cx-neg-text: #1d1d1a; }
        .cx-frame.cx-red { --cx: #b91c1c; --cx-neg: #b91c1c; --cx-neg-text: #fff; }
        .cx-frame.cx-green { --cx: #166534; --cx-neg: #166534; --cx-neg-text: #fff; }
        .cx-frame.is-neg { background: var(--cx-neg); border-color: var(--cx-neg); color: var(--cx-neg-text); }
        .cx-title { margin: 0 0 .25rem; font-size: 1.15rem; font-weight: 800; }
        .cx-text { margin: 0; font-weight: 600; }
        .cx-ico { position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border: 2px solid var(--cx); border-radius: 9999px; background: #fff; color: var(--cx); font-size: 1.1rem; }
        .cx-frame.is-neg .cx-ico { border-color: var(--cx-neg-text); background: transparent; color: var(--cx-neg-text); }
    </style>
@endif
@php
    $d = $block->data ?? [];
    $variant = array_key_exists($d['variant'] ?? '', \App\Models\ContentBlock::CALLOUT_VARIANTS) ? $d['variant'] : 'blue';
    $icon = \App\Models\ContentBlock::CALLOUT_ICONS[$d['icon'] ?? 'exclamation'] ?? 'fa-exclamation';
@endphp
<div class="cx-frame cx-{{ $variant }} {{ ! empty($d['negative']) ? 'is-neg' : '' }} not-prose" role="note">
    @if (! empty($d['title']))<p class="cx-title">{{ $d['title'] }}</p>@endif
    <p class="cx-text">{!! nl2br(e($d['text'] ?? '')) !!}</p>
    <span class="cx-ico" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
</div>
