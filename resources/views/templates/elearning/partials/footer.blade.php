{{-- Stopka szablonu „elearning”: granatowa, z adresem, linkami stopki i kontaktem. Style: partials/base-css (prefiks el-). --}}
@php
    $footerNavItems ??= collect();
    $elPhone = $siteSettings->contact_phone;
    $elEmail = $siteSettings->contact_email;
    $elAddress = $siteSettings->hasOfficeAddress() ? $siteSettings->officeAddressLine() : $siteSettings->registeredAddressLine();
@endphp

@include('templates.elearning.partials.base-css')
<x-banner-zone name="footer" />

<footer class="el el-foot" aria-label="Stopka strony">
    <div class="el-wrap el-foot-grid">
        <div>
            <h2>{{ $siteSettings->site_name }}</h2>
            @if (filled($elAddress))<address>{{ $elAddress }}</address>@endif
        </div>
        @if ($footerNavItems->isNotEmpty())
            <nav aria-labelledby="el-foot-links-h">
                <h2 id="el-foot-links-h">Przydatne linki</h2>
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
    <div class="el-foot-bar">
        <div class="el-wrap el-foot-bar-in">
            <span>&copy; {{ now()->year }} {{ $siteSettings->site_name }}@if ($siteSettings->show_cms_credit ?? true) &middot; Napędzane przez weCMS @endif</span>
            @if ($siteSettings->cookie_banner_enabled ?? true)<button type="button" data-cookie-settings>Ustawienia cookies</button>@endif
        </div>
    </div>
</footer>
