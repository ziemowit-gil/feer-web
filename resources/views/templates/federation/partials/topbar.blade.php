<div class="border-b border-gray-100 bg-white text-xs text-gray-600" role="region" aria-label="Ustawienia dostępności">
    <div class="mx-auto flex max-w-[1400px] items-center gap-4 overflow-x-auto px-4 py-2">

        {{-- Lewa: kontrolki dostępności --}}
        <div class="flex shrink-0 items-center gap-4">
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
                <i class="fa-solid fa-text-width" aria-hidden="true"></i>
                <span class="hidden sm:inline">Odstęp</span>
            </button>

            <div role="group" aria-label="Tryb kontrastowy" class="flex items-center gap-2">
                <button type="button" data-a11y-contrast="contrast"
                    class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-pressed="false" aria-label="Wysoki kontrast">
                    <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Kontrast</span>
                </button>
                <button type="button" data-a11y-contrast="contrast-bw"
                    class="flex min-h-6 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                    aria-pressed="false" aria-label="Czarno-żółty">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-current text-[9px] font-black" aria-hidden="true" style="background:#000;color:#ff0">A</span>
                </button>
            </div>

            <button type="button" data-a11y-animations
                class="flex min-h-6 items-center gap-1 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
                aria-pressed="false" aria-label="Wyłącz animacje">
                <i class="fa-solid fa-film" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Prawa: social + BIP --}}
        <div class="ml-auto flex shrink-0 items-center gap-3">
            <div class="hidden items-center gap-2 sm:flex" role="list" aria-label="Media społecznościowe">
                @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $key => $info)
                    @php $url = $siteSettings->{$key.'_url'} ?? null; @endphp
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                            class="flex h-7 w-7 items-center justify-center rounded-full text-muted transition hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            aria-label="{{ $info['label'] }}" role="listitem">
                            <i class="{{ $info['icon'] }}" aria-hidden="true"></i>
                        </a>
                    @endif
                @endforeach
            </div>

            @if ($siteSettings->show_topbar_bip && $siteSettings->bip_url)
                @php $bipIsExternal = ($siteSettings->bip_mode ?? 'internal') === 'external'; @endphp
                <a href="{{ $bipIsExternal ? $siteSettings->bip_url : route('bip') }}"
                    @if ($bipIsExternal) target="_blank" rel="noopener" @endif
                    class="hidden items-center gap-1 font-bold text-muted hover:text-brand sm:flex focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Biuletyn Informacji Publicznej">
                    <i class="fa-solid fa-landmark" aria-hidden="true"></i> BIP
                </a>
            @endif
        </div>
    </div>
</div>
