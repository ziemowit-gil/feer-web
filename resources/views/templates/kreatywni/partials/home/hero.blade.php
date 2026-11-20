{{--
    Slider na górze strony głównej: slajd 1 — powitanie (napis, H1, tekst, hasła, dwa przyciski, obrazek; domyślnie
    logo), slajd 2 — „Naszą misją jest” z punktami misji (gdy są). Bez automatycznego przewijania (WCAG 2.2.2):
    strzałki „Poprzedni / Następny” i kropki z nazwą slajdu, strzałki ←/→ na kontrolkach. Nieaktywny slajd jest
    ukryty (visibility + inert), ale zajmuje miejsce, więc wysokość się nie zmienia. Bez JS widać pierwszy slajd.
--}}
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
    $kcMission = $texts->rows('mission_items');
    $kcSlides = $kcMission !== [] ? ['Warsztaty', $texts->get('mission_title')] : ['Warsztaty'];
    $kcCount = count($kcSlides);
@endphp
<section class="kc-hero" aria-roledescription="karuzela" aria-label="Najważniejsze informacje"
         x-data="{ active: 0, count: {{ $kcCount }}, go(i) { this.active = (i + this.count) % this.count } }">
    <div class="kc-wrap">
        <div class="kc-slides">
            {{-- Slajd 1: powitanie --}}
            <div class="kc-slide kc-hero-in" role="group" aria-roledescription="slajd" aria-label="1 z {{ $kcCount }}"
                 :class="{ 'is-off': active !== 0 }" :inert="active !== 0">
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

            {{-- Slajd 2: misja --}}
            @if ($kcMission !== [])
                <div class="kc-slide kc-hero-in is-off" role="group" aria-roledescription="slajd" aria-label="2 z {{ $kcCount }}"
                     :class="{ 'is-off': active !== 1 }" :inert="active !== 1" inert>
                    <div>
                        <p class="kc-eyebrow kc-hand">{{ $kcKicker }}</p>
                        <h2 class="kc-slide-title">{{ $texts->get('mission_title') }}</h2>
                        <ol class="kc-steps kc-steps-hero">
                            @foreach ($kcMission as [$item])
                                <li>{{ $item }}</li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="kc-hero-art kc-hero-art-icons" aria-hidden="true">
                        <span class="kc-dot kc-dot-2"></span>
                        <span class="kc-art-icon"><i class="fa-solid fa-hands-holding-child"></i></span>
                        <span class="kc-dot kc-dot-1"></span>
                    </div>
                </div>
            @endif
        </div>

        @if ($kcCount > 1)
            <div class="kc-slider-nav" x-cloak @keydown.arrow-right.prevent="go(active + 1)" @keydown.arrow-left.prevent="go(active - 1)">
                <button type="button" class="kc-icon-btn kc-slider-arrow" @click="go(active - 1)">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="kc-sr">Poprzedni slajd</span>
                </button>
                <ul class="kc-dots">
                    @foreach ($kcSlides as $i => $label)
                        <li>
                            <button type="button" @click="go({{ $i }})" :aria-current="active === {{ $i }} ? 'true' : null"
                                    :class="{ 'is-on': active === {{ $i }} }" @if ($i === 0) aria-current="true" class="is-on" @endif>
                                <span class="kc-sr">Slajd {{ $i + 1 }}: </span><span class="kc-dot-label">{{ $label }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <button type="button" class="kc-icon-btn kc-slider-arrow" @click="go(active + 1)">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span class="kc-sr">Następny slajd</span>
                </button>
                <p class="kc-sr" aria-live="polite" x-text="'Slajd ' + (active + 1) + ' z ' + count"></p>
            </div>
        @endif
    </div>
</section>
