{{--
    Szablon FEER — slajder na stronie głównej w jasnym, płaskim stylu: tekst po lewej na białym tle,
    zdjęcie slajdu po prawej w ramce (bez przesłony, więc kontrast tekstu nie zależy od zdjęcia).
    Slajdy leżą jeden na drugim (siatka), zmieniają się płynnie; ukryte są wyłączone z czytania i fokusu (inert).

    Dostępność (WCAG 2.2.2): automatyczna zmiana slajdów zatrzymuje się po najechaniu i fokusie oraz
    ma widoczny przycisk „Zatrzymaj / Wznów"; przy ustawieniu „ogranicz ruch" nie startuje wcale.
    Kontrast: ink #1D1D1A na bieli 16,9:1; przycisk — biały tekst na kolorze marki (≥ 4,5:1).
--}}
@if ($slides->isNotEmpty())
<section class="bg-white"
    aria-roledescription="karuzela" aria-label="Slider strony głównej"
    x-data="feerHeroSlider({{ $slides->count() }})" x-init="start()"
    @mouseenter="hover = true" @mouseleave="hover = false"
    @focusin="focus = true" @focusout="focus = false">

    <div class="mx-auto max-w-6xl px-4 py-10 md:py-14">
        <div class="grid">
            @foreach ($slides as $i => $slide)
                @php
                    // Slajd „misja" to obiekt bez pól zwykłego slajdu (HomeController) — czytamy je bezpiecznie.
                    $isMission = isset($slide->mission_text) && ! isset($slide->title);
                    $sImage    = $slide->image_url ?? $slide->mission_img_url ?? null;
                    $sTitle    = $isMission ? $slide->mission_text : ($slide->title ?? null);
                    $sKicker   = $isMission ? 'Nasza misja' : ($slide->mission_text ?? null);
                    $sText     = $slide->text ?? null;
                    $sBtnUrl   = $slide->button_url ?? null;
                    $sBtnLabel = $slide->button_label ?? null;
                    $sAlt      = $slide->image_alt ?? '';
                @endphp
                <div class="col-start-1 row-start-1 grid items-center gap-8 transition-opacity duration-500 {{ $sImage ? 'lg:grid-cols-[minmax(0,1fr)_28rem]' : '' }}"
                     :class="current === {{ $i }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
                     :inert="current !== {{ $i }}"
                     role="group" aria-roledescription="slajd" aria-label="{{ $i + 1 }} z {{ $slides->count() }}">
                    <div class="min-w-0">
                        @if ($sKicker)
                            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $sKicker }}</p>
                        @endif
                        @if ($sTitle)
                            <h2 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{!! nl2br(e($sTitle)) !!}</h2>
                        @endif
                        @if ($sText)
                            <p class="mb-6 max-w-2xl text-base leading-relaxed text-ink md:text-lg">{{ $sText }}</p>
                        @endif
                        @if ($sBtnUrl && $sBtnLabel)
                            <a href="{{ $sBtnUrl }}"
                               class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-6 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                {{ $sBtnLabel }}
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                    @if ($sImage)
                        <img src="{{ $sImage }}" alt="{{ $sAlt }}" @if ($i > 0) loading="lazy" @endif
                             class="aspect-[4/3] w-full rounded-lg object-cover ring-1 ring-gray-200">
                    @endif
                </div>
            @endforeach
        </div>

        @if ($slides->count() > 1)
            <div class="mt-6 flex items-center gap-4">
                <div class="flex items-center gap-2" role="tablist" aria-label="Wybór slajdu">
                    @foreach ($slides as $i => $slide)
                        <button type="button" role="tab" :aria-selected="current === {{ $i }}"
                                :class="current === {{ $i }} ? 'w-8 bg-brand-dark' : 'w-3 bg-gray-400 hover:bg-gray-500'"
                                class="h-3 rounded-sm transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                                @click="go({{ $i }})" aria-label="Slajd {{ $i + 1 }}"></button>
                    @endforeach
                </div>
                <button type="button" @click="toggle()" :aria-pressed="paused"
                        class="inline-flex min-h-9 items-center gap-2 rounded-md border border-gray-300 px-3 text-sm font-bold text-ink hover:border-brand hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i>
                    <span x-text="paused ? 'Wznów' : 'Zatrzymaj'">Zatrzymaj</span>
                </button>
            </div>
        @endif
    </div>
</section>

<script>
function feerHeroSlider(total) {
    return {
        current: 0, total, timer: null, paused: false, hover: false, focus: false,
        start() {
            if (total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.paused = total > 1; return; }
            this.timer = setInterval(() => { if (! this.paused && ! this.hover && ! this.focus) this.current = (this.current + 1) % this.total; }, 6000);
        },
        go(i) { this.current = i; },
        toggle() { this.paused = ! this.paused; },
    };
}
</script>
@endif
