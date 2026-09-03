@if ($partners->isNotEmpty())
@php
    $carouselTitle = $siteSettings->municipality_carousel_title ?: 'Nasi partnerzy';
@endphp
<section class="border-t border-gray-100 py-10" aria-labelledby="ngo-carousel-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        <h2 id="ngo-carousel-heading" class="mb-6 text-center text-sm font-extrabold uppercase tracking-widest text-muted">
            {{ $carouselTitle }}
        </h2>

        <div class="relative" x-data="ngoCarousel()" x-init="start()">
            <div class="overflow-hidden">
                <div class="flex gap-6 transition-transform duration-500"
                    :style="`transform: translateX(-${offset}px)`">
                    @foreach ($partners as $partner)
                    <a href="{{ $partner->url ?: '#' }}"
                        @if ($partner->url) target="_blank" rel="noopener" @endif
                        class="flex-none w-[140px] flex items-center justify-center rounded-xl border border-gray-100 bg-white p-4 grayscale transition hover:grayscale-0 hover:shadow"
                        aria-label="{{ $partner->name }}">
                        @if ($partner->logo_url)
                            <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}"
                                class="max-h-12 w-full object-contain">
                        @else
                            <span class="text-xs font-semibold text-gray-500 text-center">{{ $partner->name }}</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Prev/Next arrows --}}
            <button type="button"
                class="absolute -left-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-white shadow ring-1 ring-gray-200 transition hover:ring-brand"
                @click="prev()" aria-label="Poprzedni">
                <i class="fa-solid fa-chevron-left text-xs text-gray-500" aria-hidden="true"></i>
            </button>
            <button type="button"
                class="absolute -right-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-white shadow ring-1 ring-gray-200 transition hover:ring-brand"
                @click="next()" aria-label="Następny">
                <i class="fa-solid fa-chevron-right text-xs text-gray-500" aria-hidden="true"></i>
            </button>
        </div>

    </div>
</section>

<script>
function ngoCarousel() {
    return {
        offset: 0,
        step: 152,
        max: {{ $partners->count() }} * 152,
        visible: Math.floor((window.innerWidth < 768 ? window.innerWidth : 1300) / 152),
        timer: null,
        start() {
            this.timer = setInterval(() => this.next(), 3500);
        },
        next() {
            this.offset += this.step;
            const maxOffset = ({{ $partners->count() }} - this.visible) * this.step;
            if (this.offset > maxOffset) this.offset = 0;
        },
        prev() {
            this.offset -= this.step;
            if (this.offset < 0) {
                this.offset = Math.max(0, ({{ $partners->count() }} - this.visible) * this.step);
            }
        },
    }
}
</script>
@endif
