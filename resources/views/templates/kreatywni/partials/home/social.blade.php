{{--
    Social media — główne miejsce, gdzie fundacja pokazuje swoją pracę: duże karty z profilami z Ustawienia → Media
    i BIP — znak w kolorach serwisu (Facebook, Instagram, YouTube), adres profilu i przycisk „Obserwuj” (bez osadzania
    wtyczek serwisów, więc bez ciasteczek stron trzecich). Bez profili sekcji nie ma.
--}}
@php
    $kcProfiles = $siteSettings->socialLinks();
    $kcNet = fn (string $icon) => str_contains($icon, 'facebook') ? 'fb' : (str_contains($icon, 'instagram') ? 'ig' : (str_contains($icon, 'youtube') ? 'yt' : 'other'));
    $kcHandle = function (string $url): ?string {
        return preg_match('#(?:instagram|tiktok|threads)\.(?:com|net)/@?([A-Za-z0-9._]+)#i', $url, $m) ? '@'.$m[1] : null;
    };
@endphp
@if ($kcProfiles !== [])
<section id="social" class="kc-sec kc-sec-cream" aria-labelledby="kc-social-title">
    <div class="kc-wrap">
        <div class="kc-center">
            <h2 id="kc-social-title" class="kc-h2">{{ $texts->get('social_title') }}</h2>
            @foreach ($texts->paragraphs('social_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
        </div>
        <ul class="kc-social">
            @foreach ($kcProfiles as [$sUrl, $sIcon, $sLabel])
                <li>
                    <a href="{{ $sUrl }}" target="_blank" rel="noopener">
                        <span class="kc-social-icon kc-net-{{ $kcNet($sIcon) }}" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                        <span class="kc-social-text">
                            <span class="kc-social-name">{{ $sLabel }}</span>
                            <span class="kc-social-cta">{{ $kcHandle($sUrl) ?? 'Relacje z naszych warsztatów' }}</span>
                        </span>
                        <span class="kc-social-follow">Obserwuj<span class="kc-sr"> nas: {{ $sLabel }} (otwiera się w nowej karcie)</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
