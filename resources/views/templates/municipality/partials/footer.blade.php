@php
    $footerNavItems ??= collect();
    $half = (int) ceil($footerNavItems->count() / 2);
    $col1 = $footerNavItems->take($half);
    $col2 = $footerNavItems->skip($half);
@endphp

<x-banner-zone name="footer" />

<footer class="bg-brand text-white" aria-label="Stopka strony">

    <div class="mx-auto max-w-[1400px] px-4 py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Kolumna 1: Przydatne linki --}}
            <div>
                <h2 class="mb-4 text-xs font-extrabold uppercase tracking-widest text-white/60">
                    Przydatne linki
                </h2>
                @if ($col1->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($col1 as $item)
                            <li>
                                <a href="{{ $item->url }}" class="text-sm text-white/80 hover:text-white transition">
                                    &rsaquo; {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-white/40">—</p>
                @endif
            </div>

            {{-- Kolumna 2: Przydatne linki (cd.) --}}
            <div>
                <h2 class="mb-4 text-xs font-extrabold uppercase tracking-widest text-white/60">
                    Przydatne linki
                </h2>
                @if ($col2->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($col2 as $item)
                            <li>
                                <a href="{{ $item->url }}" class="text-sm text-white/80 hover:text-white transition">
                                    &rsaquo; {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-white/40">—</p>
                @endif
            </div>

            {{-- Kolumna 3: Godziny pracy --}}
            <div>
                <h2 class="mb-4 text-xs font-extrabold uppercase tracking-widest text-white/60">
                    Godziny pracy urzędu
                </h2>
                @if ($siteSettings->contact_office_hours)
                    <div class="text-sm text-white/80 leading-relaxed whitespace-pre-line">{{ $siteSettings->contact_office_hours }}</div>
                @else
                    <p class="text-sm text-white/40">—</p>
                @endif
            </div>

            {{-- Kolumna 4: Adres --}}
            <div>
                <h2 class="mb-4 text-xs font-extrabold uppercase tracking-widest text-white/60">
                    Adres
                </h2>
                <address class="not-italic text-sm text-white/80 leading-relaxed space-y-1">
                    <div class="font-bold text-white">{{ $siteSettings->site_name }}</div>
                    @if ($siteSettings->contact_address)
                        <div>{{ $siteSettings->contact_address }}</div>
                    @endif
                    @if ($siteSettings->contact_city)
                        <div>{{ $siteSettings->contact_city }}</div>
                    @endif
                    @if ($siteSettings->contact_phone)
                        <div class="mt-2">
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                               class="hover:text-white transition">
                                <i class="fa-solid fa-phone mr-1" aria-hidden="true"></i>{{ $siteSettings->contact_phone }}
                            </a>
                        </div>
                    @endif
                    @if ($siteSettings->contact_email)
                        <div>
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                               class="break-all hover:text-white transition">
                                <i class="fa-solid fa-envelope mr-1" aria-hidden="true"></i>{{ $siteSettings->contact_email }}
                            </a>
                        </div>
                    @endif
                </address>
            </div>
        </div>
    </div>

    {{-- Pasek dolny --}}
    <div class="border-t border-white/20 bg-black/20">
        <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-3 px-4 py-3 text-xs text-white/50">
            <span>
                &copy; {{ now()->year }} {{ $siteSettings->site_name }}
                @if ($siteSettings->show_cms_credit ?? true)
                    &middot; <span class="font-bold">weCMS</span>
                @endif
            </span>
            <nav aria-label="Linki stopki dolne">
                <ul class="flex flex-wrap gap-x-5 gap-y-1">
                    @foreach ($footerNavItems->take(5) as $item)
                        <li><a href="{{ $item->url }}" class="hover:text-white transition">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</footer>
