{{-- Hero „podcasts”: nazwa witryny/tagline i najnowszy odcinek z odtwarzaczem (gdy odcinek nie jest premium i ma plik audio). --}}
@include('templates.podcasts.partials.base-css')
@php
    $pcTitle = filled($siteSettings->tagline) ? $siteSettings->tagline : $siteSettings->site_name;
    $pcCover = $latest?->getFirstMediaUrl('cover') ?: null;
    $pcCanPlay = $latest && ! $latest->is_premium && $latest->hasMedia('audio');
@endphp
<section class="pc pc-hero" aria-labelledby="pc-hero-h">
    <div class="pc-wrap pc-hero-in">
        <div class="pc-hero-ep">
            <h1 id="pc-hero-h" style="margin:0;font-size:clamp(2rem,5vw,3.25rem);font-weight:800;line-height:1.1;text-transform:uppercase">{{ $pcTitle }}</h1>
            @if (filled($siteSettings->meta_description))<p class="pc-lead">{{ $siteSettings->meta_description }}</p>@endif
            @if ($latest)
                <p class="pc-kicker">Najnowszy odcinek{{ $latest->episode_number ? " · nr ".$latest->episode_number : "" }}</p>
                <h2 class="pc-ep-title"><a href="{{ route('podcasts.show', $latest) }}">{{ $latest->title }}</a></h2>
                @if ($pcCanPlay)<audio controls preload="none" src="{{ route('podcasts.stream', $latest) }}">Twoja przeglądarka nie obsługuje odtwarzacza audio.</audio>@endif
                <div class="pc-hero-cta">
                    <a class="pc-btn pc-btn-green" href="{{ route('podcasts.show', $latest) }}">Zobacz odcinek</a>
                    <a class="pc-btn pc-btn-blue" href="{{ route('podcasts.index') }}">Wszystkie odcinki</a>
                </div>
            @endif
        </div>
        @if ($pcCover)<img class="pc-cover" src="{{ $pcCover }}" alt="" loading="eager">@endif
    </div>
</section>
