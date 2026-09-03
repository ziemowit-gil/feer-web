@php
    $footerNavItems ??= collect();
@endphp

<x-banner-zone name="footer" />

<footer class="bg-ink text-white" aria-label="Stopka strony">

    <div class="mx-auto max-w-[1400px] px-4 py-10">
        <div class="grid gap-8 md:grid-cols-3">

            {{-- Marka --}}
            <div>
                <div class="flex items-center gap-3">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-10 w-auto max-w-[80px] object-contain brightness-0 invert">
                    @else
                        <span class="grid h-10 w-10 flex-none place-items-center rounded-lg bg-white/10 text-base font-black text-white" aria-hidden="true">
                            {{ mb_substr($siteSettings->site_name, 0, 1) }}
                        </span>
                    @endif
                    <span class="text-base font-extrabold leading-tight text-white">{{ $siteSettings->site_name }}</span>
                </div>
                @if ($siteSettings->tagline)
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/70">{{ $siteSettings->tagline }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2" aria-label="Media społecznościowe">
                    @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $key => $info)
                        @php $url = $siteSettings->{$key.'_url'} ?? null; @endphp
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                aria-label="{{ $info['label'] }}">
                                <i class="{{ $info['icon'] }} text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Kontakt --}}
            <div>
                <h2 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-white/60">Kontakt</h2>
                <address class="not-italic space-y-2 text-sm text-white/80">
                    @if ($siteSettings->contact_address)
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot mt-0.5 flex-none text-white/40" aria-hidden="true"></i>
                            <span>{{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}</span>
                        </div>
                    @endif
                    @if ($siteSettings->contact_phone)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-phone flex-none text-white/40" aria-hidden="true"></i>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                                class="rounded transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{{ $siteSettings->contact_phone }}</a>
                        </div>
                    @endif
                    @if ($siteSettings->contact_email)
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-envelope mt-0.5 flex-none text-white/40" aria-hidden="true"></i>
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                                class="rounded break-all transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{{ $siteSettings->contact_email }}</a>
                        </div>
                    @endif
                </address>

                @if ($siteSettings->hasRegistryData())
                    <div class="mt-5 space-y-1 text-xs text-white/40">
                        @if ($siteSettings->krs_number)<div>KRS: {{ $siteSettings->krs_number }}</div>@endif
                        @if ($siteSettings->nip_number)<div>NIP: {{ $siteSettings->nip_number }}</div>@endif
                        @if ($siteSettings->regon_number)<div>REGON: {{ $siteSettings->regon_number }}</div>@endif
                    </div>
                @endif
            </div>

            {{-- Nawigacja --}}
            <div>
                <h2 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-white/60">Nawigacja</h2>
                <ul class="space-y-1.5 text-sm">
                    <li><a href="{{ site_route('home') }}" class="rounded text-white/80 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Strona główna</a></li>
                    @if ($siteSettings->isModuleEnabled('news'))
                        <li><a href="{{ route('news.index') }}" class="rounded text-white/80 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Aktualności</a></li>
                    @endif
                    <li><a href="{{ route('contact.show') }}" class="rounded text-white/80 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Kontakt</a></li>
                    @foreach ($footerNavItems->take(4) as $item)
                        <li><a href="{{ $item->url }}" class="rounded text-white/80 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-3 px-4 py-4 text-xs text-white/50">
            <span>
                &copy; {{ now()->year }} {{ $siteSettings->site_name }}
                @if ($siteSettings->show_cms_credit ?? true)
                    &middot;
                    <span class="opacity-40 transition-opacity duration-200 hover:opacity-100 focus-within:opacity-100">
                        Napędzane przez <span class="font-bold">weCMS</span>
                        &middot; Projekt i wykonanie <a href="mailto:ziemowit.gil@gmail.com" class="hover:text-white">Ziemowit Gil</a>
                    </span>
                @endif
            </span>
            <nav aria-label="Linki stopki">
                <ul class="flex flex-wrap gap-x-4 gap-y-1">
                    <li><a href="{{ route('accessibility.show') }}" class="rounded transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Deklaracja dostępności</a></li>
                    <li><a href="{{ route('sitemap.page') }}" class="rounded transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Mapa strony</a></li>
                </ul>
            </nav>
            <a href="{{ route('admin.dashboard') }}" class="rounded text-white/20 transition hover:text-white/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
               aria-label="Panel administracyjny">
                <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
            </a>
        </div>
    </div>

</footer>
