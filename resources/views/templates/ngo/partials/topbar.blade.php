<div class="border-b border-gray-200 bg-white text-xs text-gray-600" role="region" aria-label="Ustawienia dostępności">
    <div class="mx-auto flex max-w-[1400px] items-center gap-4 overflow-x-auto px-4 py-2">

        {{-- Lewa: kontrolki dostępności --}}
        <div class="flex shrink-0 items-center gap-4">
            <div class="flex items-center gap-1.5" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="down"
                    class="ngo-a11y flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand"
                    aria-label="Zmniejsz czcionkę">A-</button>
                <button type="button" data-a11y-font="reset"
                    class="ngo-a11y flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand"
                    aria-label="Domyślny rozmiar">A</button>
                <button type="button" data-a11y-font="up"
                    class="ngo-a11y flex min-h-6 min-w-6 items-center justify-center rounded border border-gray-300 hover:border-brand hover:text-brand"
                    aria-label="Zwiększ czcionkę">A+</button>
            </div>

            <button type="button" data-a11y-ls
                class="ngo-a11y flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand"
                aria-pressed="false" aria-label="Rozstrzał liter">
                <i class="fa-solid fa-text-width" aria-hidden="true"></i>
                <span class="hidden sm:inline">Odstęp</span>
            </button>

            <div role="group" aria-label="Tryb kontrastowy" class="flex items-center gap-2">
                <button type="button" data-a11y-contrast="contrast"
                    class="ngo-a11y flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Wysoki kontrast">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Kontrast</span>
                </button>
                <button type="button" data-a11y-contrast="contrast-bw"
                    class="ngo-a11y flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand"
                    aria-pressed="false" aria-label="Czarno-żółty">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-current text-[9px] font-black" aria-hidden="true" style="background:#000;color:#ff0">A</span>
                </button>
            </div>

            <button type="button" data-a11y-animations
                class="ngo-a11y flex min-h-6 items-center gap-1 hover:text-brand"
                aria-pressed="false" aria-label="Wyłącz animacje">
                <i class="fa-solid fa-film" aria-hidden="true"></i>
            </button>
        </div>

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
