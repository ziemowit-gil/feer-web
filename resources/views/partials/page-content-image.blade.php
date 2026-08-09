@if (filled($page->content_image))
    <figure class="my-6">
        <img src="{{ $page->content_image }}"
             alt="{{ $page->content_image_alt ?: 'Grafika ilustracyjna' }}"
             class="{{ $page->content_image_width ? $page->content_image_width . ' ' : '' }}w-full rounded-lg object-cover">
    </figure>
@endif
