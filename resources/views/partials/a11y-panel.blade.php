{{--
    Panel ułatwień dostępu — mały panel wysuwany pod przyciskiem „Dostępność” w pasku górnym
    (partials/topbar). Jest nakładką (position:absolute), więc nie przesuwa treści strony,
    zamyka się klawiszem Escape (oddaje fokus przyciskowi), kliknięciem poza panelem i ponownym
    kliknięciem przycisku.

    Wymaga w rodzicu Alpine `open` (stan panelu) oraz `$refs.a11yToggle` (przycisk otwierający),
    a w rodzicu `position: relative`. Logika przycisków (data-a11y-*) jest w resources/js/app.js;
    tutaj tylko markup zgodny z WCAG 2.2:
      • każdy przycisk ma widoczną etykietę, a cel dotyku ≥ 36 px (2.5.8),
      • przełączniki mają aria-pressed (4.1.2), grupy mają nazwę (1.3.1),
      • kontrast tekstu na tle panelu ≥ 7:1 (1.4.6).
    Położenie i siatki są wbudowane w style, żeby panel wyglądał poprawnie także przy nieprzebudowanym CSS.
--}}
@php
    $a11yBtn = 'group inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-2.5 text-[13px] font-medium text-ink transition '
        . 'hover:border-brand hover:text-brand aria-pressed:border-brand aria-pressed:bg-brand-light aria-pressed:text-brand '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1';
    $a11yIcon = 'w-3.5 flex-none text-center text-gray-500 group-aria-pressed:text-brand';
    $a11yHead = 'text-[11px] font-bold uppercase tracking-wide text-muted';
    $grid2 = 'display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.375rem';
@endphp

<div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności"
     @click.outside="if (! $refs.a11yToggle.contains($event.target)) open = false"
     style="position:absolute;left:0;right:0;top:100%;z-index:60;pointer-events:none">
    <div class="mx-auto max-w-6xl px-4">
        <div class="rounded-xl border border-gray-200 bg-white shadow-xl" style="pointer-events:auto;width:min(21rem,100%);padding:.75rem">

            {{-- Rozmiar tekstu --}}
            <div role="group" aria-label="Rozmiar tekstu" style="display:flex;align-items:center;gap:.375rem">
                <span class="{{ $a11yHead }}" style="flex:1" aria-hidden="true">Tekst</span>
                <button type="button" data-a11y-font="down" class="{{ $a11yBtn }} min-w-9 justify-center" aria-label="Zmniejsz tekst">A<sup aria-hidden="true">−</sup></button>
                <button type="button" data-a11y-font="reset" class="{{ $a11yBtn }} min-w-9 justify-center" aria-label="Domyślny rozmiar tekstu">A</button>
                <button type="button" data-a11y-font="up" class="{{ $a11yBtn }} min-w-9 justify-center" aria-label="Powiększ tekst">A<sup aria-hidden="true">+</sup></button>
            </div>

            {{-- Czytelność --}}
            <div role="group" aria-label="Czytelność tekstu" style="margin-top:.75rem">
                <p class="{{ $a11yHead }}" style="margin-bottom:.375rem" aria-hidden="true">Czytelność</p>
                <div style="{{ $grid2 }}">
                    <button type="button" data-a11y-lh class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-arrows-up-down {{ $a11yIcon }}" aria-hidden="true"></i> Odstęp wierszy
                    </button>
                    <button type="button" data-a11y-ls class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-text-width {{ $a11yIcon }}" aria-hidden="true"></i> Odstęp liter
                    </button>
                    <button type="button" data-a11y-sans class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-font {{ $a11yIcon }}" aria-hidden="true"></i> Bez szeryfów
                    </button>
                    <button type="button" data-a11y-underline-links class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-underline {{ $a11yIcon }}" aria-hidden="true"></i> Podkreśl linki
                    </button>
                    <button type="button" data-a11y-focus class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-vector-square {{ $a11yIcon }}" aria-hidden="true"></i> Wyraźny fokus
                    </button>
                    <button type="button" data-a11y-animations class="{{ $a11yBtn }}" aria-pressed="false">
                        <i class="fa-solid fa-pause {{ $a11yIcon }}" aria-hidden="true"></i> Bez animacji
                    </button>
                </div>
            </div>

            {{-- Kontrast --}}
            <div role="group" aria-label="Tryb kontrastu" style="margin-top:.75rem">
                <p class="{{ $a11yHead }}" style="margin-bottom:.375rem" aria-hidden="true">Kontrast</p>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.375rem">
                    <button type="button" data-a11y-contrast="contrast" class="{{ $a11yBtn }} justify-center" aria-pressed="false">
                        <i class="fa-solid fa-circle-half-stroke {{ $a11yIcon }}" aria-hidden="true"></i> Wysoki
                    </button>
                    <button type="button" data-a11y-contrast="contrast-bw" class="{{ $a11yBtn }} justify-center" aria-pressed="false" aria-label="Czarno-żółty">
                        <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#000;color:#ff0">A</span> Czarno-żółty
                    </button>
                    <button type="button" data-a11y-contrast="contrast-gray" class="{{ $a11yBtn }} justify-center" aria-pressed="false" aria-label="Skala szarości">
                        <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-black leading-none" aria-hidden="true" style="background:#6b7280;color:#fff">A</span> Szarość
                    </button>
                </div>
            </div>

            {{-- Stopka panelu --}}
            <div class="border-t border-gray-100" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.25rem .75rem;margin-top:.75rem;padding-top:.5rem">
                <button type="button" data-a11y-reset
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-1.5 text-[13px] font-medium text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                    <i class="fa-solid fa-rotate-left w-3.5 text-center" aria-hidden="true"></i> Przywróć domyślne
                </button>
                @if (\Illuminate\Support\Facades\Route::has('accessibility.show'))
                    <a href="{{ route('accessibility.show') }}"
                       class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-1.5 text-[13px] font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                        <i class="fa-solid fa-file-lines w-3.5 text-center" aria-hidden="true"></i> Deklaracja dostępności
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
