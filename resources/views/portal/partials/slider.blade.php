{{--
    Kompaktowy slider portalu (kolumna obok menu): zdjęcie 16:9 na górze, pod nim tytuł, opis i przycisk
    na jasnym tle — kontrast tekstu nie zależy od zdjęcia. Expects: $slides (tytuł, text, image_url, image_alt,
    button_label, button_url), $label, $duration (s).

    WCAG 2.2.2: widoczne „Zatrzymaj / Wznów”, pauza po najechaniu i fokusie, przy „ogranicz ruch” bez autoodtwarzania.
    Ukryte slajdy są inert (bez fokusu i czytania).
--}}
@php $count = $slides->count(); @endphp
<section class="portal-slider overflow-hidden rounded-md bg-gray-50" aria-roledescription="karuzela" aria-label="{{ $label }}"
    x-data="{
        i: 0, n: {{ $count }}, paused: false, hover: false, focus: false, timer: null,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        go(k) { this.i = (k + this.n) % this.n; },
        start() {
            if (this.n < 2) return;
            if (this.reduced) this.paused = true;
            this.timer = setInterval(() => { if (! this.paused && ! this.hover && ! this.focus) this.go(this.i + 1); }, {{ max(3, (int) ($duration ?: 7)) * 1000 }});
        },
    }" x-init="start()" @mouseenter="hover = true" @mouseleave="hover = false" @focusin="focus = true" @focusout="focus = false">
    <div class="grid">
        @foreach ($slides as $k => $slide)
            <div class="flex flex-col transition-opacity duration-300 motion-reduce:transition-none" style="grid-area: 1 / 1"
                role="group" aria-roledescription="slajd" aria-label="{{ $k + 1 }} z {{ $count }}"
                :class="i === {{ $k }} ? 'opacity-100' : 'pointer-events-none opacity-0'" :inert="i !== {{ $k }}" @if ($k > 0) inert @endif>
                @if ($slide->image_url)
                    <img src="{{ $slide->image_url }}" alt="{{ $slide->image_alt ?? '' }}" class="aspect-[16/9] w-full object-cover" @if ($k > 0) loading="lazy" @endif>
                @endif
                <div class="flex flex-1 flex-col p-5 md:p-6">
                    @if ($slide->title)
                        <p class="text-xl font-bold leading-snug text-ink md:text-2xl">{{ $slide->title }}</p>
                    @endif
                    @if ($slide->text)
                        <p class="mt-2 line-clamp-3 leading-relaxed text-ink">{{ strip_tags((string) $slide->text) }}</p>
                    @endif
                    @if ($slide->button_url)
                        <p class="mt-4">
                            <a href="{{ $slide->button_url }}" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                {{ $slide->button_label ?: 'Zobacz' }}<span class="sr-only">: {{ $slide->title }}</span> <span aria-hidden="true">→</span>
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($count > 1)
        <div class="flex items-center gap-2 border-t border-gray-200 px-5 py-2.5 md:px-6">
            <button type="button" @click="paused = ! paused" :aria-pressed="paused.toString()" class="inline-flex min-h-10 items-center gap-2 rounded-md px-3 text-sm font-bold text-ink hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i><span x-text="paused ? 'Wznów' : 'Zatrzymaj'">Zatrzymaj</span>
            </button>
            <span class="ml-auto text-sm text-ink" aria-live="polite"><span x-text="i + 1">1</span> z {{ $count }}</span>
            <button type="button" @click="go(i - 1)" class="flex h-10 w-10 items-center justify-center rounded-md text-ink hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Poprzedni slajd"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" @click="go(i + 1)" class="flex h-10 w-10 items-center justify-center rounded-md text-ink hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Następny slajd"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    @endif
</section>
