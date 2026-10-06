{{--
    Pasek ułatwień dostępu — jeden smukły rząd pod paskiem górnym (partials/topbar), widoczny po kliknięciu
    przycisku „Dostępność”. Wszystkie opcje są w jednej linii z krótkimi etykietami (zawija się dopiero na
    wąskich ekranach), nic nie zasłania nagłówka ani logo.

    Wymaga w rodzicu Alpine `open` (stan paska) oraz `$refs.a11yToggle` (przycisk otwierający — Escape
    zamyka pasek i oddaje mu fokus). Logika przycisków (data-a11y-*) jest w resources/js/app.js;
    tutaj tylko markup zgodny z WCAG 2.2:
      • każdy przycisk ma widoczną etykietę, a cel dotyku ≥ 32 px (2.5.8),
      • przełączniki mają aria-pressed (4.1.2), grupy mają nazwę (1.3.1),
      • kontrast tekstu na tle paska ≥ 7:1 (1.4.6).
    Odstępy i układ są wbudowane w style, żeby pasek wyglądał poprawnie także przy nieprzebudowanym CSS.
--}}
@php
    $a11yBtn = 'group inline-flex items-center rounded-md border border-gray-300 bg-white text-xs font-medium text-ink transition '
        . 'hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1';
    $a11yBtnStyle = 'min-height:2rem;padding:.25rem .5rem;gap:.375rem;white-space:nowrap';
    $a11yIcon = 'w-3.5 flex-none text-center text-gray-500 group-aria-pressed:text-brand';
    $a11ySep = '<span aria-hidden="true" style="width:1px;height:1.25rem;background:#d1d5db;margin:0 .25rem"></span>';
@endphp

<div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności" class="border-t border-gray-200 bg-white">
    <div class="mx-auto px-4" style="max-width:1400px;display:flex;flex-wrap:wrap;align-items:center;gap:.25rem .375rem;padding-top:.375rem;padding-bottom:.375rem">

        <div role="group" aria-label="Rozmiar tekstu" style="display:inline-flex;align-items:center;gap:.25rem">
            <span class="text-[11px] font-bold uppercase tracking-wide text-muted" aria-hidden="true" style="margin-right:.125rem">Tekst</span>
            <button type="button" data-a11y-font="down" class="{{ $a11yBtn }} justify-center" style="min-height:2rem;min-width:2rem;padding:.25rem .375rem" aria-label="Zmniejsz tekst">A<sup aria-hidden="true">−</sup></button>
            <button type="button" data-a11y-font="reset" class="{{ $a11yBtn }} justify-center" style="min-height:2rem;min-width:2rem;padding:.25rem .375rem" aria-label="Domyślny rozmiar tekstu">A</button>
            <button type="button" data-a11y-font="up" class="{{ $a11yBtn }} justify-center" style="min-height:2rem;min-width:2rem;padding:.25rem .375rem" aria-label="Powiększ tekst">A<sup aria-hidden="true">+</sup></button>
        </div>

        {!! $a11ySep !!}

        <div role="group" aria-label="Czytelność tekstu" style="display:inline-flex;flex-wrap:wrap;align-items:center;gap:.25rem">
            <button type="button" data-a11y-lh class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Odstęp wierszy">
                <i class="fa-solid fa-arrows-up-down {{ $a11yIcon }}" aria-hidden="true"></i> Wiersze
            </button>
            <button type="button" data-a11y-ls class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Odstęp liter">
                <i class="fa-solid fa-text-width {{ $a11yIcon }}" aria-hidden="true"></i> Litery
            </button>
            <button type="button" data-a11y-sans class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Czcionka bezszeryfowa">
                <i class="fa-solid fa-font {{ $a11yIcon }}" aria-hidden="true"></i> Bez szeryfów
            </button>
            <button type="button" data-a11y-underline-links class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Podkreśl linki">
                <i class="fa-solid fa-underline {{ $a11yIcon }}" aria-hidden="true"></i> Linki
            </button>
            <button type="button" data-a11y-focus class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Wyraźny fokus">
                <i class="fa-solid fa-vector-square {{ $a11yIcon }}" aria-hidden="true"></i> Fokus
            </button>
            <button type="button" data-a11y-animations class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Wstrzymaj animacje">
                <i class="fa-solid fa-pause {{ $a11yIcon }}" aria-hidden="true"></i> Animacje
            </button>
        </div>

        {!! $a11ySep !!}

        <div role="group" aria-label="Tryb kontrastu" style="display:inline-flex;flex-wrap:wrap;align-items:center;gap:.25rem">
            <button type="button" data-a11y-contrast="contrast" class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Wysoki kontrast">
                <i class="fa-solid fa-circle-half-stroke {{ $a11yIcon }}" aria-hidden="true"></i> Kontrast
            </button>
            <button type="button" data-a11y-contrast="contrast-bw" class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Czarno-żółty">
                <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#000;color:#ff0">A</span> Czarno-żółty
            </button>
            <button type="button" data-a11y-contrast="contrast-gray" class="{{ $a11yBtn }}" style="{{ $a11yBtnStyle }}" aria-pressed="false" aria-label="Skala szarości">
                <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#6b7280;color:#fff">A</span> Szarość
            </button>
        </div>

        <div style="margin-left:auto;display:inline-flex;flex-wrap:wrap;align-items:center;gap:.25rem .5rem">
            <button type="button" data-a11y-reset aria-label="Przywróć domyślne ustawienia dostępności"
                class="inline-flex items-center rounded-md text-xs font-medium text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                style="min-height:2rem;padding:.25rem .375rem;gap:.375rem;white-space:nowrap">
                <i class="fa-solid fa-rotate-left w-3.5 text-center" aria-hidden="true"></i> Domyślne
            </button>
            @if (\Illuminate\Support\Facades\Route::has('accessibility.show'))
                <a href="{{ route('accessibility.show') }}" aria-label="Deklaracja dostępności"
                   class="inline-flex items-center rounded-md text-xs font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                   style="min-height:2rem;padding:.25rem .375rem;gap:.375rem;white-space:nowrap">
                    <i class="fa-solid fa-file-lines w-3.5 text-center" aria-hidden="true"></i> Deklaracja
                </a>
            @endif
        </div>
    </div>
</div>
