{{--
    Slider ze zdjęć strony (zakładka „Galeria”): przycisk wstrzymania (WCAG 2.2.2), strzałki, kropki,
    zatrzymanie przy najechaniu/fokusie, bez autoodtwarzania przy ograniczonym ruchu.
    Używany w szablonie „Strona działu” i — po włączeniu „Slider ze zdjęć strony” — na każdej innej stronie.
    Dane: $slides (kolekcja zdjęć z image_url, alt, caption oraz opcjonalnie title, text, button_label, button_url), $label (nazwa do aria-label).
--}}
@if ($slides->isNotEmpty())
    <section class="mb-10" aria-roledescription="karuzela" aria-label="Zdjęcia: {{ $label }}"
        x-data="{
            i: 0, n: {{ $slides->count() }}, playing: {{ $slides->count() > 1 ? 'true' : 'false' }}, paused: false, timer: null,
            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.playing = false;
                this.timer = setInterval(() => { if (this.playing && ! this.paused) this.next(); }, 6000);
            },
            go(k) { this.i = (k + this.n) % this.n; },
            next() { this.go(this.i + 1); },
            prev() { this.go(this.i - 1); },
        }"
        @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false">
        <div class="relative overflow-hidden rounded-md bg-gray-100">
            @foreach ($slides as $k => $slide)
                <figure x-show="i === {{ $k }}" @if ($k > 0) x-cloak @endif
                    role="group" aria-roledescription="slajd" aria-label="{{ $k + 1 }} z {{ $slides->count() }}"
                    :aria-hidden="(i !== {{ $k }}).toString()">
                    <img src="{{ $slide->image_url }}" alt="{{ $slide->alt }}" class="aspect-[16/7] w-full object-cover" @if ($k > 0) loading="lazy" @endif>
                    @php $hasContent = method_exists($slide, 'hasSlideContent') && $slide->hasSlideContent(); @endphp
                    @if ($hasContent || $slide->caption)
                        {{-- Treść slajdu pod zdjęciem (nie na nim) — kontrast nie zależy od zdjęcia. --}}
                        <figcaption class="bg-ink px-5 py-4 text-white">
                            @if (filled($slide->title ?? null))
                                <p class="text-xl font-bold leading-snug">{{ $slide->title }}</p>
                            @endif
                            @if (filled($slide->text ?? null))
                                <p class="mt-1">{{ $slide->text }}</p>
                            @endif
                            @if ($slide->caption)
                                <p @class(['text-sm', 'mt-2' => $hasContent])>{{ $slide->caption }}</p>
                            @endif
                            @if (filled($slide->button_label ?? null) && filled($slide->button_url ?? null))
                                <a href="{{ $slide->button_url }}"
                                    class="mt-3 inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-4 py-2 font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                    {{ $slide->button_label }} <span aria-hidden="true">→</span>
                                </a>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
        @if ($slides->count() > 1)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="playing = ! playing" :aria-pressed="(! playing).toString()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md px-2 text-sm font-bold uppercase tracking-wide text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'" aria-hidden="true"></i>
                    <span x-text="playing ? 'Wstrzymaj' : 'Wznów'">Wstrzymaj</span>
                </button>
                <div class="flex items-center gap-1">
                    <button type="button" @click="prev()" class="flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Poprzedni slajd"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    @foreach ($slides as $k => $slide)
                        <button type="button" @click="go({{ $k }})" :aria-current="(i === {{ $k }}).toString()"
                            class="flex h-11 w-8 items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                            aria-label="Slajd {{ $k + 1 }} z {{ $slides->count() }}">
                            <span class="block h-3 w-3 rounded-full" :class="i === {{ $k }} ? 'bg-brand-dark' : 'bg-gray-400'" aria-hidden="true"></span>
                        </button>
                    @endforeach
                    <button type="button" @click="next()" class="flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Następny slajd"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                </div>
            </div>
        @endif
    </section>
@endif
