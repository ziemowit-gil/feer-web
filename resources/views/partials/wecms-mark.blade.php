{{--
    Znak weCMS do stopek szablonów: kwadrat z zaokrągleniem i „W” jako zygzak + napis „weCMS”. Grafika dekoracyjna
    (aria-hidden), nazwę czyta napis obok. Kolory: $bg (tło znaku) i $fg (kreska), domyślnie żółć na ciemnej zieleni
    szablonu „kreatywni” — kontrast kreski #143805 na #fce98b ok. 12:1.
--}}
@php
    $bg = $bg ?? '#fce98b';
    $fg = $fg ?? '#143805';
    $size = (int) ($size ?? 24);
@endphp
<span class="wecms-mark">
    <svg viewBox="0 0 32 32" width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false"><rect width="32" height="32" rx="8" fill="{{ $bg }}"/><path d="M7 11l3.6 10.5L16 13.5l5.4 8L25 11" fill="none" stroke="{{ $fg }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span class="wecms-mark-name">we<b>CMS</b></span>
</span>
