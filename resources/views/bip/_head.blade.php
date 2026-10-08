{{-- Wspólny nagłówek stron BIP: logo, tytuł, podtytuł, powrót. Zmienne: $bipTitle (opcjonalnie), $bipSub (opcjonalnie). --}}
@php
    $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');
    $bipTitle = $bipTitle ?? 'Biuletyn Informacji Publicznej';
    $bipSub = $bipSub ?? $siteSettings->siteNameGenitive();
    $bipIsHome = request()->routeIs('bip');
@endphp
@if (! request()->attributes->get('bip_css'))
    @php request()->attributes->set('bip_css', true); @endphp
    <style>
        /* BIP: płasko, kontrastowo, zwykły CSS (niezależny od zbudowanych klas Tailwinda). */
        .bip-head { border-top: 6px solid var(--color-brand-dark); border-bottom: 2px solid #1d1d1a; background: #fff; }
        .bip-head-in { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; max-width: 72rem; margin: 0 auto; padding: 1.25rem 1rem; }
        .bip-head-brand { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .bip-head-brand img { height: 3.25rem; width: auto; flex: none; }
        .bip-head-k { margin: 0; font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #4b5563; }
        .bip-head-t { margin: .1rem 0 0; font-size: 1.5rem; line-height: 1.2; font-weight: 800; color: #1d1d1a; }
        .bip-head-s { margin: .15rem 0 0; font-size: .95rem; color: #374151; }
        .bip-back { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; padding: 0 1rem; border: 2px solid #1d1d1a; border-radius: .375rem; font-size: .875rem; font-weight: 800; color: #1d1d1a; text-decoration: none; }
        .bip-back:hover { background: #1d1d1a; color: #fff; } .bip-back:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
        .bip-wrap { max-width: 72rem; margin: 0 auto; padding: 2rem 1rem; display: grid; gap: 2rem; }
        @media (min-width: 1024px) { .bip-wrap { grid-template-columns: 16rem minmax(0, 1fr); align-items: start; } }
        .bip-cat { margin: 2.25rem 0 .75rem; padding-bottom: .4rem; border-bottom: 3px solid #1d1d1a; font-size: 1.05rem; font-weight: 800; color: #1d1d1a; }
        .bip-cat:first-of-type { margin-top: 1rem; }
        .bip-docs { list-style: none; margin: 0; padding: 0; display: grid; gap: .6rem; }
        .bip-doc { display: grid; grid-template-columns: 1fr auto; gap: .35rem 1rem; align-items: start; padding: .9rem 1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; }
        .bip-doc:hover, .bip-doc:focus-within { border-color: var(--color-brand-dark); }
        .bip-doc-t { font-size: 1.05rem; font-weight: 800; color: #1d1d1a; text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; }
        .bip-doc-t:hover { color: var(--color-brand-dark); } .bip-doc-t:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; border-radius: .25rem; }
        .bip-doc-s { grid-column: 1; margin: 0; font-size: .9rem; color: #374151; }
        .bip-doc-files { grid-column: 2; grid-row: 1 / span 2; display: inline-flex; align-items: center; gap: .4rem; align-self: start; padding: .25rem .6rem; border-radius: .25rem; background: #1d1d1a; color: #fff; font-size: .75rem; font-weight: 800; white-space: nowrap; }
        .bip-doc-meta { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; margin: .25rem 0 0; font-size: .8rem; color: #374151; }
        .bip-doc-meta dt { display: inline; font-weight: 700; } .bip-doc-meta dd { display: inline; margin: 0; }
        .bip-doc-meta > div { display: inline-flex; gap: .3rem; }
        .bip-h2 { margin: 2.5rem 0 .25rem; font-size: 1.35rem; font-weight: 800; color: #1d1d1a; }
        .bip-tbl { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .bip-tbl th { padding: .6rem .9rem; text-align: left; font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #1d1d1a; border-bottom: 2px solid #1d1d1a; }
        .bip-tbl td { padding: .7rem .9rem; border-bottom: 1px solid #d1d5db; vertical-align: top; color: #1d1d1a; }
        .bip-tbl tbody tr:hover { background: #f3f4f6; }
        .bip-op { display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .55rem; border-radius: .25rem; font-size: .72rem; font-weight: 800; border: 2px solid currentColor; }
        .bip-op.is-created { color: #166534; } .bip-op.is-updated { color: #1e40af; } .bip-op.is-deleted { color: #991b1b; } .bip-op.is-other { color: #374151; }
        .bip-intro { max-width: 44rem; }
        .bip-intro h2 { margin-top: 0; }
    </style>
@endif
<header class="bip-head">
    <div class="bip-head-in">
        <div class="bip-head-brand">
            <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej">
            <div class="min-w-0">
                @if ($bipIsHome)
                    <p class="bip-head-k">{{ $siteSettings->site_name }}</p>
                    <h1 class="bip-head-t">Biuletyn Informacji Publicznej</h1>
                    <p class="bip-head-s">Dokumenty i informacje publiczne {{ $siteSettings->siteNameGenitive() }}</p>
                @else
                    <p class="bip-head-k"><a href="{{ route('bip') }}" class="underline hover:text-ink focus-visible:outline-2 focus-visible:outline-ink">Biuletyn Informacji Publicznej</a> › {{ $siteSettings->site_name }}</p>
                    <p class="bip-head-t">{{ $bipTitle }}</p>
                    @if ($bipSub)<p class="bip-head-s">{{ $bipSub }}</p>@endif
                @endif
            </div>
        </div>
        <a href="{{ route('home') }}" class="bip-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Strona główna organizacji</a>
    </div>
</header>
