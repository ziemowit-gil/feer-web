@if ($siteSettings->isModuleEnabled('gallery') && $gallery->isNotEmpty())
<section id="galeria" class="mx-auto max-w-6xl px-4 py-12" aria-labelledby="home-gallery-heading">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 id="home-gallery-heading" class="text-2xl font-bold text-ink">Galeria</h2>
        <a href="#" class="inline-flex min-h-11 items-center rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zobacz wszystkie</a>
    </div>

    <div class="relative">
        <div class="flex gap-4 overflow-x-auto scroll-smooth pb-2" data-gallery-track>
            @foreach ($gallery as $photo)
                <img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" class="h-40 w-56 flex-none rounded-md object-cover">
            @endforeach
        </div>

        {{-- Przyciski przewijania: płaskie, ciemne pole z białą ikoną (kontrast niezależny od zdjęć). --}}
        <button type="button" data-gallery-prev class="absolute -left-4 top-1/2 hidden min-h-11 min-w-11 -translate-y-1/2 items-center justify-center rounded-md bg-ink p-2 text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 md:flex" aria-label="Przewiń galerię w lewo">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" data-gallery-next class="absolute -right-4 top-1/2 hidden min-h-11 min-w-11 -translate-y-1/2 items-center justify-center rounded-md bg-ink p-2 text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 md:flex" aria-label="Przewiń galerię w prawo">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
</section>
@endif
