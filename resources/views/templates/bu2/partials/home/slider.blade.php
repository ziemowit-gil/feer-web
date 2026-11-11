{{--
    Slider strony głównej BU2: po lewej niebieski panel (tytuł, tekst, przycisk), po prawej zdjęcie slajdu (ozdobne,
    alt=""), w rogu przyciski: poprzedni, wstrzymaj/wznów, następny. Przewija się sam co 7 s, ale nie przy „ograniczonym
    ruchu” w systemie ani po wyłączeniu animacji w panelu dostępności; staje, gdy kursor lub fokus jest na sliderze
    (WCAG 2.2.2). Bez JS slajdy są jeden pod drugim. Dane: $slides (slajdy strony głównej; slajd misji ma mission_text).
--}}
@php $slides = collect($slides ?? []); @endphp
@if ($slides->isNotEmpty())
    @php $count = $slides->count(); @endphp
    <section class="bu2-slider" aria-roledescription="karuzela" aria-label="Wyróżnione"
             x-data="{
                i: 0, n: {{ $count }}, playing: true, held: false, t: null,
                init() {
                    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('no-animations');
                    if (calm || this.n < 2) this.playing = false;
                    window.addEventListener('a11y-animations-changed', () => { this.playing = false; this.run() });
                    this.run();
                },
                run() { clearInterval(this.t); if (this.playing && ! this.held && this.n > 1) this.t = setInterval(() => this.go(1), 7000) },
                go(d) { this.i = (this.i + d + this.n) % this.n },
                toggle() { this.playing = ! this.playing; this.run() },
                hold(on) { this.held = on; this.run() },
             }"
             @mouseenter="hold(true)" @mouseleave="hold(false)" @focusin="hold(true)" @focusout="hold(false)">
        <div :aria-live="playing ? 'off' : 'polite'">
            @foreach ($slides as $k => $slide)
                @php
                    $isMission = ! empty($slide->mission_text);
                    $title = $isMission ? null : trim((string) ($slide->title ?? ''));
                    $text = $isMission ? $slide->mission_text : trim((string) ($slide->text ?? ''));
                    $img = $isMission ? ($slide->mission_img_url ?? null) : ($slide->image_url ?? null);
                    $btnUrl = $isMission ? null : ($slide->button_url ?? null);
                    $btnLabel = trim((string) ($slide->button_label ?? '')) ?: 'Więcej';
                @endphp
                <div class="bu2-slide" role="group" aria-roledescription="slajd" aria-label="{{ $k + 1 }} z {{ $count }}"
                     @if ($k > 0) x-cloak @endif x-show="i === {{ $k }}">
                    <div class="bu2-slide-panel bu2-on-blue">
                        @if ($title)<h2>{{ $title }}</h2>@endif
                        @if ($text !== '')<p>{{ $text }}</p>@endif
                        @if ($btnUrl)
                            <a class="bu2-btn-ghost" href="{{ $btnUrl }}">{{ $btnLabel }}@if ($title)<span class="bu2-sr">: {{ $title }}</span>@endif</a>
                        @endif
                    </div>
                    <div class="bu2-slide-img">
                        @if ($img)<img src="{{ $img }}" alt="" @if ($k > 0) loading="lazy" @endif>@endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($count > 1)
            <div class="bu2-slider-ctrl">
                <button type="button" @click="go(-1)" aria-label="Poprzedni slajd">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.4 4.6 8 12l7.4 7.4L14 20.8 5.2 12 14 3.2z"/></svg>
                </button>
                <button type="button" @click="toggle()" aria-label="Wstrzymaj przewijanie slajdów" :aria-label="playing ? 'Wstrzymaj przewijanie slajdów' : 'Wznów przewijanie slajdów'">
                    <svg x-show="playing" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zm6.5 0H17v14h-3.5z"/></svg>
                    <svg x-show="! playing" x-cloak viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button type="button" @click="go(1)" aria-label="Następny slajd">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.6 19.4 16 12 8.6 4.6 10 3.2l8.8 8.8-8.8 8.8z"/></svg>
                </button>
            </div>
        @endif
    </section>
@endif
