@if (filled($page->content_image))
    @php $feerImg = ($siteSettings->site_template ?? 'default') === 'feer'; @endphp
    {{-- Szablon FEER: zdjęcie strony jako szeroki baner 21:9 pod tytułem (bez zawężania szerokości), powiększane po kliknięciu. --}}
    <figure class="my-6">
        <img src="{{ $page->content_image }}"
             alt="{{ $page->content_image_alt ?: 'Grafika ilustracyjna' }}"
             @if ($feerImg) data-lightbox decoding="async" @endif
             class="{{ $feerImg ? 'aspect-[21/9] cursor-zoom-in rounded-md' : (($page->content_image_width ? $page->content_image_width . ' ' : '') . 'rounded-lg') }} w-full object-cover">
    </figure>
@endif
