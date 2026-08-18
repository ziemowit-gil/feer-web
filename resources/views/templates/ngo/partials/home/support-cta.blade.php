@php
    $ctaTitle = $siteSettings->support_hero_title ?? 'Twoje wsparcie tworzy zmianę';
    $ctaSubtitle = $siteSettings->support_hero_subtitle ?? 'Każda darowizna realnie napędza nasze działania.';
    $ctaLabel = $siteSettings->support_hero_cta_label ?? 'Wesprzyj nas';
    $ctaBadge = $siteSettings->support_hero_badge ?? null;

    $primaryUrl = $siteSettings->support_quick_transfer_url
        ?: $siteSettings->support_buycoffee_url
        ?: $siteSettings->support_wplacam_url
        ?: route('support.show');
    $isPrimary = str_starts_with($primaryUrl, 'http');

    $benefits = array_filter([
        ['icon' => $siteSettings->support_benefit1_icon ?: 'fa-solid fa-star', 'title' => $siteSettings->support_benefit1_title, 'text' => $siteSettings->support_benefit1_text],
        ['icon' => $siteSettings->support_benefit2_icon ?: 'fa-solid fa-star', 'title' => $siteSettings->support_benefit2_title, 'text' => $siteSettings->support_benefit2_text],
        ['icon' => $siteSettings->support_benefit3_icon ?: 'fa-solid fa-star', 'title' => $siteSettings->support_benefit3_title, 'text' => $siteSettings->support_benefit3_text],
    ], fn($b) => $b['title']);
@endphp

<section class="relative overflow-hidden bg-brand py-16 text-white" aria-labelledby="ngo-cta-heading">

    {{-- Background pattern --}}
    <div class="pointer-events-none absolute inset-0 opacity-10" aria-hidden="true">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots" x="0" y="0" width="32" height="32" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.5" fill="white"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots)"/>
        </svg>
    </div>

    <div class="relative mx-auto max-w-[1400px] px-4">
        <div class="mx-auto max-w-3xl text-center">
            @if ($ctaBadge)
                <span class="mb-4 inline-block rounded-full bg-white/20 px-4 py-1 text-xs font-bold uppercase tracking-widest">
                    {{ $ctaBadge }}
                </span>
            @endif
            <h2 id="ngo-cta-heading" class="mb-4 text-3xl font-extrabold leading-tight md:text-4xl">
                {{ $ctaTitle }}
            </h2>
            <p class="mb-8 text-base leading-relaxed text-white/80 md:text-lg">
                {{ $ctaSubtitle }}
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ $primaryUrl }}"
                    @if ($isPrimary) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center gap-2 rounded-full bg-white px-8 py-3 text-sm font-extrabold text-brand shadow transition hover:bg-white/90">
                    <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
                    {{ $ctaLabel }}
                </a>
                @if ($isPrimary)
                <a href="{{ route('support.show') }}"
                    class="inline-flex items-center gap-2 rounded-full border border-white/40 px-8 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                    Inne formy wsparcia
                </a>
                @endif
            </div>
        </div>

        {{-- Benefits --}}
        @if (count($benefits) > 0)
        <div class="mt-14 grid gap-6 sm:grid-cols-{{ count($benefits) }}">
            @foreach ($benefits as $benefit)
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/15">
                    <i class="{{ $benefit['icon'] }} text-xl" aria-hidden="true"></i>
                </div>
                <h3 class="font-bold">{{ $benefit['title'] }}</h3>
                @if (!empty($benefit['text']))
                    <p class="text-sm leading-relaxed text-white/70">{{ $benefit['text'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
