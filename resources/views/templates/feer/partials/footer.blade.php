{{--
    Stopka szablonu FEER (Brand book 2024): partnerzy na jasnym tle, a pod nimi jeden niski ciemny pas (#1D1D1A) spójny
    z paskiem górnym — w jednym rzędzie: marka i prawa autorskie, linki, social (zwarta wersja). Płasko, bez przezroczystości na tekście:
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
        <div class="bg-white" role="region" aria-label="Partnerzy i systemy powiązane">
            <div class="mx-auto max-w-6xl px-4 py-7">
                <h2 class="mb-5 text-center text-sm font-bold uppercase tracking-wide text-ink">Współpracujemy</h2>
                <ul class="mx-auto flex max-w-4xl flex-wrap items-center justify-center gap-x-10 gap-y-5" role="list">
                    @foreach ($partners as $partner)
                        <li>
                            @if ($partner->url)
                                <a href="{{ $partner->url }}" target="_blank" rel="noopener" class="block rounded-sm grayscale contrast-200 transition hover:contrast-100 hover:grayscale-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }} (otwiera się w nowej karcie)" class="h-10 w-auto object-contain mix-blend-multiply">
                                </a>
                            @else
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-10 w-auto object-contain mix-blend-multiply grayscale contrast-200">
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <x-banner-zone name="footer" />

    <div class="relative bg-ink text-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 pr-12 text-sm lg:flex-row lg:items-center lg:justify-between lg:gap-8">

            {{-- Marka --}}
            <div class="flex min-w-0 items-center gap-3">
                @if ($siteSettings->logoUrl())
                    <span class="flex-none rounded bg-white p-1"><img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-7 w-7 object-contain"></span>
                @else
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-white font-bold text-ink" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                <p class="leading-snug">
                    <span class="font-bold">{{ $siteSettings->site_name }}</span>
                    <span class="block">
                        &copy; {{ now()->year }} Wszystkie prawa zastrzeżone
                        @if ($siteSettings->show_cms_credit ?? true)
                            · Napędzane przez <span class="font-bold">weCMS</span> · <a href="mailto:ziemowit.gil@gmail.com" class="{{ $footLink }}">Ziemowit Gil</a>
                        @endif
                    </span>
                </p>
            </div>

            {{-- Linki --}}
            <nav aria-label="Linki stopki">
                <ul class="flex flex-wrap gap-x-5 gap-y-2" role="list">
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
                                   class="flex h-9 w-9 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                    <i class="{{ $sIcon }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endif
                    @endforeach
                    @if ($siteSettings->isModuleEnabled('news'))
                        <li>
                            <a href="{{ route('feed') }}" aria-label="Kanał RSS z aktualnościami"
                               class="flex h-9 w-9 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
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
