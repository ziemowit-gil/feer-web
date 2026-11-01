@php
    $footerNavItems ??= collect();
    $z2Socials = $siteSettings->socialLinks(2);
    $z2Phone = $siteSettings->contact_phone;
@endphp

<x-banner-zone name="footer" />

<style>
    .z2-footer { --z2-blue: #0b4f9e; background: #efefef; color: #1a1a1a; }
    .z2-footer .z2-wrap { max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    .z2-foot-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 2rem; padding: 3rem 0; align-items: start; }
    .z2-foot-brand { display: flex; align-items: center; gap: 1rem; color: var(--z2-blue); font-weight: 800; font-size: 1.2rem; line-height: 1.15; }
    .z2-foot-brand img { height: 4rem; width: auto; max-width: 5rem; object-fit: contain; }
    .z2-footer ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .6rem; }
    .z2-footer a { color: var(--z2-blue); text-decoration: underline; font-size: .9rem; }
    .z2-footer a:hover { color: #083a75; }
    .z2-foot-tel { display: block; font-weight: 700; font-size: 1rem; margin-top: .25rem; }
    .z2-foot-bar { background: var(--z2-blue); color: #fff; font-size: .75rem; padding: .6rem 0; }
    .z2-foot-bar a, .z2-foot-bar button { color: #fff; font-size: .75rem; background: none; border: 0; padding: 0; cursor: pointer; text-decoration: underline; }
    .z2-side { position: fixed; bottom: 1.5rem; z-index: 35; display: flex; flex-direction: column; background: #fff; border: 3px solid var(--z2-blue); }
    .z2-side a { display: flex; height: 2.75rem; width: 3rem; align-items: center; justify-content: center; color: var(--z2-blue); font-size: 1.2rem; text-decoration: none; }
    .z2-side a:hover { background: var(--z2-blue); color: #fff; }
    .z2-side-l { left: 0; border-left: 0; }
    .z2-side-r { right: 0; border-right: 0; }
    @media (max-width: 800px) { .z2-foot-grid { grid-template-columns: 1fr; padding: 2rem 0; } .z2-side { display: none; } }
</style>

@if ($z2Socials)
    <nav class="z2-side z2-side-l" aria-label="Media społecznościowe">
        @foreach ($z2Socials as [$url, $icon, $label])
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }} — otwiera się w nowej karcie"><i class="{{ $icon }}" aria-hidden="true"></i></a>
        @endforeach
    </nav>
@endif
@if (filled($z2Phone))
    <div class="z2-side z2-side-r">
        <a href="tel:{{ preg_replace('/\s+/', '', $z2Phone) }}" aria-label="Zadzwoń: {{ $z2Phone }}"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
    </div>
@endif

<footer class="z2-footer" aria-label="Stopka strony">
    <div class="z2-wrap">
        <div class="z2-foot-grid">
            <div class="z2-foot-brand">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">
                @endif
                <span>{{ $siteSettings->site_name }}</span>
            </div>
            <nav aria-label="Linki stopki">
                <ul>
                    @foreach ($footerNavItems as $item)
                        <li><a href="{{ site_href($item->url) }}">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <address style="font-style:normal;font-size:.9rem;line-height:1.6">
                @if (filled($z2Phone))
                    Całodobowy numer kontaktowy:
                    <a class="z2-foot-tel" href="tel:{{ preg_replace('/\s+/', '', $z2Phone) }}">{{ $z2Phone }}</a>
                @endif
                @if ($siteSettings->contact_address || $siteSettings->contact_city)
                    <div style="margin-top:.75rem">{{ $siteSettings->contact_address }}@if ($siteSettings->contact_city), {{ $siteSettings->contact_city }}@endif</div>
                @endif
                @if ($siteSettings->contact_email)
                    <a href="mailto:{{ $siteSettings->contact_email }}">{{ $siteSettings->contact_email }}</a>
                @endif
            </address>
        </div>
    </div>
    <div class="z2-foot-bar">
        <div class="z2-wrap" style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem">
            <span>&copy; {{ now()->year }} {{ $siteSettings->site_name }}
                @if ($siteSettings->show_cms_credit ?? true)
                    &middot; Napędzane przez weCMS
                @endif
            </span>
            @if ($siteSettings->cookie_banner_enabled ?? true)
                <button type="button" data-cookie-settings>Ustawienia cookies</button>
            @endif
        </div>
    </div>
</footer>
