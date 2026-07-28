{{--
    Galeria zdjęć podstrony — pokazywana, gdy zaznaczono „Pokaż galerię zdjęć”
    i strona ma zdjęcia. Nie dotyczy typu „O organizacji” (ma własną galerię).
--}}
@php
    $galleryImages = ($page->show_gallery ?? false) && ! $page->isAbout()
        ? $page->images->filter(fn ($i) => $i->image_url)->values()
        : collect();
@endphp

@if ($galleryImages->isNotEmpty())
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
