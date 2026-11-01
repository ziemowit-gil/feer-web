{{-- Slider (lewo) + trzy duże kafle skrótów (prawo, z „Szybkich akcji”). Dane: $slides, $quickLinks. --}}
@php
    $tiles = $quickLinks->take(3)->values();
    $palette = ['#0b4f9e', '#0b4f9e', '#b3363f'];
@endphp
<style>
    .z2-hero { max-width: 1160px; margin: 2rem auto 0; padding: 0 1rem; display: grid; gap: 1rem; grid-template-columns: 1fr; }
    .z2-hero.has-tiles { grid-template-columns: 2fr 1fr; }
    .z2-slider { position: relative; overflow: hidden; background: #1a1a1a; aspect-ratio: 16 / 9; min-height: 14rem; }
    .z2-slide { position: absolute; inset: 0; opacity: 0; transition: opacity .6s; }
    .z2-slide.on { opacity: 1; z-index: 1; }
    .z2-slide img { width: 100%; height: 100%; object-fit: cover; }
    .z2-slide-cap { position: absolute; inset-inline: 0; bottom: 0; padding: 2rem 1.5rem 1.25rem; background: linear-gradient(to top, rgba(0,0,0,.8), transparent); color: #fff; }
    .z2-slide-cap h2 { margin: 0; font-size: clamp(1.1rem, 2.4vw, 1.9rem); font-weight: 800; }
    .z2-slide-cap a { color: #fff; text-decoration: none; }
    .z2-slide-cap a:hover { text-decoration: underline; }
    .z2-mission { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; padding: 2rem; text-align: center; background: #0b4f9e; color: #fff; }
    .z2-mission img { height: 4.5rem; width: auto; object-fit: contain; }
    .z2-mission p { margin: 0; font-size: clamp(1.1rem, 2.2vw, 1.7rem); font-weight: 700; line-height: 1.4; }
    .z2-dots { display: flex; justify-content: center; gap: .6rem; margin: .75rem 0 0; grid-column: 1; }
    .z2-dots button { height: .9rem; width: .9rem; border-radius: 50%; border: 0; background: #b9c2cf; cursor: pointer; padding: 0; position: relative; }
    .z2-dots button::after { content: ""; position: absolute; inset: -.65rem; }
    .z2-dots .z2-pause { width: auto; height: auto; border-radius: 0; background: transparent; color: #0b4f9e; font-size: .9rem; padding: .1rem .35rem; }
    .z2-dots button[aria-selected="true"] { background: #0b4f9e; }
    .z2-tiles { display: grid; gap: 1rem; grid-template-rows: repeat(3, 1fr); }
    .z2-tile { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: #fff; text-decoration: none; font-weight: 800; font-size: 1.1rem; min-height: 5.5rem; }
    .z2-tile:hover, .z2-tile:focus-visible { filter: brightness(.88); }
    .z2-tile i { font-size: 2.4rem; flex: none; width: 3.5rem; text-align: center; }
    .z2-tile span { flex: 1; text-align: center; }
    @media (max-width: 800px) { .z2-hero.has-tiles { grid-template-columns: 1fr; } .z2-tiles { grid-template-rows: none; } }
</style>
<section class="z2-hero {{ $tiles->isNotEmpty() ? 'has-tiles' : '' }}" aria-label="Slider i skróty"
         x-data="{ cur: 0, n: {{ max($slides->count(), 1) }}, paused: false, hold: false, t: null,
                   go(i) { this.cur = i; },
                   start() { if (this.n > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.t = setInterval(() => { if (! this.paused && ! this.hold) this.cur = (this.cur + 1) % this.n; }, 6000); } }"
         x-init="start()" @mouseenter="hold = true" @mouseleave="hold = false" @focusin="hold = true" @focusout="hold = false">
    <div>
        <div class="z2-slider" role="region" aria-roledescription="karuzela" aria-label="Wyróżnione treści" :aria-live="(paused || hold) ? 'polite' : 'off'">
            @forelse ($slides as $i => $slide)
                <div class="z2-slide" :class="cur === {{ $i }} ? 'on' : ''" role="group" aria-roledescription="slajd"
                     aria-label="{{ $i + 1 }} z {{ $slides->count() }}" :aria-hidden="(cur !== {{ $i }}).toString()">
                    @if (! empty($slide->mission_text))
                        {{-- Slajd misji (obiekt bez zdjęcia i tytułu). --}}
                        <div class="z2-mission">
                            @if (! empty($slide->logo_url))<img src="{{ $slide->logo_url }}" alt="">@endif
                            <p>{{ $slide->mission_text }}</p>
                        </div>
                    @else
                    @if (! empty($slide->image_url))<img src="{{ $slide->image_url }}" alt="">@endif
                    @if (! empty($slide->title))
                        <div class="z2-slide-cap">
                            <h2>@if (! empty($slide->button_url))<a href="{{ $slide->button_url }}" :tabindex="cur === {{ $i }} ? 0 : -1">{{ $slide->title }}</a>@else{{ $slide->title }}@endif</h2>
                        </div>
                    @endif
                    @endif
                </div>
            @empty
                <div class="z2-slide on" style="display:flex;align-items:center;justify-content:center;color:#fff">{{ $siteSettings->site_name }}</div>
            @endforelse
        </div>
        @if ($slides->count() > 1)
            <div class="z2-dots" role="tablist" aria-label="Wybór slajdu">
                {{-- WCAG 2.2.2: automatyczną zmianę slajdów można wstrzymać. --}}
                <button type="button" class="z2-pause" role="presentation" @click="paused = ! paused" :aria-label="paused ? 'Wznów automatyczną zmianę slajdów' : 'Wstrzymaj automatyczną zmianę slajdów'">
                    <i class="fa-solid" :class="paused ? 'fa-play' : 'fa-pause'" aria-hidden="true"></i>
                </button>
                @foreach ($slides as $i => $slide)
                    <button type="button" role="tab" @click="go({{ $i }})" :aria-selected="(cur === {{ $i }}).toString()" aria-label="Slajd {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>
    @if ($tiles->isNotEmpty())
        <div class="z2-tiles">
            @foreach ($tiles as $i => $tile)
                @php
                    $bg = \App\Support\Color::isValid($tile->color) ? \App\Support\Color::button($tile->color) : ['bg' => $palette[$i % 3], 'text' => '#ffffff'];
                @endphp
                <a class="z2-tile" href="{{ site_href($tile->url) }}" style="background: {{ $bg['bg'] }}; color: {{ $bg['text'] }}">
                    {!! icon_html($tile->icon) !!}
                    <span>{{ $tile->label }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>
