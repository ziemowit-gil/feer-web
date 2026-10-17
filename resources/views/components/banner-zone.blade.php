@if ($banners->isNotEmpty())
    <div class="banner-zone not-prose flex flex-col items-center gap-4" data-zone="{{ $name }}" role="region" aria-label="{{ $label ?: 'Materiał sponsorowany' }}">
        @foreach ($banners as $banner)
            @include('components.banner-item', ['banner' => $banner])
        @endforeach
    </div>
@endif
