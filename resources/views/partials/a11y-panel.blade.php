{{--
    Panel ułatwień dostępu — rozwijany pod paskiem górnym (partials/topbar).

    Wymaga w rodzicu Alpine `open` (stan panelu) oraz `$refs.a11yToggle`
    (przycisk otwierający — Escape zamyka panel i oddaje mu fokus).
    Logika przycisków (data-a11y-*) jest w resources/js/app.js; tutaj tylko
    markup zgodny z WCAG 2.2:
      • każdy przycisk ma widoczną etykietę i pole klikalne ≥ 40 px (2.5.8),
      • przełączniki mają aria-pressed (4.1.2), grupy mają nazwę (1.3.1),
      • kontrast tekstu na tle panelu ≥ 7:1 (1.4.6).
--}}
@php
    $a11yBtn = 'group inline-flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-ink transition '
        . 'hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    $a11yIcon = 'w-4 text-center text-gray-500 group-aria-pressed:text-brand';
@endphp

<div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności"
     class="border-b border-gray-200 bg-gray-50">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">

        <div role="group" aria-label="Rozmiar tekstu" class="flex items-center gap-1">
            <span class="mr-1 text-xs font-bold uppercase tracking-wide text-muted" aria-hidden="true">Tekst</span>
            <button type="button" data-a11y-font="down" class="{{ $a11yBtn }} min-w-10 justify-center px-2" aria-label="Zmniejsz tekst">A<sup aria-hidden="true">−</sup></button>
            <button type="button" data-a11y-font="reset" class="{{ $a11yBtn }} min-w-10 justify-center px-2" aria-label="Domyślny rozmiar tekstu">A</button>
            <button type="button" data-a11y-font="up" class="{{ $a11yBtn }} min-w-10 justify-center px-2" aria-label="Powiększ tekst">A<sup aria-hidden="true">+</sup></button>
        </div>

        <div role="group" aria-label="Czytelność tekstu" class="flex flex-wrap items-center gap-1.5">
            <button type="button" data-a11y-lh class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-arrows-up-down {{ $a11yIcon }}" aria-hidden="true"></i> Odstęp wierszy
            </button>
            <button type="button" data-a11y-ls class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-text-width {{ $a11yIcon }}" aria-hidden="true"></i> Odstęp liter
            </button>
            <button type="button" data-a11y-sans class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-font {{ $a11yIcon }}" aria-hidden="true"></i> Czcionka bezszeryfowa
            </button>
            <button type="button" data-a11y-underline-links class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-underline {{ $a11yIcon }}" aria-hidden="true"></i> Podkreśl linki
            </button>
            <button type="button" data-a11y-focus class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-vector-square {{ $a11yIcon }}" aria-hidden="true"></i> Wyraźny fokus
            </button>
        </div>

        <div role="group" aria-label="Tryb kontrastu" class="flex flex-wrap items-center gap-1.5">
            <button type="button" data-a11y-contrast="contrast" class="{{ $a11yBtn }}" aria-pressed="false">
                <i class="fa-solid fa-circle-half-stroke {{ $a11yIcon }}" aria-hidden="true"></i> Kontrast
            </button>
            <button type="button" data-a11y-contrast="contrast-bw" class="{{ $a11yBtn }}" aria-pressed="false">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#000;color:#ff0">A</span> Czarno-żółty
            </button>
            <button type="button" data-a11y-contrast="contrast-gray" class="{{ $a11yBtn }}" aria-pressed="false">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#6b7280;color:#fff">A</span> Skala szarości
            </button>
        </div>

        <button type="button" data-a11y-animations class="{{ $a11yBtn }}" aria-pressed="false">
            <i class="fa-solid fa-pause {{ $a11yIcon }}" aria-hidden="true"></i> Wstrzymaj animacje
        </button>

        <div class="ml-auto flex flex-wrap items-center gap-x-4 gap-y-2">
            <button type="button" data-a11y-reset
                class="inline-flex min-h-10 items-center gap-2 rounded-lg px-2 text-sm font-medium text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-rotate-left w-4 text-center" aria-hidden="true"></i> Przywróć domyślne
            </button>
            @if (\Illuminate\Support\Facades\Route::has('accessibility.show'))
                <a href="{{ route('accessibility.show') }}"
                   class="inline-flex min-h-10 items-center gap-2 rounded-lg px-2 text-sm font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-file-lines w-4 text-center" aria-hidden="true"></i> Deklaracja dostępności
                </a>
            @endif
        </div>
    </div>
</div>
