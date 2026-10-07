{{--
    Szablon FEER — „W obiektywie": mozaika zdjęć z galerii (1 duże + do 4 małych), klik otwiera powiększenie (lightbox: data-lightbox).
    Płasko, bez ramek; podpis jako alt. Pokazuje się, gdy moduł galerii ma zdjęcia i sekcja „Galeria" nie jest ukryta.
--}}
@php $shots = ($gallery ?? collect())->filter(fn ($p) => filled($p->image_url))->values()->take(5); @endphp
@if ($shots->isNotEmpty())
<section id="galeria" class="py-12" aria-labelledby="feer-gallery-heading">
    <div class="mx-auto max-w-6xl px-4">
        <h2 id="feer-gallery-heading" class="text-2xl font-bold text-ink md:text-3xl">W obiektywie</h2>
        <span class="mt-3 mb-8 block h-1 w-14 bg-brand" aria-hidden="true"></span>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:grid-rows-2" role="list">
            @foreach ($shots as $i => $photo)
                <li class="{{ $i === 0 ? 'sm:col-span-2 lg:col-span-2 lg:row-span-2' : '' }}">
                    <img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" data-lightbox loading="lazy" decoding="async"
                         class="feer-card w-full cursor-zoom-in rounded-md object-cover {{ $i === 0 ? 'aspect-[4/3] lg:h-full lg:aspect-auto' : 'aspect-[4/3]' }}">
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
