{{-- Powitanie: napis (nazwa witryny), H1, tekst, krótkie hasła, dwa przyciski i obrazek (domyślnie logo). --}}
@php
    $kcKicker = $texts->get('hero_kicker') !== '' ? $texts->get('hero_kicker') : $siteSettings->site_name;
    $kcSocialMain = trim((string) $siteSettings->facebook_url) ?: ($siteSettings->socialLinks(1)[0][0] ?? null);
    $kcBtn1 = $texts->url('hero_button_url', $kcSocialMain ?: null);
    $kcBtn2 = $texts->url('hero_button2_url');
    $kcExternal = fn (?string $u) => $u && preg_match('#^https?://#i', $u) && ! str_starts_with($u, url('/'));
    $kcImage = $texts->url('hero_image');
    $kcImageAlt = $texts->get('hero_image_alt');
    $kcImage = $kcImage && ! str_starts_with($kcImage, '#') ? $kcImage : null;
    $kcLogo = $siteSettings->logoUrl();
@endphp
<section class="kc-hero" aria-labelledby="kc-hero-title">
    <div class="kc-wrap kc-hero-in">
        <div>
            <p class="kc-eyebrow kc-hand">{{ $kcKicker }}</p>
            <h1 id="kc-hero-title">{{ $texts->get('hero_title') }}</h1>
            @foreach ($texts->paragraphs('hero_text') as $p)
                <p class="kc-lead">{{ $p }}</p>
            @endforeach
            @if ($texts->rows('hero_points') !== [])
                <ul class="kc-points">
                    @foreach ($texts->rows('hero_points') as [$point])
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ $point }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="kc-actions">
                @if ($kcBtn1 && $texts->get('hero_button') !== '')
                    <a href="{{ $kcBtn1 }}" class="kc-btn" @if ($kcExternal($kcBtn1)) target="_blank" rel="noopener" @endif>
                        @if (str_contains($kcBtn1, 'facebook.com'))<i class="bi bi-facebook" aria-hidden="true"></i>@endif
                        {{ $texts->get('hero_button') }}@if ($kcExternal($kcBtn1))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                    </a>
                @endif
                @if ($kcBtn2 && $texts->get('hero_button2') !== '')
                    <a href="{{ $kcBtn2 }}" class="kc-btn kc-btn-ghost" @if ($kcExternal($kcBtn2)) target="_blank" rel="noopener" @endif>
                        {{ $texts->get('hero_button2') }}@if ($kcExternal($kcBtn2))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                    </a>
                @endif
            </div>
        </div>

        @if ($kcImage || $kcLogo)
            <div @class(['kc-hero-art', 'is-photo' => (bool) $kcImage])>
                <span class="kc-dot kc-dot-2" aria-hidden="true"></span>
                <img src="{{ $kcImage ?: $kcLogo }}" alt="{{ $kcImage ? $kcImageAlt : '' }}" width="416" height="416" fetchpriority="high">
                <span class="kc-dot kc-dot-1" aria-hidden="true"></span>
            </div>
        @endif
    </div>
</section>
