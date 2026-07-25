@if ($siteSettings->isModuleEnabled('gallery') && $gallery->isNotEmpty())
<section id="galeria" class="mx-auto max-w-6xl px-4 py-12">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-2xl font-bold text-ink">Galeria</h2>
        <a href="#" class="rounded bg-brand px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-brand-dark">Zobacz wszystkie</a>
    </div>

    <div class="relative">
        <div class="flex gap-4 overflow-x-auto scroll-smooth pb-2" data-gallery-track>
            @foreach ($gallery as $photo)
                <img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" class="h-40 w-56 flex-none rounded-lg object-cover">
            @endforeach
        </div>

        <button type="button" data-gallery-prev class="absolute -left-4 top-1/2 hidden min-h-6 min-w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white p-2 shadow md:flex" aria-label="Przewiń galerię w lewo">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" data-gallery-next class="absolute -right-4 top-1/2 hidden min-h-6 min-w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white p-2 shadow md:flex" aria-label="Przewiń galerię w prawo">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
</section>
@endif
