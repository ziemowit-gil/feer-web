{{-- Pasek dostępności — zawsze widoczny (bez zwijania), zgodnie z układem WRZOS. --}}
<div class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600" role="region" aria-label="Ustawienia dostępności">
    <div class="mx-auto flex max-w-[1400px] items-center gap-4 overflow-x-auto px-4 py-1.5">

        <div role="group" aria-label="Tryb kontrastowy" class="flex shrink-0 items-center gap-2">
            <span class="hidden font-bold sm:inline">kontrast</span>
            <button type="button" data-a11y-contrast="contrast"
                class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-pressed="false" aria-label="Wysoki kontrast">
                <i class="fa-solid fa-circle-half-stroke text-[11px]" aria-hidden="true"></i>
            </button>
            <button type="button" data-a11y-contrast="contrast-gray"
                class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-pressed="false" aria-label="Skala szarości">
                <i class="fa-solid fa-droplet-slash text-[11px]" aria-hidden="true"></i>
            </button>
            <button type="button" data-a11y-contrast="contrast-bw"
                class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-900 bg-gray-900 text-yellow-300 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-pressed="false" aria-label="Czarno-żółty">
                <span class="text-[10px] font-black" aria-hidden="true">A</span>
            </button>
        </div>

        <div class="flex shrink-0 items-center gap-2" role="group" aria-label="Rozmiar czcionki">
            <span class="hidden font-bold sm:inline">czcionka</span>
            <button type="button" data-a11y-font="down"
                class="flex h-6 min-w-6 items-center justify-center rounded-full border border-gray-300 px-1.5 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Zmniejsz czcionkę">
                <i class="fa-solid fa-minus text-[10px]" aria-hidden="true"></i>
            </button>
            <button type="button" data-a11y-font="up"
                class="flex h-6 min-w-6 items-center justify-center rounded-full border border-gray-300 px-1.5 hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Zwiększ czcionkę">
                <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
            </button>
            <button type="button" data-a11y-sans
                class="flex h-6 min-w-6 items-center justify-center rounded-full border border-gray-300 px-1.5 hover:border-brand hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-pressed="false" aria-label="Czytelniejsza czcionka">
                <i class="fa-solid fa-font text-[10px]" aria-hidden="true"></i>
            </button>
        </div>

        <button type="button" data-a11y-underline-links
            class="hidden shrink-0 items-center gap-1 hover:text-brand aria-pressed:font-bold aria-pressed:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:flex"
            aria-pressed="false" aria-label="Podkreśl linki">
            <i class="fa-solid fa-link text-[11px]" aria-hidden="true"></i>
        </button>

        <button type="button" data-a11y-animations
            class="hidden shrink-0 items-center gap-1 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:flex"
            aria-pressed="false" aria-label="Wyłącz animacje">
            <i class="fa-solid fa-film text-[11px]" aria-hidden="true"></i>
        </button>

        <button type="button" data-a11y-reset
            class="ml-auto flex shrink-0 items-center gap-1 text-gray-500 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
            aria-label="Przywróć domyślne ustawienia dostępności">
            <i class="fa-solid fa-rotate-left text-[11px]" aria-hidden="true"></i>
        </button>
    </div>
</div>
