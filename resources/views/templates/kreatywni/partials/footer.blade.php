{{--
    Stopka szablonu „kreatywni”: ciemna zieleń. Logo, nazwa i hasło witryny; kontakt (telefon, e-mail, adres z
    Ustawienia → Kontakt); linki z Menu nawigacji → Stopka (a gdy ich nie ma — menu główne); profile social media;
    dolny pas z prawami autorskimi, deklaracją dostępności, ustawieniami cookies i linkiem do panelu według
    Ustawienia → Ogólne → Stopka, a po prawej podpis wykonawcy z ikonką weCMS (przełącznik „Pokaż nazwę CMS w stopce”,
    poza feer.org.pl zawsze włączony). Tekst praw zaczynający się od „© rok” nie powtarza „©” po nazwie organizacji.
    Style: partials/base-css (prefiks kc-).
--}}
@php
    $footerNavItems ??= collect();
    $fo = $siteSettings->footerOptions();
    $kcLinks = $footerNavItems->filter(fn ($i) => filled($i->url));
    if ($kcLinks->isEmpty()) {
        $kcLinks = \App\Models\NavItem::forCurrentSite()->where('location', 'main')->whereNull('parent_id')->where('is_active', true)
            ->orderBy('order')->get()->filter(fn ($i) => filled($i->url) && $i->url !== '#' && (! $i->module || $siteSettings->isModuleEnabled($i->module)));
    }
    $kcPhone = trim((string) $siteSettings->contact_phone);
    $kcEmail = trim((string) $siteSettings->contact_email);
    $kcAddress = trim(implode(', ', array_filter([trim((string) $siteSettings->contact_address), trim((string) $siteSettings->contact_city)])));
    $kcLogo = $siteSettings->logoUrl();
    // Domyślny tekst praw to „© {rok} Wszystkie prawa zastrzeżone” — „© rok” jest już przed nazwą organizacji.
    $kcCopyright = trim((string) preg_replace('/^(©|\(c\)|&copy;)\s*(\d{4}\s*)?[·\-–—,]?\s*/iu', '', $fo['copyright_text']));
@endphp
@include('templates.kreatywni.partials.base-css')
<footer class="kc kc-dark kc-footer">
    <div class="kc-wrap kc-foot">
        <div>
            <a class="kc-foot-brand" href="{{ site_home_url() }}">
                @if ($kcLogo)<img src="{{ $kcLogo }}" alt="" width="72" height="72" loading="lazy">@endif
                <span class="kc-foot-name">{{ $siteSettings->site_name }}<span class="kc-sr"> — strona główna</span></span>
            </a>
            @if (filled($siteSettings->tagline))
                <p>{{ $siteSettings->tagline }}</p>
            @endif
            @if (filled($siteSettings->krs_number))
                <p>KRS: {{ $siteSettings->krs_number }}@if (filled($siteSettings->nip_number)) · NIP: {{ $siteSettings->nip_number }}@endif</p>
            @endif
        </div>

        @if ($kcPhone !== '' || $kcEmail !== '' || $kcAddress !== '')
            <div>
                <h2>Kontakt</h2>
                <ul>
                    @if ($kcPhone !== '')
                        <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $kcPhone) }}"><i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $kcPhone }}</a></li>
                    @endif
                    @if ($kcEmail !== '')
                        <li><a href="mailto:{{ $kcEmail }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $kcEmail }}</a></li>
                    @endif
                    @if ($kcAddress !== '')
                        <li><span class="kc-foot-addr"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $kcAddress }}</span></li>
                    @endif
                </ul>
            </div>
        @endif

        <div>
            @if ($kcLinks->isNotEmpty())
                <nav aria-label="Stopka">
                    <h2>Na skróty</h2>
                    <ul>
                        @foreach ($kcLinks as $item)
                            <li><a href="{{ site_href($item->url) }}" @if (str_starts_with((string) $item->url, 'http') && ! str_starts_with((string) $item->url, url('/'))) target="_blank" rel="noopener" @endif>{{ $item->label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
            @if ($siteSettings->socialLinks() !== [])
                <ul class="kc-foot-social" aria-label="Nasze profile w mediach społecznościowych">
                    @foreach ($siteSettings->socialLinks() as [$sUrl, $sIcon, $sLabel])
                        <li><a href="{{ $sUrl }}" target="_blank" rel="noopener"><i class="{{ $sIcon }}" aria-hidden="true"></i><span class="kc-sr">{{ $sLabel }} (otwiera się w nowej karcie)</span></a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="kc-wrap kc-foot-bottom">
        <p>© {{ now()->year }} {{ $siteSettings->site_name }}@if ($kcCopyright !== '') · {{ $kcCopyright }}@endif</p>
        <ul>
            @if ($fo['show_sitemap'] && \Illuminate\Support\Facades\Route::has('sitemap.page'))
                <li><a href="{{ route('sitemap.page') }}">Mapa serwisu</a></li>
            @endif
            @if ($fo['show_accessibility'] && \Illuminate\Support\Facades\Route::has('accessibility.show'))
                <li><a href="{{ route('accessibility.show') }}">Deklaracja dostępności</a></li>
            @endif
            @if ($fo['show_cookies'] && ($siteSettings->cookie_banner_enabled ?? true))
                <li><button type="button" data-cookie-settings>Ustawienia cookies</button></li>
            @endif
            @if ($fo['show_cookies'] && \Illuminate\Support\Facades\Route::has('cookies.info'))
                <li><a href="{{ route('cookies.info') }}">Informacja o cookies</a></li>
            @endif
            @if ($fo['staff_link'] && $fo['staff_url'] !== '')
                <li><a href="{{ str_starts_with($fo['staff_url'], 'http') ? $fo['staff_url'] : url($fo['staff_url']) }}">{{ $fo['staff_label'] }}</a></li>
            @endif
            @if ($fo['show_admin_link'])
                <li><a href="{{ route('admin.dashboard') }}">Panel administracyjny</a></li>
            @endif
        </ul>
        @if ($siteSettings->show_cms_credit ?? true)
            <p class="kc-foot-credit">
                @include('partials.wecms-mark')
                <a href="https://ziemowitgil.pl" target="_blank" rel="noopener">Projekt i wykonanie: Ziemowit Gil<span class="kc-sr"> (otwiera się w nowej karcie)</span></a>
            </p>
        @endif
    </div>
</footer>
