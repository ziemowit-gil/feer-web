{{--
    Slider (po lewej) i kafle skrótów (po prawej, z modułu „Szybkie akcje”) na jasnoszarym tle. Slider ma przyciski
    poprzedni/następny, kropki oraz przycisk wstrzymania (WCAG 2.2.2); automatyczna zmiana zatrzymuje się też po najechaniu
    i fokusie, a przy „ogranicz ruch” nie startuje. Slajd „misji” to obiekt bez zdjęcia i tytułu (HomeController).
    Dane: $slides, $quickLinks.
--}}
@php
    $slides ??= collect();
    $tiles = ($quickLinks ?? collect())->take(8)->values();
    $count = $slides->count();
    // Sekcję slidera można wyłączyć w Ustawieniach → Strona główna; kafle skrótów zostają wtedy same, w czterech kolumnach.
    $showSlider = $siteSettings->isHomepageSectionEnabled('hero');
@endphp
@include('templates.test3.partials.home.css')
@if ($showSlider || $tiles->isNotEmpty())
<section class="z3 z3-hero" aria-label="Wyróżnione treści i skróty">
    <div class="z3-wrap">
        <div class="z3-hero-in {{ $showSlider && $tiles->isNotEmpty() ? 'has-tiles' : '' }}">
            @if ($showSlider)
            <div class="z3-slider" role="region" aria-roledescription="karuzela" aria-label="Wyróżnione treści"
                 x-data="{ cur: 0, n: {{ max($count, 1) }}, paused: false, hold: false, timer: null,
                           reduce: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                           go(i) { this.cur = (i + this.n) % this.n; },
                           start() { if (this.n > 1 && ! this.reduce) { this.timer = setInterval(() => { if (! this.paused && ! this.hold) this.cur = (this.cur + 1) % this.n; }, 6000); } } }"
                 x-init="start()" @mouseenter="hold = true" @mouseleave="hold = false" @focusin="hold = true" @focusout="hold = false">
                <div :aria-live="(paused || hold) ? 'polite' : 'off'" style="height:100%">
                    @forelse ($slides as $i => $slide)
                        @php
                            $isMission = ! empty($slide->mission_text);
                            $image = $slide->image_url ?? null;
                            $title = $slide->title ?? null;
                            $link = $slide->button_url ?? null;
                        @endphp
                        <div class="z3-slide {{ $i === 0 ? 'is-on' : '' }}" :class="cur === {{ $i }} ? 'is-on' : ''" role="group" aria-roledescription="slajd"
                             aria-label="{{ $i + 1 }} z {{ $count }}" :aria-hidden="(cur !== {{ $i }}).toString()">
                            @if ($isMission)
                                <div class="z3-slide-mission">
                                    @if (! empty($slide->logo_url))<img src="{{ $slide->logo_url }}" alt="">@endif
                                    <p>{{ $slide->mission_text }}</p>
                                </div>
                            @else
                                @if (! empty($image))<img src="{{ $image }}" alt="{{ $slide->image_alt ?? '' }}" @if ($i > 0) loading="lazy" @endif>@endif
                                @if (! empty($title))
                                    <div class="z3-slide-cap">
                                        <h3>@if (! empty($link))<a href="{{ $link }}" :tabindex="cur === {{ $i }} ? 0 : -1">{{ $title }}</a>@else{{ $title }}@endif</h3>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @empty
                        <div class="z3-slide is-on"><div class="z3-slide-mission"><p>{{ $siteSettings->site_name }}</p></div></div>
                    @endforelse
                </div>

                @if ($count > 1)
                    <button type="button" class="z3-arrow z3-arrow--prev" @click="go(cur - 1)" aria-label="Poprzedni slajd"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" class="z3-arrow z3-arrow--next" @click="go(cur + 1)" aria-label="Następny slajd"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                    <div class="z3-dots">
                        <button type="button" class="z3-pause" @click="paused = ! paused" :aria-label="paused ? 'Wznów automatyczną zmianę slajdów' : 'Wstrzymaj automatyczną zmianę slajdów'">
                            <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i>
                        </button>
                        @foreach ($slides as $i => $slide)
                            <button type="button" class="z3-dot" @click="go({{ $i }})" :aria-current="(cur === {{ $i }}).toString()" aria-label="Slajd {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

            @if ($tiles->isNotEmpty())
                <ul class="z3-tiles {{ $showSlider ? '' : 'z3-tiles--wide' }}" role="list">
                    @foreach ($tiles as $tile)
                        @php $custom = \App\Support\Color::isValid($tile->color) ? \App\Support\Color::button($tile->color) : null; @endphp
                        <li>
                            <a class="z3-tile {{ $custom ? 'z3-tile--c' : '' }}" href="{{ site_href($tile->url) }}" @if ($custom) style="background: {{ $custom['bg'] }}; color: {{ $custom['text'] }}" @endif>
                                {!! icon_html($tile->icon, '', 'font-size: 2.25rem') !!}
                                <span>{{ $tile->label }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
@endif
