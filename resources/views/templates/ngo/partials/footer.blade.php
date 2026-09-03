@php
    $footerNavItems ??= collect();
    $half = (int) ceil($footerNavItems->count() / 2);
    $col1 = $footerNavItems->take($half);
    $col2 = $footerNavItems->skip($half);
@endphp

<x-banner-zone name="footer" />

<footer class="bg-brand text-white" aria-label="Stopka strony">

    {{-- Górna część: misja + kolumny --}}
    <div class="mx-auto max-w-[1400px] px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-4">

            {{-- Kolumna 1: Misja + logo --}}
            <div class="lg:col-span-1">
                <div class="flex items-center gap-3">
                    @if ($siteSettings->logoUrl())
                        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name }}"
                            class="h-12 w-auto max-w-[48px] rounded object-contain brightness-0 invert">
                    @endif
                    <span class="text-base font-extrabold leading-tight text-white">
                        {{ $siteSettings->site_name }}
                    </span>
                </div>
                @if ($siteSettings->tagline)
                    <p class="mt-2 text-sm leading-relaxed text-white/85">{{ $siteSettings->tagline }}</p>
                @endif

                {{-- Social --}}
                <div class="mt-4 flex flex-wrap gap-2" aria-label="Media społecznościowe">
                    @foreach (\App\Models\SiteSetting::SOCIAL_KEYS as $key => $info)
                        @php $url = $siteSettings->{$key.'_url'} ?? null; @endphp
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/25"
                                aria-label="{{ $info['label'] }}">
                                <i class="{{ $info['icon'] }} text-sm" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Kolumna 2: Nawigacja (col 1) --}}
            <div>
                <h2 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-white/80">Nawigacja</h2>
                @if ($col1->isNotEmpty())
                    <ul class="space-y-1.5">
                        @foreach ($col1 as $item)
                            <li>
                                <a href="{{ $item->url }}" class="text-sm text-white/75 transition hover:text-white">
                                    &rsaquo; {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-white/70">—</p>
                @endif
            </div>

            {{-- Kolumna 3: Nawigacja (col 2) --}}
            <div>
                <h2 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-white/80">Ważne linki</h2>
                @if ($col2->isNotEmpty())
                    <ul class="space-y-1.5">
                        @foreach ($col2 as $item)
                            <li>
                                <a href="{{ $item->url }}" class="text-sm text-white/75 transition hover:text-white">
                                    &rsaquo; {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-white/70">—</p>
                @endif

                {{-- Dane rejestrowe --}}
                @if ($siteSettings->hasRegistryData())
                    <div class="mt-6 space-y-1 text-xs text-white/70">
                        @if ($siteSettings->krs_number)
                            <div>KRS: {{ $siteSettings->krs_number }}</div>
                        @endif
                        @if ($siteSettings->nip_number)
                            <div>NIP: {{ $siteSettings->nip_number }}</div>
                        @endif
                        @if ($siteSettings->regon_number)
                            <div>REGON: {{ $siteSettings->regon_number }}</div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Kolumna 4: Kontakt --}}
            <div>
                <h2 class="mb-3 text-xs font-extrabold uppercase tracking-widest text-white/80">Kontakt</h2>
                <address class="not-italic space-y-2 text-sm text-white/75">
                    @if ($siteSettings->contact_address)
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot mt-0.5 flex-none text-white/70" aria-hidden="true"></i>
                            <span>{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span>
                        </div>
                    @endif
                    @if ($siteSettings->contact_phone)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-phone flex-none text-white/70" aria-hidden="true"></i>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                                class="hover:text-white transition">{{ $siteSettings->contact_phone }}</a>
                        </div>
                    @endif
                    @if ($siteSettings->contact_email)
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-envelope mt-0.5 flex-none text-white/70" aria-hidden="true"></i>
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                                class="break-all hover:text-white transition">{{ $siteSettings->contact_email }}</a>
                        </div>
                    @endif
                    @if ($siteSettings->contact_office_hours)
                        <div class="mt-3 text-xs text-white/70 leading-relaxed whitespace-pre-line">
                            {{ $siteSettings->contact_office_hours }}
                        </div>
                    @endif
                </address>
            </div>
        </div>
    </div>

    {{-- Pasek dolny --}}
    <div class="border-t border-white/20 bg-black/20">
        <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-3 px-4 py-2.5 text-xs text-white/85">
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
                    <li><a href="{{ route('accessibility.show') }}" class="hover:text-white transition">Deklaracja dostępności</a></li>
                    <li><a href="{{ route('sitemap.page') }}" class="hover:text-white transition">Mapa strony</a></li>
                    @foreach ($footerNavItems->take(3) as $item)
                        <li><a href="{{ $item->url }}" class="hover:text-white transition">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <a href="{{ route('admin.dashboard') }}" class="text-white/20 transition hover:text-white/60"
               aria-label="Panel administracyjny">
                <i class="fa-solid fa-gear text-sm" aria-hidden="true"></i>
            </a>
        </div>
    </div>

</footer>
