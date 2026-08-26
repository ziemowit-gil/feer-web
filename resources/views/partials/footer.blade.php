@php
    $partners ??= collect();
    $footerNavItems ??= collect();
@endphp

@if (($siteSettings->site_template ?? 'default') === 'municipality')
    @include('templates.municipality.partials.footer')
@elseif (($siteSettings->site_template ?? 'default') === 'ngo')
    @include('templates.ngo.partials.footer')
@else

<footer>
    @if ($partners->isNotEmpty())
        <div class="mx-auto max-w-6xl px-4 py-8" role="region" aria-label="Partnerzy i systemy powiązane">
            <h2 class="mb-6 text-center text-xl font-bold text-ink">Współpracujemy</h2>
            <div class="flex flex-wrap items-center justify-center gap-8">
            @foreach ($partners as $partner)
                @if ($partner->url)
                    <a href="{{ $partner->url }}" target="_blank" rel="noopener" class="opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0">
                        <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-10 w-auto object-contain">
                    </a>
                @else
                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-10 w-auto object-contain opacity-70 grayscale">
                @endif
            @endforeach
            </div>
        </div>
    @endif

    <x-banner-zone name="footer" />

    <div class="relative border-t border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-5 text-sm text-muted">
            <div class="flex items-center justify-between gap-6">

                {{-- Marka --}}
                <div class="flex shrink-0 items-center gap-2.5">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-8 w-8 flex-none rounded object-contain">
                    @else
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded bg-brand text-sm font-bold text-white">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
                    @endif
                    <span>
                        &copy; {{ now()->year }} {{ $siteSettings->site_name }}
                        @if ($siteSettings->show_cms_credit ?? true)
                            &middot; <span class="font-bold">weCMS</span>
                        @endif
                    </span>
                </div>

                {{-- Nawigacja --}}
                <nav aria-label="Linki stopki">
                    <ul class="flex flex-wrap gap-x-5 gap-y-1">
                        @foreach ($footerNavItems as $item)
                            <li><a href="{{ $item->url }}" class="hover:text-brand">{{ $item->label }}</a></li>
                        @endforeach
                        <li><a href="{{ route('accessibility.show') }}" class="hover:text-brand">Deklaracja dostępności</a></li>
                        <li><a href="{{ route('sitemap.page') }}" class="hover:text-brand">Mapa strony</a></li>
                        <li><a href="{{ url('/strefa-wspolpracownika-feer') }}" class="hover:text-brand">Strefa współpracownika</a></li>
                    </ul>
                </nav>

                {{-- Social + admin --}}
                <div class="flex items-center gap-2">
                    @if ($siteSettings->substack_url)
                        <a href="{{ $siteSettings->substack_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand font-bold text-white transition hover:bg-brand-dark" aria-label="Substack">
                            <i class="fa-solid fa-pen-nib text-sm" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->facebook_url)
                        <a href="{{ $siteSettings->facebook_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="Facebook">
                            <i class="bi bi-facebook" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->facebook_group_url)
                        <a href="{{ $siteSettings->facebook_group_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="Grupa Facebook">
                            <i class="bi bi-people-fill" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->twitter_url)
                        <a href="{{ $siteSettings->twitter_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="Twitter / X">
                            <i class="bi bi-twitter-x" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->instagram_url)
                        <a href="{{ $siteSettings->instagram_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="Instagram">
                            <i class="bi bi-instagram" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->linkedin_url)
                        <a href="{{ $siteSettings->linkedin_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="LinkedIn">
                            <i class="bi bi-linkedin" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if ($siteSettings->youtube_url)
                        <a href="{{ $siteSettings->youtube_url }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="YouTube">
                            <i class="bi bi-youtube" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if ($siteSettings->isModuleEnabled('news'))
                        <a href="{{ route('feed') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-dark" aria-label="Kanał RSS z aktualnościami">
                            <i class="bi bi-rss" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>

            </div>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="absolute bottom-2 right-3 text-muted transition hover:text-brand" aria-label="Panel administracyjny">
            <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
        </a>
    </div>
</footer>

@endif
