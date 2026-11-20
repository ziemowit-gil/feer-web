{{-- Galeria: do ośmiu zdjęć z modułu Galeria (powiększenie po kliknięciu) i link do pierwszego profilu social media. --}}
@php
    $kcPhotos = $siteSettings->isModuleEnabled('gallery') ? $gallery->filter(fn ($p) => filled($p->image_url)) : collect();
    $kcMore = $siteSettings->socialLinks(1)[0] ?? null;
@endphp
@if ($kcPhotos->isNotEmpty())
<section id="galeria" class="kc-sec kc-sec-cream" aria-labelledby="kc-gallery-title">
    <div class="kc-wrap">
        <h2 id="kc-gallery-title" class="kc-h2 kc-head">{{ $texts->get('gallery_title') }}</h2>
        <ul class="kc-gallery">
            @foreach ($kcPhotos as $photo)
                <li><img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" loading="lazy" data-lightbox></li>
            @endforeach
        </ul>
        @if ($kcMore)
            <div class="kc-more">
                <a href="{{ $kcMore[0] }}" class="kc-btn kc-btn-ghost" target="_blank" rel="noopener">
                    <i class="{{ $kcMore[1] }}" aria-hidden="true"></i> Więcej zdjęć: {{ $kcMore[2] }}<span class="kc-sr"> (otwiera się w nowej karcie)</span>
                </a>
            </div>
        @endif
    </div>
</section>
@endif
