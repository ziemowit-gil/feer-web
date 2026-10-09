@extends('layouts.site')

@section('title', ($page->title ?? 'Dołącz do nas') . ' — ' . $siteSettings->site_name)
@section('meta_description', $page?->hub_intro ?? 'Wolontariat, praca i współpraca z ' . $siteSettings->site_name . '. Wybierz, jak chcesz się zaangażować.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $page->title ?? 'Dołącz do nas', 'url' => null],
    ]])
@endsection

{{--
    Strona „Dołącz do nas": jeden widok bez zakładek — nagłówek, kafle ścieżek (wolontariat, praca, współpraca,
    wsparcie, newsletter — zależnie od włączonych modułów), aktualne ogłoszenia wolontariatu i oferty pracy,
    „Jak to działa" w trzech krokach oraz ramka kontaktu. Płasko, zwykły CSS, kontrast ≥ 4,5:1.
--}}
@section('content')
@php
    $title = $page->title ?? 'Dołącz do nas';
    $intro = filled($page->hub_intro ?? null) ? $page->hub_intro : $siteSettings->joinText('intro');
    $jt = fn (string $k) => $siteSettings->joinText($k);
    $hubLinks = collect($page->hub_links ?? [])->filter(fn ($l) => filled($l['label'] ?? null) && filled($l['url'] ?? null))->values();
    $pal = \App\Support\ThemePalette::tiles();

    $paths = array_values(array_filter([
        $volunteeringActive ? [$jt('vol_title'), $jt('vol_text'), route('volunteer.index'), 'fa-handshake-angle', $adsCount, trans_choice('ogłoszenie|ogłoszenia|ogłoszeń', $adsCount)] : null,
        $jobsActive ? [$jt('job_title'), $jt('job_text'), route('praca.index'), 'fa-briefcase', $offersCount, trans_choice('oferta|oferty|ofert', $offersCount)] : null,
        $cooperationPage ? [$jt('coop_title'), $jt('coop_text'), $cooperationPage->publicUrl(), 'fa-handshake', null, null] : null,
        $supportActive ? [$jt('support_title'), $jt('support_text'), route('support.show'), 'fa-heart', null, null] : null,
        $newsletterActive ? [$jt('news_title'), $jt('news_text'), route('newsletter.show'), 'fa-envelope-open-text', null, null] : null,
    ]));
