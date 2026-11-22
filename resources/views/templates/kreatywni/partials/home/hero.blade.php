{{--
    Slider na górze strony głównej. Slajdy z modułu Slajder (panel → Slajder, slider „Strona główna”: tytuł, tekst,
    przycisk, obrazek, kolejność, terminy) zastępują slajd powitalny; bez nich slajd 1 to powitanie z Ustawienia →
    Szablon (napis, H1, tekst, hasła, dwa przyciski, obrazek; domyślnie logo). Dalej automatycznie: „Naszą misją jest”
    z punktami misji (gdy są) i zaproszenie na Facebooka i Instagram z dużymi przyciskami profili (gdy są w Ustawienia →
    Media i BIP). Tekst slajdu z panelu: każdy wiersz to akapit, wiersze od „- ” to lista z ptaszkami, od „1. ” lista
    numerowana. Obrazek slajdu jest dekoracyjny (treść niesie tytuł i tekst). Bez automatycznego przewijania (WCAG 2.2.2):
    po prawej licznik „01 / 02”, strzałki „Poprzedni / Następny” i kropki (aktywna wydłuża się w kreskę; nazwa slajdu
    dla czytników), strzałki ←/→ na kontrolkach. Nieaktywny slajd jest
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
    $kcProfiles = $siteSettings->socialLinks();
    $kcNet = fn (string $icon) => str_contains($icon, 'facebook') ? 'fb' : (str_contains($icon, 'instagram') ? 'ig' : (str_contains($icon, 'youtube') ? 'yt' : 'other'));
    // Slajdy z modułu Slajder (HomeController::kreatywniHome; pusta kolekcja, gdy moduł wyłączony albo brak slajdów).
    $kcManaged = collect($slides ?? [])->values();
    // Tekst slajdu z panelu → bloki: akapity, lista „- ” (ptaszki) i lista „1. ” (numerowana).
    $kcBlocks = function (?string $text): array {
        $blocks = [];
        foreach (preg_split('/\R/u', trim((string) $text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$type, $value] = preg_match('/^[-•*]\s+(.+)$/u', $line, $m) ? ['ul', $m[1]]
                : (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $m) ? ['ol', $m[1]] : ['p', $line]);
            $last = array_key_last($blocks);
            if ($type !== 'p' && $last !== null && $blocks[$last][0] === $type) {
                $blocks[$last][1][] = $value;
            } else {
                $blocks[] = [$type, $type === 'p' ? $value : [$value]];
            }
        }

        return $blocks;
    };
    $kcSlides = $kcManaged->isNotEmpty()
        ? $kcManaged->map(fn ($s) => \Illuminate\Support\Str::limit((string) $s->title, 60))->all()
        : ['Warsztaty'];
    $kcMissionIdx = $kcMission !== [] ? array_push($kcSlides, $texts->get('mission_title')) - 1 : null;
    $kcSocialIdx = $kcProfiles !== [] ? array_push($kcSlides, 'Social media') - 1 : null;
    $kcCount = count($kcSlides);
@endphp
<section class="kc-hero" aria-roledescription="karuzela" aria-label="Najważniejsze informacje"
         x-data="{ active: 0, count: {{ $kcCount }}, go(i) { this.active = (i + this.count) % this.count } }">
    <div class="kc-wrap">
        <div class="kc-slides">
            {{-- Slajdy z modułu Slajder: zastępują slajd powitalny --}}
            @foreach ($kcManaged as $i => $slide)
                @php
                    $sUrl = trim((string) $slide->button_url) ?: null;
                    $sImg = $slide->image_url;
                @endphp
                <div @class(['kc-slide kc-hero-in', 'is-off' => $i > 0]) role="group" aria-roledescription="slajd" aria-label="{{ $i + 1 }} z {{ $kcCount }}"
                     :class="{ 'is-off': active !== {{ $i }} }" :inert="active !== {{ $i }}" @if ($i > 0) inert @endif>
                    <div>
                        <p class="kc-eyebrow kc-hand">{{ $kcKicker }}</p>
                        @if ($i === 0)
                            <h1 id="kc-hero-title">{{ $slide->title }}</h1>
                        @else
                            <h2 class="kc-slide-title">{{ $slide->title }}</h2>
                        @endif
                        @foreach ($kcBlocks($slide->text) as [$type, $value])
                            @if ($type === 'p')
                                <p class="kc-lead">{{ $value }}</p>
                            @elseif ($type === 'ul')
                                <ul class="kc-points">
                                    @foreach ($value as $point)
                                        <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ $point }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <ol class="kc-steps kc-steps-hero">
                                    @foreach ($value as $point)
                                        <li>{{ $point }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        @endforeach
                        @if ($sUrl && trim((string) $slide->button_label) !== '')
                            <div class="kc-actions">
                                <a href="{{ $sUrl }}" class="kc-btn" @if ($kcExternal($sUrl)) target="_blank" rel="noopener" @endif>
                                    @if (str_contains($sUrl, 'facebook.com'))<i class="bi bi-facebook" aria-hidden="true"></i>@endif
                                    {{ $slide->button_label }}@if ($kcExternal($sUrl))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                                </a>
                            </div>
                        @endif
                    </div>
                    @if ($sImg || $kcLogo)
                        <div @class(['kc-hero-art', 'is-photo' => (bool) $sImg])>
                            <span class="kc-dot kc-dot-2" aria-hidden="true"></span>
                            <img src="{{ $sImg ?: $kcLogo }}" alt="" width="416" height="416" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            <span class="kc-dot kc-dot-1" aria-hidden="true"></span>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Slajd 1: powitanie (gdy w module Slajder nie ma slajdów) --}}
            @if ($kcManaged->isEmpty())
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
            @endif

            {{-- Slajd 2: misja --}}
            @if ($kcMissionIdx !== null)
                <div class="kc-slide kc-hero-in is-off" role="group" aria-roledescription="slajd" aria-label="{{ $kcMissionIdx + 1 }} z {{ $kcCount }}"
                     :class="{ 'is-off': active !== {{ $kcMissionIdx }} }" :inert="active !== {{ $kcMissionIdx }}" inert>
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

            {{-- Slajd 3: zaproszenie do social media (Facebook, Instagram…) --}}
            @if ($kcSocialIdx !== null)
                <div class="kc-slide kc-hero-in is-off" role="group" aria-roledescription="slajd" aria-label="{{ $kcSocialIdx + 1 }} z {{ $kcCount }}"
                     :class="{ 'is-off': active !== {{ $kcSocialIdx }} }" :inert="active !== {{ $kcSocialIdx }}" inert>
                    <div>
                        <p class="kc-eyebrow kc-hand">{{ $texts->get('slide_social_kicker') }}</p>
                        <h2 class="kc-slide-title">{{ $texts->get('slide_social_title') }}</h2>
                        @foreach ($texts->paragraphs('slide_social_text') as $p)
                            <p class="kc-lead">{{ $p }}</p>
                        @endforeach
                        <div class="kc-actions kc-slide-social">
                            @foreach ($kcProfiles as [$sUrl, $sIcon, $sLabel])
                                <a href="{{ $sUrl }}" class="kc-net-pill kc-net-pill-lg" target="_blank" rel="noopener">
                                    <span class="kc-net kc-net-{{ $kcNet($sIcon) }}" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                                    <span>Obserwuj na {{ $sLabel === 'Facebook' ? 'Facebooku' : ($sLabel === 'Instagram' ? 'Instagramie' : ($sLabel === 'YouTube' ? 'YouTubie' : $sLabel)) }}</span><span class="kc-sr"> (otwiera się w nowej karcie)</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="kc-hero-art kc-hero-art-icons" aria-hidden="true">
                        <span class="kc-dot kc-dot-2"></span>
                        <span class="kc-art-nets">
                            @foreach (array_slice($kcProfiles, 0, 2) as [, $sIcon])
                                <span class="kc-net kc-net-{{ $kcNet($sIcon) }}"><i class="{{ $sIcon }}"></i></span>
                            @endforeach
                        </span>
                        <span class="kc-dot kc-dot-1"></span>
                    </div>
                </div>
            @endif
        </div>

        @if ($kcCount > 1)
            <div class="kc-slider-nav" x-cloak @keydown.arrow-right.prevent="go(active + 1)" @keydown.arrow-left.prevent="go(active - 1)">
                <p class="kc-slider-count" aria-hidden="true"><strong x-text="String(active + 1).padStart(2, '0')">01</strong> / {{ str_pad((string) $kcCount, 2, '0', STR_PAD_LEFT) }}</p>
                <button type="button" class="kc-icon-btn kc-slider-arrow" @click="go(active - 1)">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="kc-sr">Poprzedni slajd</span>
                </button>
                <ul class="kc-dots">
                    @foreach ($kcSlides as $i => $label)
                        <li>
                            <button type="button" @click="go({{ $i }})" :aria-current="active === {{ $i }} ? 'true' : null"
                                    :class="{ 'is-on': active === {{ $i }} }" @if ($i === 0) aria-current="true" class="is-on" @endif>
                                <span class="kc-sr">Slajd {{ $i + 1 }}: {{ $label }}</span>
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
