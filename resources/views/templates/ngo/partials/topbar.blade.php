{{--
    Pasek ułatwień dostępu zwinięty do jednego przełącznika (wzorzec z szablonu
    "federation") — te same data-a11y-* co dotąd, ta sama logika w resources/js/app.js,
    tylko domyślnie schowane pod jednym przyciskiem. Stan zapamiętywany w localStorage.
--}}
<div x-data="{ open: (function () { try { return localStorage.getItem('a11y-panel-open') === '1' } catch (e) { return false } })() }"
     x-effect="(() => { try { localStorage.setItem('a11y-panel-open', open ? '1' : '0') } catch (e) {} })()">

    <div class="border-b border-gray-200 bg-white text-xs text-gray-600">
        <div class="mx-auto flex max-w-[1400px] items-center gap-4 overflow-x-auto px-4 py-2">

            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="a11y-panel"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-gray-300 text-ink transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                :class="open ? 'bg-brand border-brand text-white' : ''"
                aria-label="Ułatwienia dostępu">
                <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
            </button>

            {{-- Prawa: donate CTA + social --}}
            <div class="ml-auto flex shrink-0 items-center gap-3">
                @if ($siteSettings->substack_url)
                    <a href="{{ $siteSettings->substack_url }}" target="_blank" rel="noopener"
                        class="hidden items-center gap-1 text-muted hover:text-brand sm:flex"
                        aria-label="Newsletter Substack">
                        <i class="bi bi-substack" aria-hidden="true"></i>
                        <span>Newsletter</span>
                    </a>
                @endif

                @if ($siteSettings->show_topbar_bip && $siteSettings->bip_url)
                    @php $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external'; @endphp
                    <a href="{{ $bipIsExternal ? $siteSettings->bip_url : route('bip') }}"
                        @if ($bipIsExternal) target="_blank" rel="noopener" @endif
                        class="hidden items-center gap-1 font-bold text-muted hover:text-brand sm:flex"
                        aria-label="BIP">
                        <i class="fa-solid fa-landmark" aria-hidden="true"></i> BIP
                    </a>
                @endif

                @php
                    $supportUrl = $siteSettings->support_quick_transfer_url
                        ?: $siteSettings->support_buycoffee_url
                        ?: $siteSettings->support_wplacam_url;
                @endphp
                @if ($supportUrl)
                    <a href="{{ $supportUrl }}" target="_blank" rel="noopener"
                        class="hidden items-center gap-1.5 rounded-full border border-brand bg-brand-light px-3 py-1 text-xs font-bold text-brand transition hover:bg-brand hover:text-white sm:flex"
                        aria-label="Wesprzyj nas finansowo">
                        <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
                        Wesprzyj nas
                    </a>
                @else
                    <a href="{{ route('support.show') }}"
                        class="hidden items-center gap-1.5 rounded-full border border-brand bg-brand-light px-3 py-1 text-xs font-bold text-brand transition hover:bg-brand hover:text-white sm:flex"
                        aria-label="Wesprzyj nas">
                        <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
                        Wesprzyj nas
                    </a>
                @endif
            </div>
        </div>
    </div>

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
        </div>
    </div>
</div>

<noscript><style>[x-cloak] { display: block !important; }</style></noscript>
