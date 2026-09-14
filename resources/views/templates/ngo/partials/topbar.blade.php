{{--
    Panel ułatwień dostępu — bez osobnego, zawsze widocznego paska (wzorzec
    "federation"): przełącznik mieszka w nagłówku (templates.ngo.partials.header),
    stan "open" trzymany na wspólnym wrapperze w layouts/site.blade.php. Ten plik
    to tylko sam panel, chowany/pokazywany pod tym przełącznikiem.

    Newsletter/BIP (drugorzędne linki) są tutaj, nie w nagłówku — "Wesprzyj nas"
    zostaje wyłącznie w pasku nawigacji (header), żeby nie dublować tego samego
    CTA w dwóch miejscach.
--}}
<div id="a11y-panel" x-show="open" x-cloak role="region" aria-label="Ustawienia dostępności"
    class="border-b border-gray-200 bg-white text-xs text-gray-600">
    <div class="mx-auto flex max-w-[1400px] flex-wrap items-center gap-4 px-4 py-3">
        <div class="flex items-center gap-1.5" role="group" aria-label="Rozmiar czcionki">
            <button type="button" data-a11y-font="down"
                class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-label="Zmniejsz czcionkę">A-</button>
            <button type="button" data-a11y-font="reset"
                class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-label="Domyślny rozmiar">A</button>
            <button type="button" data-a11y-font="up"
                class="flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-label="Zwiększ czcionkę">A+</button>
        </div>

        <button type="button" data-a11y-ls
            class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
            aria-pressed="false" aria-label="Rozstrzał liter">
            <i class="fa-solid fa-text-width" aria-hidden="true"></i> Odstęp liter
        </button>

        <div role="group" aria-label="Tryb kontrastowy" class="flex items-center gap-2">
            <button type="button" data-a11y-contrast="contrast"
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Wysoki kontrast">
                <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> Kontrast
            </button>
            <button type="button" data-a11y-contrast="contrast-bw"
                class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Czarno-żółty">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-current text-[9px] font-black" aria-hidden="true" style="background:#000;color:#ff0">A</span> Czarny/żółty
            </button>
        </div>

        <button type="button" data-a11y-animations
            class="flex min-h-6 items-center gap-1 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
            aria-pressed="false" aria-label="Wyłącz animacje">
            <i class="fa-solid fa-film" aria-hidden="true"></i> Wyłącz animacje
        </button>

        <button type="button" data-a11y-reset class="flex min-h-6 items-center gap-1 text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1" aria-label="Przywróć domyślne ustawienia dostępności">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Resetuj
        </button>

        {{-- Prawa: newsletter + BIP (drugorzędne — nie zajmują stałego miejsca w nagłówku) --}}
        <div class="ml-auto flex flex-wrap items-center gap-3">
            @if ($siteSettings->substack_url)
                <a href="{{ $siteSettings->substack_url }}" target="_blank" rel="noopener"
                    class="flex items-center gap-1 text-muted hover:text-brand"
                    aria-label="Newsletter Substack">
                    <i class="bi bi-substack" aria-hidden="true"></i>
                    <span>Newsletter</span>
                </a>
            @endif

            @if ($siteSettings->show_topbar_bip && $siteSettings->bip_url)
                @php $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external'; @endphp
                <a href="{{ $bipIsExternal ? $siteSettings->bip_url : route('bip') }}"
                    @if ($bipIsExternal) target="_blank" rel="noopener" @endif
                    class="flex items-center gap-1 font-bold text-muted hover:text-brand"
                    aria-label="BIP">
                    <i class="fa-solid fa-landmark" aria-hidden="true"></i> BIP
                </a>
            @endif
        </div>
    </div>
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>
