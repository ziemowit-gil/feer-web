{{-- Slider z „Slidery” wybrany w edycji strony (Typ i układ → „Slider nad treścią”). --}}
@if (filled(($page->layout_data ?? [])['hero_slider_id'] ?? null))
    @include('partials.content-slider', ['sliderId' => $page->layout_data['hero_slider_id']])
@endif
{{-- Slider ze zdjęć strony — na każdej stronie po włączeniu „Slider ze zdjęć strony” (Dodatki). „Strona działu” ma własne miejsce na slider. --}}
@if (($page->page_template ?? 'default') !== 'section' && (($page->layout_data ?? [])['slider'] ?? false))
    @include('partials.page-slider', [
        'slides' => $page->images->filter(fn ($i) => $i->image_url)->sortBy('order')->values(),
        'label' => $page->title,
    ])
@endif
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
