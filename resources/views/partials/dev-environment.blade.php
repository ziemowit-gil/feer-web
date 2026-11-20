{{--
    Oznaczenie instalacji podglądowej (kopia dev dla klienta), włączane w .env:
      APP_PREVIEW_BAR="Wersja podglądowa"  — pasek na samej górze strony (przed przejściem do treści nie przeszkadza:
                                              to zwykły tekst w landmarku „region” z nazwą),
      APP_WATERMARK="ZG"                    — znak wodny powtarzany ukośnie na całej stronie, nad treścią, ale
                                              przezroczysty dla kliknięć (pointer-events: none), aria-hidden i bardzo
                                              jasny (6% krycia), więc nie obniża czytelności tekstu.
    Kontrast paska: #1d1d1a na #fce98b 14:1. Na wydruku pasek i znak wodny są ukryte.
--}}
@php
    $devBar = trim((string) config('app.preview_bar'));
    $devMark = trim((string) config('app.watermark'));
    $devSvg = $devMark !== ''
        ? 'data:image/svg+xml;charset=utf-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="260" height="200" viewBox="0 0 260 200"><text x="130" y="110" text-anchor="middle" font-family="Montserrat, Arial, sans-serif" font-size="64" font-weight="800" fill="#000" fill-opacity="0.06" transform="rotate(-30 130 100)">'.e($devMark).'</text></svg>')
        : null;
@endphp
<style>
    .dev-preview-bar { position: relative; z-index: 60; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .25rem .75rem;
        padding: .45rem 1rem; background: #fce98b; color: #1d1d1a; font: 700 .9rem/1.4 Montserrat, Arial, sans-serif; text-align: center; border-bottom: 2px solid #1d1d1a; }
    .dev-preview-bar span { font-weight: 600; }
    .dev-watermark { position: fixed; inset: 0; z-index: 9999; pointer-events: none; background-repeat: repeat; }
    @media print { .dev-preview-bar, .dev-watermark { display: none !important; } }
    @media (forced-colors: active) { .dev-watermark { display: none; } }
</style>
@if ($devBar !== '')
    <div class="dev-preview-bar no-print" role="region" aria-label="Informacja o wersji strony">
        <i class="fa-solid fa-eye" aria-hidden="true"></i>{{ $devBar }}@if ($devMark !== '')<span>· {{ $devMark }}</span>@endif<span>— strona w przygotowaniu, nie jest jeszcze opublikowana</span>
    </div>
@endif
@if ($devSvg)
    <div class="dev-watermark" aria-hidden="true" style="background-image: url(&quot;{{ $devSvg }}&quot;)"></div>
@endif
