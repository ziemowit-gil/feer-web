{{--
    Stopka szablonu „bu2”: rząd małych niebieskich linków między dwiema cienkimi liniami (Mapa serwisu, linki
    z Menu nawigacji → Stopka, Deklaracja dostępności, Ustawienia cookies, RSS, strefa współpracownika, panel —
    według Ustawienia → Ogólne → Stopka), pod nim logotypy źródeł finansowania (kolekcja „logotypy partnerów /
    źródeł finansowania” z Ustawień nagłówka) i tekst praw autorskich.
--}}
@php
    $footerNavItems ??= collect();
    $fo = $siteSettings->footerOptions();
    $funding = $siteSettings->headerPartnerLogos();
@endphp
@include('templates.bu2.partials.base-css')
<footer class="bu2 bu2-footer">
    <div class="bu2-wrap">
        <nav class="bu2-foot-links" aria-label="Stopka">
            <ul>
                @if ($fo['show_sitemap'] && \Illuminate\Support\Facades\Route::has('sitemap.page'))
                    <li><a href="{{ route('sitemap.page') }}">Mapa serwisu</a></li>
                @endif
                @foreach ($footerNavItems as $item)
                    @if (filled($item->url))
                        <li><a href="{{ site_href($item->url) }}" @if (str_starts_with((string) $item->url, 'http') && ! str_starts_with((string) $item->url, url('/'))) target="_blank" rel="noopener" @endif>{{ $item->label }}</a></li>
                    @endif
                @endforeach
                @if ($fo['show_accessibility'] && \Illuminate\Support\Facades\Route::has('accessibility.show'))
                    <li><a href="{{ route('accessibility.show') }}">Deklaracja dostępności</a></li>
                @endif
                @if ($fo['show_cookies'] && ($siteSettings->cookie_banner_enabled ?? true))
                    <li><button type="button" data-cookie-settings>Ustawienia cookies</button></li>
                @endif
                @if ($fo['show_rss'] && $siteSettings->isModuleEnabled('news') && \Illuminate\Support\Facades\Route::has('feed'))
                    <li><a href="{{ route('feed') }}">Kanał RSS</a></li>
                @endif
                @if ($fo['staff_link'] && $fo['staff_url'] !== '')
                    <li><a href="{{ str_starts_with($fo['staff_url'], 'http') ? $fo['staff_url'] : url($fo['staff_url']) }}">{{ $fo['staff_label'] }}</a></li>
                @endif
                @if (auth()->user()?->isAdmin())
                    <li><a href="{{ route('admin.ustawienia.edit', ['tab' => 'general']) }}#gen-footer-h" data-footer-edit>Edytuj stopkę</a></li>
                @endif
                @if ($fo['show_admin_link'])
                    <li><a href="{{ route('admin.dashboard') }}">Panel administracyjny</a></li>
                @endif
            </ul>
        </nav>

        @if ($funding !== [])
            <ul class="bu2-funding" role="list" aria-label="Źródła finansowania">
                @foreach ($funding as $logo)
                    <li><img src="{{ $logo['url'] }}" alt="{{ $logo['alt'] }}" loading="lazy"></li>
                @endforeach
            </ul>
        @endif

        @if ($fo['copyright_text'] !== '')
            <p class="bu2-copy">{{ $fo['copyright_text'] }} · {{ $siteSettings->site_name }}</p>
        @endif
    </div>
</footer>