@endphp
<style>
    .jn-head { max-width: 72rem; margin: 0 auto; padding: 2.5rem 1rem 1rem; }
    .jn-h1 { margin: 0; font-size: 2.25rem; line-height: 1.15; font-weight: 800; color: #1d1d1a; }
    .jn-bar { display: block; width: 3.5rem; height: 4px; margin: .9rem 0 1.25rem; background: var(--color-brand-dark); }
    .jn-lead { max-width: 44rem; margin: 0; font-size: 1.2rem; line-height: 1.6; color: #1d1d1a; }
    .jn-wrap { max-width: 72rem; margin: 0 auto; padding: 1rem 1rem 3rem; }
    .jn-cols { display: grid; gap: 2.5rem; }
    @media (min-width: 1024px) { .jn-cols { grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr); align-items: stretch; } .jn-side { position: sticky; top: 1.5rem; align-self: start; } }
    .jn-side .jn-paths { grid-template-columns: 1fr; }
    /* Wyrównanie: wszystkie kafle ścieżek jednakowej wysokości, treść od góry, a kolumny zaczynają się na tej samej linii. */
    .jn-cols { align-items: stretch; }
    .jn-main { display: flex; flex-direction: column; }
    .jn-main > section:last-child, .jn-main > div:last-child { flex: 1 1 auto; }
    .jn-side .jn-paths { align-items: stretch; }
    .jn-side .jn-path { min-height: 4.75rem; height: 100%; padding: .75rem .9rem; gap: .25rem; justify-content: flex-start; }
    .jn-side .jn-path > span:first-child { display: block; }
    .jn-side .jn-path-i, .jn-side .jn-path-t, .jn-side .jn-path-d { text-align: left; }
    .jn-main .jn-cards { align-items: stretch; }
    .jn-main .jn-card { min-height: 0; }
    .jn-main .jn-card h3 { margin: 0; }
    .jn-side .jn-path-i { font-size: 1rem; }
    .jn-side .jn-path-t { font-size: 1rem; margin-top: .25rem !important; }
    .jn-side .jn-path-d { font-size: .85rem; margin-top: .15rem; }
    .jn-side .jn-path-f { display: none; }
    .jn-main .jn-cards { grid-template-columns: 1fr; gap: .6rem; }
    .jn-main .jn-card { flex-direction: row; flex-wrap: wrap; align-items: center; gap: .25rem 1rem; padding: .9rem 1rem; }
    .jn-main .jn-card h3 { flex: 1 1 14rem; min-width: 0; }
    .jn-main .jn-card p.jn-meta { flex-basis: 100%; margin: 0; }
    .jn-main .jn-card p:not(.jn-meta) { flex-basis: 100%; }
    .jn-main .jn-card .jn-cta { margin-top: 0; padding-top: 0; }
    .jn-paths { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
    .jn-path { display: flex; min-height: 14rem; flex-direction: column; justify-content: space-between; gap: 1.25rem; padding: 1.5rem; border-radius: .5rem; text-decoration: none; }
    .jn-path:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    .jn-path-i { font-size: 2rem; }
    .jn-path-t { display: block; font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .jn-path-d { display: block; margin-top: .5rem; font-size: 1rem; line-height: 1.5; }
    .jn-path-f { display: flex; align-items: center; justify-content: space-between; gap: 1rem; font-weight: 800; }
    .jn-path-n { display: inline-flex; align-items: center; gap: .4rem; padding: .15rem .6rem; border: 2px solid currentColor; border-radius: .25rem; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
    .jn-path:hover .jn-path-arrow { transform: translateX(4px); } .jn-path-arrow { transition: transform .2s; }
    @media (prefers-reduced-motion: reduce) { .jn-path-arrow { transition: none; } }
    .jn-sec { margin-top: 3.5rem; }
    .jn-sec-h { display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: .75rem 1.5rem; margin-bottom: 1.25rem; }
    .jn-h2 { margin: 0; padding-left: .75rem; border-left: 4px solid var(--color-brand-dark); font-size: 1.6rem; font-weight: 800; color: #1d1d1a; }
    .jn-h2 small { display: block; margin-top: .25rem; font-size: .95rem; font-weight: 500; color: #374151; }
    .jn-more { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 3px; } .jn-more:hover { color: #1d1d1a; }
    .jn-cards { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); }
    .jn-card { display: flex; flex-direction: column; gap: .6rem; padding: 1.25rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; }
    .jn-card:hover, .jn-card:focus-within { border-color: var(--color-brand-dark); }
    .jn-meta { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin: 0; font-size: .85rem; color: #374151; } .jn-meta i { margin-right: .3rem; color: var(--color-brand-dark); }
    .jn-card h3 { margin: 0; font-size: 1.15rem; line-height: 1.3; font-weight: 800; }
    .jn-card h3 a { color: #1d1d1a; text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; } .jn-card h3 a:hover { color: var(--color-brand-dark); }
    .jn-card h3 a:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; border-radius: .25rem; }
    .jn-card p { margin: 0; color: #374151; }
    .jn-card .jn-cta { margin-top: auto; padding-top: .5rem; font-weight: 800; color: var(--color-brand-dark); }
    .jn-empty { padding: 1.5rem; border: 2px dashed #9ca3af; border-radius: .5rem; color: #1d1d1a; }
    .jn-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); counter-reset: step; }
    .jn-step { position: relative; padding: 1.25rem 1.25rem 1.25rem 4rem; border-radius: .5rem; background: #f3f4f6; }
    .jn-step::before { counter-increment: step; content: counter(step); position: absolute; left: 1.25rem; top: 1.25rem; display: flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border-radius: 9999px; background: #1d1d1a; color: #fff; font-weight: 800; }
    .jn-step strong { display: block; margin-bottom: .25rem; color: #1d1d1a; } .jn-step span { color: #374151; }
    .jn-contact { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 3.5rem; padding: 1.5rem; border-radius: .5rem; background: var(--color-brand-light); }
    .jn-contact p { margin: 0; max-width: 40rem; font-size: 1.1rem; font-weight: 700; color: #1d1d1a; }
    .jn-btn { display: inline-flex; min-height: 3rem; align-items: center; gap: .5rem; padding: 0 1.5rem; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-weight: 800; text-decoration: none; }
    .jn-btn:hover { background: #1d1d1a; } .jn-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
</style>

<header class="jn-head">
    <h1 class="jn-h1">{{ $title }}</h1>
    <span class="jn-bar" aria-hidden="true"></span>
    <p class="jn-lead">{{ $intro }}</p>
</header>

<div class="jn-wrap">
  <div class="jn-cols">
    <div class="jn-side">
    {{-- ── Ścieżki zaangażowania ── --}}
    @if ($paths)
        <nav aria-label="{{ $siteSettings->joinText('paths_label') }}">
            <ul class="jn-paths" role="list">
                @foreach ($paths as $i => [$pt, $pd, $pu, $pi, $pn, $pnl])
                    @php $bg = $pal[$i % 4]; $fg = \App\Support\ThemePalette::button($bg)['text']; @endphp
                    <li>
                        <a href="{{ $pu }}" class="jn-path" style="background: {{ $bg }}; color: {{ $fg }}">
                            <span><i class="fa-solid {{ $pi }} jn-path-i" aria-hidden="true"></i><span class="jn-path-t" style="margin-top:1rem">{{ $pt }}</span><span class="jn-path-d">{{ $pd }}</span></span>
                            <span class="jn-path-f">
                                @if ($pn !== null)<span class="jn-path-n">{{ $pn }} {{ $pnl }}</span>@else<span></span>@endif
                                <i class="fa-solid fa-arrow-right jn-path-arrow" aria-hidden="true"></i>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    </div>
    <div class="jn-main">
    {{-- ── Własne kafelki „Na skróty" z edycji strony (jeśli dodane) ── --}}
    @if ($hubLinks->isNotEmpty())
        <section class="jn-sec" aria-labelledby="jn-hub-h">
            <div class="jn-sec-h"><h2 id="jn-hub-h" class="jn-h2">{{ $siteSettings->joinText('hub_title') }}</h2></div>
            @include('partials._tiles-grid', ['tiles' => $hubLinks, 'label' => $jt('hub_title')])
        </section>
    @endif

    {{-- ── Wolontariat ── --}}
    @if ($volunteeringActive)
        <section class="jn-sec" aria-labelledby="jn-vol-h">
            <div class="jn-sec-h">
                <h2 id="jn-vol-h" class="jn-h2">{{ $siteSettings->joinText('vol_h') }}<small>{{ $siteSettings->joinText('vol_sub') }}</small></h2>
                <a href="{{ route('volunteer.index') }}" class="jn-more">{{ $siteSettings->joinText('vol_more') }} →</a>
            </div>
            @if ($ads->isEmpty())
                <p class="jn-empty">{{ $siteSettings->joinText('vol_empty') }} <a href="{{ route('contact.show') }}" class="jn-more">Kontakt →</a></p>
            @else
                <ul class="jn-cards" role="list">
                    @foreach ($ads as $ad)
                        <li class="jn-card">
                            <p class="jn-meta"><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif</span>@if ($ad->closes_at)<span><i class="fa-solid fa-calendar-day" aria-hidden="true"></i>Zgłoszenia do {{ $ad->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}</span>@endif</p>
                            <h3><a href="{{ route('volunteer.show', $ad) }}">{{ $ad->title }}</a></h3>
                            @if ($ad->lead)<p>{{ $ad->lead }}</p>@endif
                            <span class="jn-cta" aria-hidden="true">{{ $siteSettings->joinText('vol_cta') }} →</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    {{-- ── Praca ── --}}
    @if ($jobsActive)
        <section class="jn-sec" aria-labelledby="jn-job-h">
            <div class="jn-sec-h">
                <h2 id="jn-job-h" class="jn-h2">{{ $siteSettings->joinText('job_h') }}<small>{{ $jt('job_sub') !== '' ? $jt('job_sub') : 'Dołącz do zespołu '.$siteSettings->siteNameGenitive().'.' }}</small></h2>
                <a href="{{ route('praca.index') }}" class="jn-more">{{ $siteSettings->joinText('job_more') }} →</a>
            </div>
            @if ($offers->isEmpty())
                <p class="jn-empty">{{ $siteSettings->joinText('job_empty') }} <a href="{{ route('contact.show') }}" class="jn-more">Kontakt →</a></p>
            @else
                <ul class="jn-cards" role="list">
                    @foreach ($offers as $offer)
                        <li class="jn-card">
                            <p class="jn-meta"><span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $offer->jobTypeLabel() }}</span><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $offer->modeLabel() }}@if ($offer->location) · {{ $offer->location }}@endif</span>@if ($offer->closes_at)<span><i class="fa-solid fa-calendar-day" aria-hidden="true"></i>Aplikuj do {{ $offer->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}</span>@endif</p>
                            <h3><a href="{{ route('praca.show', $offer) }}">{{ $offer->title }}</a></h3>
                            @if ($offer->lead)<p>{{ $offer->lead }}</p>@endif
                            <span class="jn-cta" aria-hidden="true">{{ $siteSettings->joinText('job_cta') }} →</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    </div>
  </div>

    {{-- ── Jak to działa ── --}}
    <section class="jn-sec" aria-labelledby="jn-how-h">
        <div class="jn-sec-h"><h2 id="jn-how-h" class="jn-h2">{{ $siteSettings->joinText('how_h') }}</h2></div>
        <ol class="jn-steps">
            <li class="jn-step"><strong>{{ $siteSettings->joinText('how_1_t') }}</strong><span>{{ $siteSettings->joinText('how_1') }}</span></li>
            <li class="jn-step"><strong>{{ $siteSettings->joinText('how_2_t') }}</strong><span>{{ $siteSettings->joinText('how_2') }}</span></li>
            <li class="jn-step"><strong>{{ $siteSettings->joinText('how_3_t') }}</strong><span>{{ $siteSettings->joinText('how_3') }}</span></li>
        </ol>
    </section>

    {{-- ── Kontakt ── --}}
    <div class="jn-contact">
        <p>{{ $siteSettings->joinText('contact_text') }}</p>
        <a href="{{ route('contact.show') }}" class="jn-btn">{{ $siteSettings->joinText('contact_btn') }}<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</div>
@endsection
