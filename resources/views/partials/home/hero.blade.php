@if ($siteSettings->isModuleEnabled('hero') && $slides->isNotEmpty())
<section class="relative overflow-hidden bg-ink" data-hero-slider role="region" aria-roledescription="karuzela" aria-label="Wyróżnione treści">
    <div class="relative h-[320px] md:h-[440px]">
        @foreach ($slides as $index => $slide)
            <div
                class="absolute inset-0 flex items-end bg-cover bg-center transition-opacity duration-700 motion-reduce:transition-none {{ $index === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
                style="background-image: linear-gradient(0deg, rgba(0,0,0,.65), rgba(0,0,0,.15)), url('{{ $slide->image_url }}')"
                data-hero-slide
                @if ($index !== 0) aria-hidden="true" @endif
            >
                <div class="mx-auto w-full max-w-6xl px-4 py-8 text-white">
                    <h1 class="max-w-xl text-2xl font-bold leading-tight md:text-4xl">{{ $slide->title }}</h1>
                    <p class="mt-2 max-w-lg text-sm text-white/85 md:text-base">{{ $slide->text }}</p>
                    @if ($slide->button_label && $slide->button_url)
                        <a href="{{ $slide->button_url }}" data-hero-cta {{ $index !== 0 ? 'tabindex=-1' : '' }}
                            class="mt-4 inline-block rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                            {{ $slide->button_label }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" data-hero-prev class="absolute left-3 top-1/2 flex min-h-[36px] min-w-[36px] -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50" aria-label="Poprzedni slajd">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button type="button" data-hero-next class="absolute right-3 top-1/2 flex min-h-[36px] min-w-[36px] -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50" aria-label="Następny slajd">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>

    <div class="absolute bottom-3 right-4 flex items-center gap-2 rounded-full bg-black/30 px-3 py-1 text-xs text-white">
        <button type="button" data-hero-toggle class="flex min-h-6 min-w-6 items-center justify-center" aria-label="Wstrzymaj automatyczną zmianę slajdów" aria-pressed="false">
            <i class="fa-solid fa-pause" data-hero-toggle-icon aria-hidden="true"></i>
        </button>
        <span aria-live="polite"><span data-hero-counter>1</span>/{{ count($slides) }}</span>
    </div>
</section>
@endif
