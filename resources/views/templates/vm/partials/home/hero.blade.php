{{--
    Slider szablonu "vm": zdjęcie na całą szerokość, na desktopie z kartą
    w kolorze marki po prawej (na telefonie karta pod zdjęciem). Strzałki,
    kropki i przycisk pauzy.

    WCAG: autoodtwarzanie da się zatrzymać (2.2.2) i jest wyłączone przy
    prefers-reduced-motion; nieaktywne slajdy są inert (brak fokusu w ukrytych
    linkach); karta ma biały tekst na kolorze marki (kolor marki pilnuje
    kontrastu przy zapisie ustawień).
--}}
@if ($slides->isNotEmpty())
@php
    $vmSlides = $slides->values()->map(fn ($slide) => (object) [
        'title'  => $slide->title ?? $slide->site_name ?? null,
        'text'   => $slide->text ?? $slide->mission_text ?? null,
        'image'  => $slide->image_url ?? $slide->mission_img_url ?? null,
        'alt'    => $slide->image_alt ?? '',
        'url'    => $slide->button_url ?? null,
        'label'  => $slide->button_label ?? null,
    ]);
@endphp
<section class="relative bg-gradient-to-r from-brand to-brand-dark"
    aria-roledescription="karuzela" aria-label="Najważniejsze informacje"
    x-data="vmHeroSlider({{ $vmSlides->count() }})" x-init="start()"
    @mouseenter="hover = true" @mouseleave="hover = false"
    @focusin="hover = true" @focusout="hover = false">

    <div class="relative">
        @foreach ($vmSlides as $i => $slide)
            <div x-show="current === {{ $i }}" @if ($i > 0) style="display: none" @endif
                 role="group" aria-roledescription="slajd" aria-label="{{ $i + 1 }} z {{ $vmSlides->count() }}"
                 :inert="current !== {{ $i }}">
                <div class="relative lg:h-[480px]">
                    <div class="relative h-[240px] overflow-hidden sm:h-[340px] lg:absolute lg:inset-0 lg:h-full">
                        @if ($slide->image)
                            <img src="{{ $slide->image }}" alt="{{ $slide->alt }}" class="h-full w-full object-cover"
                                 @if ($i > 0) loading="lazy" @endif>
                        @endif
                    </div>

                    @if ($slide->title || $slide->text)
                        <div class="relative bg-brand px-6 py-6 text-white lg:absolute lg:right-[8%] lg:top-1/2 lg:w-[420px] lg:-translate-y-1/2 lg:rounded-2xl lg:p-8 lg:shadow-xl">
                            @if ($slide->title)
                                <h2 class="vm-display mb-3 text-xl leading-tight lg:text-2xl">{!! nl2br(e($slide->title)) !!}</h2>
                            @endif
                            @if ($slide->text)
                                <p class="mb-5 font-semibold leading-relaxed">{{ $slide->text }}</p>
                            @endif
                            @if ($slide->url && $slide->label)
                                <a href="{{ $slide->url }}"
                                   class="vm-display inline-flex min-h-11 items-center rounded-2xl border-2 border-white px-5 text-xs transition hover:bg-white hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand">
                                    {{ $slide->label }}<span class="sr-only">: {{ $slide->title }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($vmSlides->count() > 1)
        {{-- Strzałki — nad zdjęciem (na telefonie w jego pionowym środku). --}}
        <button type="button" @click="prev()"
            class="absolute left-3 top-[120px] z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center border-2 border-white bg-black/30 text-white transition hover:bg-black/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:top-[170px] lg:top-1/2"
            aria-label="Poprzedni slajd">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" @click="next()"
            class="absolute right-3 top-[120px] z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center border-2 border-white bg-black/30 text-white transition hover:bg-black/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white sm:top-[170px] lg:top-1/2"
            aria-label="Następny slajd">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <div class="absolute left-0 right-0 top-[200px] z-10 flex items-center justify-center gap-1 sm:top-[300px] lg:bottom-3 lg:top-auto">
            @foreach ($vmSlides as $i => $slide)
                <button type="button" @click="go({{ $i }})"
                    class="flex h-6 w-6 items-center justify-center rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    :aria-current="current === {{ $i }} ? 'true' : 'false'"
                    aria-label="Pokaż slajd {{ $i + 1 }}{{ $slide->title ? ': ' . $slide->title : '' }}">
                    <span class="block h-3.5 w-3.5 rounded-full border-2 border-white shadow"
                          :class="current === {{ $i }} ? 'bg-white' : 'bg-black/20'" aria-hidden="true"></span>
                </button>
            @endforeach
            <button type="button" @click="toggle()"
                class="ml-1 flex h-6 w-6 items-center justify-center rounded text-white drop-shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                :aria-label="paused ? 'Wznów automatyczne przewijanie' : 'Zatrzymaj automatyczne przewijanie'">
                <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i>
            </button>
        </div>
    @endif
</section>

<script>
function vmHeroSlider(total) {
    return {
        current: 0,
        total,
        timer: null,
        paused: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        hover: false,
        start() {
            if (this.total < 2) return;
            this.timer = setInterval(() => { if (! this.paused && ! this.hover) this.next(); }, 6000);
        },
        next() { this.current = (this.current + 1) % this.total; },
        prev() { this.current = (this.current - 1 + this.total) % this.total; },
        go(i) { this.current = i; },
        toggle() { this.paused = ! this.paused; },
    }
}
</script>
@endif
