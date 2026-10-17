{{-- Jeden baner z modułu Banery (strefa <x-banner-zone> albo shortcode [baner:ID]): wyświetlenie liczone w tle,
     kliknięcie przez /b/click (statystyki w panelu). Obraz z tekstem alternatywnym = nazwa linku. --}}
        <div class="banner-item mx-auto text-center"
             x-data
             x-init="$nextTick(() => fetch('{{ route('banner.impression', $banner) }}', {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
             }))">
            @if ($banner->type === 'image' && $banner->image_path)
                <a href="{{ route('banner.click', $banner) }}"
                   target="{{ $banner->link_target }}"
                   rel="noopener noreferrer"
                   class="inline-block"
                   aria-label="{{ $banner->image_alt ?: 'Materiał sponsorowany' }}">
                    <img src="{{ Storage::url($banner->image_path) }}"
                         alt="{{ $banner->image_alt ?? '' }}"
                         @if ($banner->width) width="{{ $banner->width }}" @endif
                         @if ($banner->height) height="{{ $banner->height }}" @endif
                         class="mx-auto block h-auto max-w-full"
                         loading="lazy">
                </a>
            @elseif ($banner->type === 'html' && $banner->html_content)
                <div class="mx-auto max-w-full"
                     @if ($banner->width) style="width: {{ $banner->width }}px" @endif>
                    {!! $banner->html_content !!}
                </div>
            @endif
        </div>
