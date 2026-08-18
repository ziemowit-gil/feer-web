{{--
    Sekcja: Hero (slider) + boczny panel aktualności.
    Dane: $slides (kolekcja HeroSlide), $newsSidebar (kolekcja News, max 3).
--}}
@if ($siteSettings->isModuleEnabled('hero') || $siteSettings->isModuleEnabled('news'))
<section id="hero-split" class="bg-white" aria-label="Aktualności i slider">
    <div class="mx-auto max-w-[1400px] px-0 md:flex md:items-stretch">

        {{-- SLIDER (lewo) --}}
        <div class="relative min-h-72 flex-1 overflow-hidden bg-gray-900 md:min-h-[420px]"
             x-data="munHeroSlider()"
             role="region" aria-label="Slider — aktualności"
             aria-roledescription="carousel">

            @forelse ($slides as $i => $slide)
                <div
                    class="absolute inset-0 transition-opacity duration-700"
                    :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    role="group"
                    aria-roledescription="slide"
                    aria-label="Slajd {{ $i + 1 }} z {{ count($slides) }}"
                    :aria-hidden="current !== {{ $i }}">

                    @if (!empty($slide->mission_text))
                        {{-- Slajd misji --}}
                        <div class="flex h-full flex-col items-center justify-center p-10 text-center
                            {{ ($slide->mission_bg ?? '') === 'dark' ? 'bg-gray-900 text-white' : 'bg-brand text-white' }}">
                            @if (!empty($slide->logo_url))
                                <img src="{{ $slide->logo_url }}" alt="{{ $slide->site_name ?? '' }}" class="mb-6 h-20 w-auto object-contain">
                            @endif
                            <p class="text-xl font-medium leading-relaxed md:text-2xl">{{ $slide->mission_text }}</p>
                        </div>
                    @else
                        @if ($slide->image_url)
                            <img src="{{ $slide->image_url }}" alt="{{ $slide->title ?? '' }}"
                                class="h-full w-full object-cover">
                        @endif

                        {{-- Overlay z tytułem --}}
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 md:p-8">
                            <h2 class="text-2xl font-extrabold leading-tight text-white drop-shadow md:text-3xl">
                                @if (!empty($slide->button_url))
                                    <a href="{{ $slide->button_url }}" class="hover:underline">{{ $slide->title }}</a>
                                @else
                                    {{ $slide->title }}
                                @endif
                            </h2>
                            @if (!empty($slide->text))
                                <p class="mt-1 text-sm text-white/80 line-clamp-2">{{ $slide->text }}</p>
                            @endif
                            @if (!empty($slide->button_label) && !empty($slide->button_url))
                                <a href="{{ $slide->button_url }}"
                                    class="mt-3 inline-flex items-center gap-1.5 rounded bg-[#e53935] px-4 py-1.5 text-xs font-bold text-white hover:bg-red-700 transition">
                                    {{ $slide->button_label }}
                                    <i class="fa-solid fa-arrow-right text-[0.6rem]" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="flex h-full items-center justify-center text-white/40">
                    <span>Brak slajdów</span>
                </div>
            @endforelse

            @if (count($slides) > 1)
                {{-- Przyciski --}}
                <button type="button" @click="prev()"
                    class="absolute left-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition focus-visible:outline-2 focus-visible:outline-white"
                    aria-label="Poprzedni slajd">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button type="button" @click="next()"
                    class="absolute right-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition focus-visible:outline-2 focus-visible:outline-white"
                    aria-label="Następny slajd">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>

                {{-- Wskaźniki --}}
                <div class="absolute bottom-3 right-6 z-20 flex gap-2" role="tablist" aria-label="Slajdy">
                    @foreach ($slides as $i => $slide)
                        <button type="button"
                            @click="goTo({{ $i }})"
                            :class="current === {{ $i }} ? 'bg-[#e53935] w-6' : 'bg-white/50 w-2.5'"
                            class="h-2.5 rounded-full transition-all duration-300 focus-visible:outline-2 focus-visible:outline-white"
                            role="tab"
                            :aria-selected="current === {{ $i }}"
                            aria-label="Slajd {{ $i + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- PANEL AKTUALNOŚCI (prawo) --}}
        @if ($siteSettings->isModuleEnabled('news'))
        <div class="flex w-full flex-col bg-white md:w-80 lg:w-96">
            <ul class="divide-y divide-gray-100 flex-1" role="list" aria-label="Najnowsze aktualności">
                @forelse ($newsSidebar as $item)
                    <li>
                        <a href="{{ route('news.show', $item) }}"
                            class="group flex items-stretch gap-3 p-4 hover:bg-gray-50 transition">
                            @if ($img = $item->imageUrlOrDefault())
                                <div class="h-16 w-20 flex-none overflow-hidden rounded">
                                    <img src="{{ $img }}" alt="" class="h-full w-full object-cover group-hover:scale-105 transition">
                                </div>
                            @endif
                            <div class="flex flex-col justify-center min-w-0">
                                <div class="flex items-center gap-1.5 text-[0.65rem] font-medium text-muted">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <time datetime="{{ $item->published_at->toDateString() }}">
                                        {{ $item->published_at->format('d - m - Y') }}
                                    </time>
                                </div>
                                <p class="mt-0.5 text-sm font-bold text-ink line-clamp-2 group-hover:text-brand transition">
                                    {{ $item->title }}
                                </p>
                                <span class="mt-1 text-[0.65rem] font-bold text-brand" aria-hidden="true">&rsaquo;</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="p-6 text-sm text-muted">Brak aktualności.</li>
                @endforelse
            </ul>

            <div class="border-t border-gray-100 p-4">
                <a href="{{ route('news.index') }}"
                   class="block w-full rounded border-2 border-[#e53935] px-4 py-2.5 text-center text-xs font-extrabold uppercase tracking-widest text-[#e53935] transition hover:bg-[#e53935] hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#e53935]">
                    Zobacz wszystkie aktualności
                </a>
            </div>
        </div>
        @endif

    </div>
</section>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('munHeroSlider', function () {
        return {
            current: 0,
            total: {{ count($slides) }},
            timer: null,
            init() {
                if (this.total > 1) {
                    this.timer = setInterval(() => this.next(), 5000);
                }
            },
            next() { this.goTo((this.current + 1) % this.total); },
            prev() { this.goTo((this.current - 1 + this.total) % this.total); },
            goTo(i) {
                this.current = i;
                clearInterval(this.timer);
                this.timer = setInterval(() => this.next(), 5000);
            }
        };
    });
});
</script>
@endif
