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
    $fo = $siteSettings->footerOptions(); // Ustawienia → Ogólne → Stopka
    $footLink = 'rounded-sm font-semibold underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink';
@endphp
<style>@media (min-width: 1024px) { .social-row { flex-wrap: nowrap !important; } }</style>

<footer>
    @if ($partners->isNotEmpty())
        <div class="bg-white" role="region" aria-label="Partnerzy i systemy powiązane">
            {{-- Odstępy stylem w znaczniku — nie zależą od tego, czy klasy trafiły do zbudowanego CSS. --}}
            <div class="mx-auto max-w-6xl px-4" style="padding-top: 1.75rem; padding-bottom: 1.75rem">
                <h2 class="text-center text-sm font-bold uppercase tracking-wide text-ink" style="margin-bottom: 1.25rem">Współpracujemy</h2>
                {{-- Jeden rząd logotypów; na wąskich ekranach przewijany w poziomie. --}}
                <ul class="mx-auto flex max-w-5xl items-center justify-start overflow-x-auto pb-1 lg:justify-center" style="column-gap: 2.5rem; scrollbar-width: thin" role="list">
                    @foreach ($partners as $partner)
                        <li class="flex-none">
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
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 text-sm lg:flex-row lg:items-center lg:justify-between lg:gap-10" style="padding-top: 1.75rem; padding-bottom: 1.5rem; row-gap: 1.25rem; column-gap: 2.5rem">

            {{-- Marka --}}
            <div class="flex min-w-0 items-center gap-3 lg:max-w-sm lg:flex-none">
                @if ($siteSettings->logoUrl())
                    <span class="flex-none rounded bg-white p-1"><img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-7 w-7 object-contain"></span>
                @else
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-white font-bold text-ink" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                @endif
                <p class="leading-snug">
                    <span class="font-bold">{{ $siteSettings->site_name }}</span>
                    <span class="block">
                        {{ $fo['copyright_text'] }}
                        @if ($siteSettings->show_cms_credit ?? true)
                            {{ $fo['copyright_text'] !== '' ? '·' : '' }} Napędzane przez <span class="font-bold">weCMS</span> · <a href="mailto:ziemowit.gil@gmail.com" class="{{ $footLink }}">Ziemowit Gil</a>
                        @endif
                    </span>
                </p>
            </div>

            {{-- Linki --}}
            <nav aria-label="Linki stopki" class="lg:min-w-0 lg:flex-1" style="font-size: 1rem; line-height: 1.6rem">
                <ul class="flex flex-wrap lg:justify-center" style="column-gap: 2.5rem; row-gap: .75rem" role="list">
                    @foreach ($footerNavItems as $item)
                        <li><a href="{{ site_href($item->url) }}" class="{{ $footLink }}">{{ $item->label }}</a></li>
                    @endforeach
                    @if ($fo['show_accessibility'])<li><a href="{{ route('accessibility.show') }}" class="{{ $footLink }}">Deklaracja dostępności</a></li>@endif
                    @if ($fo['show_sitemap'])<li><a href="{{ route('sitemap.page') }}" class="{{ $footLink }}">Mapa strony</a></li>@endif
                    @if ($fo['show_cookies'] && ($siteSettings->cookie_banner_enabled ?? true))
                        <li><button type="button" data-cookie-settings class="{{ $footLink }}">Ustawienia cookies</button></li>
                    @endif
                </ul>
            </nav>

            {{-- Social — jeden rząd (bez łamania ikon); na wąskich ekranach zawija się dopiero przy braku miejsca. --}}
            <div style="flex: none">
                <ul class="social-row flex items-center" style="flex-wrap: wrap; gap: .75rem" role="list" aria-label="Media społecznościowe i skróty">
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
                    @if ($fo['show_rss'] && $siteSettings->isModuleEnabled('news'))
                        <li>
                            <a href="{{ route('feed') }}" aria-label="Kanał RSS z aktualnościami"
                               class="flex h-9 w-9 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                                <i class="bi bi-rss" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
@if ($fo['staff_link'] && $fo['staff_url'] !== '')
                    <li>
                        <a href="{{ str_starts_with($fo['staff_url'], 'http') ? $fo['staff_url'] : url($fo['staff_url']) }}" aria-label="{{ $fo['staff_label'] }}" title="{{ $fo['staff_label'] }}"
                           class="flex h-9 w-9 items-center justify-center rounded-md border border-white text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink">
                            <i class="bi bi-person-badge" aria-hidden="true"></i>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Skróty administratora (panel, edycja stopki) — osobny, cienki rząd zamiast nakładania się na treść. --}}
        @if ($fo['show_admin_link'] || auth()->user()?->isAdmin())
            <div class="mx-auto flex max-w-6xl items-center justify-end gap-4 px-4 text-xs" style="padding-bottom: 1rem; column-gap: 1.25rem">
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('admin.ustawienia.edit', ['tab' => 'general']) }}#gen-footer-h" class="rounded-sm font-bold text-white underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" data-footer-edit>
                        <i class="fa-solid fa-pen mr-1" aria-hidden="true"></i>Edytuj stopkę
                    </a>
                @endif
                @if ($fo['show_admin_link'])
                    <a href="{{ route('admin.dashboard') }}" class="rounded-sm text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Panel administracyjny">
                        <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @endif
        {{-- Metryczka strony („Pokaż metkę”) — zawsze w stopce (layouts/site przekazuje $footerMetka). --}}
        @if ($footerMetka ?? null)
            <div class="mx-auto max-w-6xl border-t px-4" style="border-color: rgba(255,255,255,.18); padding-bottom: .75rem">
                @include('partials.page-metka', ['page' => $footerMetka, 'onDark' => true])
            </div>
        @endif
    </div>
</footer>
