@if ($banners->isNotEmpty())
    <div class="banner-zone not-prose" data-zone="{{ $name }}" role="region" aria-label="Materiał sponsorowany">
        @foreach ($banners as $banner)
            <div class="banner-item"
                 x-data
                 x-init="$nextTick(() => fetch('{{ route('banner.impression', $banner) }}', {
                     method: 'POST',
                     headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                 }))">
                @if ($banner->type === 'image' && $banner->image_path)
                    <a href="{{ route('banner.click', $banner) }}"
                       target="{{ $banner->link_target }}"
                       rel="noopener noreferrer"
                       class="block"
                       aria-label="{{ $banner->image_alt ?: 'Materiał sponsorowany' }}">
                        <img src="{{ Storage::url($banner->image_path) }}"
                             alt="{{ $banner->image_alt ?? '' }}"
                             class="max-w-full h-auto"
                             loading="lazy">
                    </a>
                @elseif ($banner->type === 'html' && $banner->html_content)
                    {!! $banner->html_content !!}
                @endif
            </div>
        @endforeach
    </div>
@endif
