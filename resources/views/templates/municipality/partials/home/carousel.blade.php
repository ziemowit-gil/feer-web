{{--
    Sekcja: Polecamy — karuzela logotypów partnerów.
    Dane: $partners (kolekcja Partner).
--}}
@if ($siteSettings->isModuleEnabled('partners') && $partners->isNotEmpty())
<section id="polecamy" class="bg-white py-10" aria-label="Polecamy">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 class="mb-6 text-center text-3xl font-extrabold text-ink">
            {{ $siteSettings->municipality_carousel_title ?: 'Polecamy' }}
        </h2>

        <div x-data="munCarousel()" class="relative">

            {{-- Przycisk pauzy --}}
            <button type="button" @click="togglePause()"
                class="mb-4 flex h-9 w-9 items-center justify-center rounded bg-[#e53935] text-white transition hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#e53935]"
                :aria-label="paused ? 'Wznów przewijanie' : 'Zatrzymaj przewijanie'"
                :title="paused ? 'Wznów' : 'Pauza'">
                <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i>
            </button>

            <div class="relative overflow-hidden" aria-live="polite" aria-atomic="true">
                {{-- Track --}}
                <div class="flex items-center gap-12 transition-transform duration-700 ease-in-out"
                     :style="{ transform: 'translateX(' + offset + 'px)' }"
                     role="list"
                     aria-label="Logotypy partnerów">
                    @foreach ($partners as $partner)
                        <div class="flex-none" role="listitem" style="width: 180px">
                            @if ($partner->url)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                                   class="block opacity-80 grayscale transition hover:opacity-100 hover:grayscale-0 focus-visible:outline-2 focus-visible:outline-brand"
                                   title="{{ 'Kliknij aby przejść do ' . $partner->name . '. Strona otwiera się w nowej karcie.' }}"
                                   aria-label="{{ $partner->name }} (otwiera się w nowej karcie)">
                                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}"
                                        class="h-14 w-full object-contain">
                                </a>
                            @else
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}"
                                    class="h-14 w-full object-contain opacity-70 grayscale">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Prev / Next --}}
            <button type="button" @click="prev()"
                class="absolute left-0 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-600 transition hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-brand"
                aria-label="Poprzedni">
                <i class="fa-solid fa-chevron-left text-sm" aria-hidden="true"></i>
            </button>
            <button type="button" @click="next()"
                class="absolute right-0 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-600 transition hover:bg-brand hover:text-white focus-visible:outline-2 focus-visible:outline-brand"
                aria-label="Następny">
                <i class="fa-solid fa-chevron-right text-sm" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('munCarousel', function () {
        return {
            offset: 0,
            step: 192,  // 180px kafelek + 12px gap
            paused: false,
            timer: null,
            total: {{ $partners->count() }},
            position: 0,
            init() { this.start(); },
            start() {
                if (this.total <= 4) return;
                this.timer = setInterval(() => this.next(), 3500);
            },
            next() {
                if (this.position >= this.total - 4) { this.position = 0; }
                else { this.position++; }
                this.offset = -this.position * this.step;
            },
            prev() {
                if (this.position <= 0) { this.position = Math.max(0, this.total - 4); }
                else { this.position--; }
                this.offset = -this.position * this.step;
            },
            togglePause() {
                this.paused = !this.paused;
                if (this.paused) { clearInterval(this.timer); }
                else { this.start(); }
            }
        };
    });
});
</script>
@endif
