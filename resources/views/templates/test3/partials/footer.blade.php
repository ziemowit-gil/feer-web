{{--
    Stopka szablonu „test3”: jasnoszary pas z logo i adresem, linkami stopki i kontaktem, ciemny pasek z prawami i ustawieniami
    cookies oraz przycisk „Do góry” (pojawia się po przewinięciu strony). Style: partials/base-css (prefiks z3-).
--}}
@php
    $footerNavItems ??= collect();
    $z3Phone = $siteSettings->contact_phone;
    $z3Email = $siteSettings->contact_email;
    $z3Address = $siteSettings->hasOfficeAddress() ? $siteSettings->officeAddressLine() : $siteSettings->registeredAddressLine();
    $z3Hours = $siteSettings->contact_office_hours;
@endphp

@include('templates.test3.partials.base-css')
<x-banner-zone name="footer" />

<footer class="z3 z3-foot" aria-label="Stopka strony">
    <div class="z3-wrap z3-foot-grid">
        <div>
            <div class="z3-foot-brand">
                @if ($siteSettings->logoUrl())<img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}">@endif
                <span>{{ $siteSettings->site_name }}</span>
            </div>
            @if (filled($z3Address))<address>{{ $z3Address }}</address>@endif
        </div>

        @if ($footerNavItems->isNotEmpty())
            <nav aria-labelledby="z3-foot-links-h">
                <h2 id="z3-foot-links-h">Przydatne linki</h2>
                <ul>
                    @foreach ($footerNavItems as $item)<li><a href="{{ site_href($item->url) }}">{{ $item->label }}</a></li>@endforeach
                </ul>
            </nav>
        @endif

        @if (filled($z3Phone) || filled($z3Email) || filled($z3Hours))
            <div>
                <h2>Kontakt</h2>
                @if (filled($z3Phone))<p>Tel.: <a href="tel:{{ preg_replace('/\s+/', '', $z3Phone) }}">{{ $z3Phone }}</a></p>@endif
                @if (filled($z3Email))<p><a href="mailto:{{ $z3Email }}">{{ $z3Email }}</a></p>@endif
                @if (filled($z3Hours))<p>{!! nl2br(e($z3Hours)) !!}</p>@endif
            </div>
        @endif
    </div>

    <div class="z3-foot-bar">
        <div class="z3-wrap z3-foot-bar-in">
            <span>&copy; {{ now()->year }} {{ $siteSettings->site_name }}@if ($siteSettings->show_cms_credit ?? true) &middot; Napędzane przez weCMS @endif</span>
            @if ($siteSettings->cookie_banner_enabled ?? true)
                <button type="button" data-cookie-settings>Ustawienia cookies</button>
            @endif
        </div>
    </div>
</footer>

<button type="button" class="z3 z3-top" data-z3-top hidden><i class="fa-solid fa-chevron-up" aria-hidden="true"></i><span>Do góry</span></button>
<script>
(function () {
    var button = document.querySelector('[data-z3-top]');
    if (!button) return;
    function toggle() { button.hidden = window.scrollY < 400; }
    toggle();
    window.addEventListener('scroll', toggle, { passive: true });
    button.addEventListener('click', function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        var main = document.getElementById('main-content');
        if (main) { main.setAttribute('tabindex', '-1'); main.focus({ preventScroll: true }); }
    });
})();
</script>
