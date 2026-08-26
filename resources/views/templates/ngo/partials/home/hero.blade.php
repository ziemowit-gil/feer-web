@if ($slides->isNotEmpty())
<section class="relative overflow-hidden bg-brand"
    aria-label="Slider strony głównej"
    x-data="ngoHeroSlider()"
    x-init="start()">

    {{-- Slides --}}
    <div class="relative min-h-[420px] md:min-h-[540px]">
        @foreach ($slides as $i => $slide)
        <div class="absolute inset-0 transition-opacity duration-700"
            :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
            aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
            :aria-hidden="current !== {{ $i }} ? 'true' : 'false'">

            @if ($slide->image_url)
                <img src="{{ $slide->image_url }}" alt="{{ $slide->image_alt ?? '' }}"
                    class="h-full w-full object-cover absolute inset-0">
            @endif
            {{-- Przesłona pod tekstem. Wcześniej gradient gasł do przezroczystości
                 mniej więcej w połowie kolumny tekstu, więc biały tekst lądował
                 na jasnym zdjęciu (WCAG 1.4.3). Nie gasimy go też do końca, bo
                 na węższych ekranach kolumna tekstu zajmuje większą część
                 szerokości. Najgorszy przypadek (całkiem białe zdjęcie): 4,7:1
                 przy prawej krawędzi, 8:1 w środku, 12:1 przy lewej. --}}
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/70 to-black/55" aria-hidden="true"></div>

            {{-- Text content --}}
            <div class="relative z-10 mx-auto flex h-full max-w-[1400px] flex-col justify-center px-4 py-16">
                <div class="max-w-2xl">
                    @if (!empty($slide->mission_text))
                        <p class="mb-4 text-sm font-bold uppercase tracking-widest text-white">
                            {{ $slide->mission_text }}
                        </p>
                    @endif
                    @if ($slide->title)
                        <h2 class="mb-3 text-3xl font-extrabold leading-tight text-white md:text-5xl">
                            {!! nl2br(e($slide->title)) !!}
                        </h2>
                    @endif
                    @if ($slide->text)
                        <p class="mb-6 text-base text-white md:text-lg">{{ $slide->text }}</p>
                    @endif
                    @if ($slide->button_url && $slide->button_label)
                        <a href="{{ $slide->button_url }}"
                            class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-2.5 text-sm font-extrabold text-white shadow transition hover:bg-brand-dark hover:shadow-md">
                            {{ $slide->button_label }}
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Dots navigation --}}
    @if ($slides->count() > 1)
    <div class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2" role="tablist" aria-label="Slajdy">
        @foreach ($slides as $i => $slide)
        <button type="button"
            role="tab"
            :aria-selected="current === {{ $i }}"
            :class="current === {{ $i }} ? 'bg-white w-6' : 'bg-white/50 w-2'"
            class="h-2 rounded-full transition-all duration-300 cursor-pointer"
            @click="current = {{ $i }}; reset()"
            aria-label="Slajd {{ $i + 1 }}">
        </button>
        @endforeach
    </div>
    @endif

</section>

<script>
function ngoHeroSlider() {
    return {
        current: 0,
        total: {{ $slides->count() }},
        timer: null,
        start() {
            if (this.total > 1) this.timer = setInterval(() => this.next(), 5000);
        },
        next() { this.current = (this.current + 1) % this.total; },
        reset() { clearInterval(this.timer); this.start(); },
    }
}
</script>
@endif
