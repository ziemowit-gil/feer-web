{{--
    Stopka szablonu FEER (Brand book 2024): partnerzy na jasnym tle, a pod nimi jeden ciemny pas (#1D1D1A) spójny
    z paskiem górnym — marka i prawa autorskie, linki, social. Płasko, bez przezroczystości na tekście:
    biały tekst na #1D1D1A ma 16,9:1; ikony social mają białą ramkę i odwracają się po najechaniu.
--}}
@php
    $partners ??= collect();
    $footerNavItems ??= collect();
    $footerSocials = [
        [$siteSettings->substack_url, 'fa-solid fa-pen-nib', 'Substack'],
        [$siteSettings->facebook_url, 'bi bi-facebook', 'Facebook'],
        [$siteSettings->facebook_group_url, 'bi bi-people-fill', 'Grupa na Facebooku'],
        [$siteSettings->twitter_url, 'bi bi-twitter-x', 'X (Twitter)'],
        [$siteSettings->instagram_url, 'bi bi-instagram', 'Instagram'],
        [$siteSettings->linkedin_url, 'bi bi-linkedin', 'LinkedIn'],
        [$siteSettings->youtube_url, 'bi bi-youtube', 'YouTube'],
    ];
    $footLink = 'rounded-sm underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink';
@endphp

<footer>
    @if ($partners->isNotEmpty())
        <div class="bg-gray-50" role="region" aria-label="Partnerzy i systemy powiązane">
            <div class="mx-auto max-w-6xl px-4 py-12">
                <h2 class="mb-2 text-center text-lg font-bold uppercase tracking-wide text-ink">Współpracujemy</h2>
                <p class="mx-auto mb-8 max-w-xl text-center text-sm text-muted">Działamy razem z organizacjami i instytucjami, które nas wspierają.</p>
                <ul class="mx-auto flex max-w-4xl flex-wrap items-center justify-center gap-x-12 gap-y-8" role="list">
                    @foreach ($partners as $partner)
                        <li>
                            @if ($partner->url)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener" class="block rounded-sm grayscale transition hover:grayscale-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }} (otwiera się w nowej karcie)" class="h-12 w-auto object-contain">
                                </a>
                            @else
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-12 w-auto object-contain grayscale">
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <x-banner-zone name="footer" />

    <div class="relative bg-ink text-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 text-sm lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-start lg:gap-12">

            {{-- Marka --}}
            <div class="flex min-w-0 items-start gap-3">
                @if ($siteSettings->logoUrl())
                    <span class="flex-none rounded-md bg-white p-1.5"><img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-9 w-9 object-contain"></span>
                @else
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-md bg-white text-lg font-bold text-ink" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                <p class="leading-relaxed">
                    <span class="block text-base font-bold">{{ $siteSettings->site_name }}</span>
                    &copy; {{ now()->year }} Wszystkie prawa zastrzeżone
                    @if ($siteSettings->show_cms_credit ?? true)
                        <span class="mt-1 block">Napędzane przez <span class="font-bold">weCMS</span> · <a href="mailto:ziemowit.gil@gmail.com" class="{{ $footLink }}">Ziemowit Gil</a></span>
                    @endif
                </p>
            </div>

            {{-- Linki --}}
            <nav aria-label="Linki stopki">
                <ul class="space-y-2" role="list">
                    @foreach ($footerNavItems as $item)
                        <li><a href="{{ $item->url }}" class="{{ $footLink }}">{{ $item->label }}</a></li>
                    @endforeach
                    <li><a href="{{ route('accessibility.show') }}" class="{{ $footLink }}">Deklaracja dostępności</a></li>
                    <li><a href="{{ route('sitemap.page') }}" class="{{ $footLink }}">Mapa strony</a></li>
                    <li><a href="{{ url('/strefa-wspolpracownika-feer') }}" class="{{ $footLink }}">Strefa współpracownika</a></li>
                </ul>
            </nav>

            {{-- Social --}}
            <div>
                <ul class="flex flex-wrap items-center gap-2" role="list" aria-label="Media społecznościowe">
                    @foreach ($footerSocials as [$sUrl, $sIcon, $sLabel])
                        @if (filled($sUrl))
                            <li>
                                <a href="{{ $sUrl }}" target="_blank" rel="noopener" aria-label="{{ $sLabel }} — otwiera się w nowej karcie"
                                   class="flex h-10 w-10 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                    <i class="{{ $sIcon }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endif
                    @endforeach
                    @if ($siteSettings->isModuleEnabled('news'))
                        <li>
                            <a href="{{ route('feed') }}" aria-label="Kanał RSS z aktualnościami"
                               class="flex h-10 w-10 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                <i class="bi bi-rss" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="absolute bottom-3 right-4 rounded-sm text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Panel administracyjny">
            <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
        </a>
    </div>
</footer>
