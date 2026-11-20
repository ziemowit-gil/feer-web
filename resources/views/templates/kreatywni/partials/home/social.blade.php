{{--
    Social media — główne miejsce, gdzie fundacja pokazuje swoją pracę: duże karty z profilami z Ustawienia → Media
    i BIP (bez osadzania wtyczek serwisów, więc bez ciasteczek stron trzecich). Bez profili sekcji nie ma.
--}}
@php
    $kcProfiles = $siteSettings->socialLinks();
    $kcHandle = function (string $url): ?string {
        return preg_match('#(?:instagram|tiktok|threads)\.(?:com|net)/@?([A-Za-z0-9._]+)#i', $url, $m) ? '@'.$m[1] : null;
    };
@endphp
@if ($kcProfiles !== [])
<section id="social" class="kc-sec kc-dark" aria-labelledby="kc-social-title">
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
                        <span class="kc-social-icon" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                        <span class="kc-social-text">
                            <span class="kc-social-name">{{ $sLabel }}</span>
                            <span class="kc-social-cta">{{ $kcHandle($sUrl) ?? 'Zobacz nasz profil' }}<span class="kc-sr"> (otwiera się w nowej karcie)</span></span>
                        </span>
                        <i class="fa-solid fa-arrow-up-right-from-square kc-social-arrow" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
