{{--
    Galeria zdjęć podstrony — pokazywana, gdy zaznaczono „Pokaż galerię zdjęć"
    i strona ma zdjęcia. Nie dotyczy typu „O organizacji" (ma własną galerię).
--}}
@php
    $galleryImages = ($page->show_gallery ?? false) && ! $page->isAbout()
        ? $page->images->filter(fn ($i) => $i->image_url)->values()
        : collect();
@endphp

@if ($galleryImages->isNotEmpty())
    @if (($siteSettings->site_template ?? 'default') === 'feer')
        {{-- FEER: mozaika (pierwsze zdjęcie duże), bez ramek; podpis jako nakładka na dole zdjęcia na jednolitym ciemnym pasku (kontrast ≥ 7:1); klik = powiększenie. --}}
        <section class="mt-12" aria-labelledby="page-gallery-h">
            <h2 id="page-gallery-h" class="text-2xl font-bold text-ink">Galeria</h2>
            <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" role="list" data-lightbox>
                @foreach ($galleryImages as $image)
                    <li class="{{ $loop->first && $galleryImages->count() > 2 ? 'col-span-2 row-span-2' : '' }}">
                        <figure class="feer-card relative h-full overflow-hidden rounded-md bg-gray-100">
                            <img src="{{ $image->image_url }}" alt="{{ $image->alt }}" loading="lazy" decoding="async"
                                 class="w-full cursor-zoom-in object-cover {{ $loop->first && $galleryImages->count() > 2 ? 'aspect-square h-full' : 'aspect-[4/3]' }}">
                            @if ($image->caption)
                                <figcaption class="absolute inset-x-0 bottom-0 px-3 py-2 text-xs font-bold text-white" style="background-color: rgba(29,29,26,.82)">{{ $image->caption }}</figcaption>
                            @endif
                        </figure>
                    </li>
                @endforeach
            </ul>
        </section>
    @else
    <section class="mt-10" aria-label="Galeria zdjęć">
        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
            <i class="fa-solid fa-images text-brand" aria-hidden="true"></i> Galeria
        </h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3" data-lightbox>
            @foreach ($galleryImages as $image)
                <figure class="overflow-hidden rounded-xl border border-gray-200">
                    <img src="{{ $image->image_url }}" alt="{{ $image->alt }}" loading="lazy" class="h-40 w-full object-cover">
                    @if ($image->caption)
                        <figcaption class="bg-gray-50 px-3 py-1.5 text-xs text-muted">{{ $image->caption }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
    @endif
@endif
