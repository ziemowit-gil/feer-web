{{-- Stopka szablonu „podcasts”: granatowa, z adresem, linkami stopki i kontaktem. Style: partials/base-css (prefiks pc-). --}}
@php
    $footerNavItems ??= collect();
    $elPhone = $siteSettings->contact_phone;
    $elEmail = $siteSettings->contact_email;
    $elAddress = $siteSettings->hasOfficeAddress() ? $siteSettings->officeAddressLine() : $siteSettings->registeredAddressLine();
@endphp

@include('templates.podcasts.partials.base-css')
<x-banner-zone name="footer" />

<footer class="pc pc-foot" aria-label="Stopka strony">
    <div class="pc-wrap pc-foot-grid">
        <div>
            <h2>{{ $siteSettings->site_name }}</h2>
            @if (filled($elAddress))<address>{{ $elAddress }}</address>@endif
        </div>
        @if ($footerNavItems->isNotEmpty())
            <nav aria-labelledby="pc-foot-links-h">
                <h2 id="pc-foot-links-h">Przydatne linki</h2>
                <ul>@foreach ($footerNavItems as $item)<li><a href="{{ site_href($item->url) }}">{{ $item->label }}</a></li>@endforeach</ul>
            </nav>
        @endif
        @if (filled($elPhone) || filled($elEmail))
            <div>
                <h2>Kontakt</h2>
                @if (filled($elPhone))<p>Tel.: <a href="tel:{{ preg_replace('/\s+/', '', $elPhone) }}">{{ $elPhone }}</a></p>@endif
                @if (filled($elEmail))<p><a href="mailto:{{ $elEmail }}">{{ $elEmail }}</a></p>@endif
            </div>
        @endif
    </div>
    <div class="pc-foot-bar">
        <div class="pc-wrap pc-foot-bar-in">
            <span>&copy; {{ now()->year }} {{ $siteSettings->site_name }}@if ($siteSettings->show_cms_credit ?? true) &middot; Napędzane przez weCMS @endif</span>
            @if ($siteSettings->cookie_banner_enabled ?? true)<button type="button" data-cookie-settings>Ustawienia cookies</button>@endif
        </div>
    </div>
</footer>
