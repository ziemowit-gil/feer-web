{{--
    Szablon FEER — blok wsparcia: płaski, jednolity pas (bez wzoru i przezroczystości).
    Kontrast: biały tekst na bg-brand-dark (#1456CC) 6,5:1; przycisk główny ink na bieli 16,9:1; drugi — biała ramka i tekst.
--}}
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
    ], fn ($b) => $b['title']);
@endphp

<section class="bg-brand-dark py-14 text-white" aria-labelledby="ngo-cta-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div class="min-w-0">
                @if ($ctaBadge)
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest">{{ $ctaBadge }}</p>
                @endif
                <h2 id="ngo-cta-heading" class="mb-3 text-3xl font-extrabold leading-tight md:text-4xl">{{ $ctaTitle }}</h2>
                <p class="max-w-2xl text-base leading-relaxed md:text-lg">{{ $ctaSubtitle }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $primaryUrl }}" @if ($isPrimary) target="_blank" rel="noopener" @endif
                   class="inline-flex min-h-11 items-center gap-2 rounded-md bg-white px-6 text-sm font-bold text-ink transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">
                    <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> {{ $ctaLabel }}
                    @if ($isPrimary)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                </a>
                @if ($isPrimary)
                    <a href="{{ route('support.show') }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-md border-2 border-white px-6 text-sm font-bold text-white transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">
                        Inne formy wsparcia
                    </a>
                @endif
            </div>
        </div>

        @if (count($benefits) > 0)
            <ul class="mt-10 grid gap-6 border-t border-white pt-8 sm:grid-cols-{{ count($benefits) }}" role="list">
                @foreach ($benefits as $benefit)
                    <li class="flex items-start gap-3">
                        <i class="{{ $benefit['icon'] }} mt-1 text-xl" aria-hidden="true"></i>
                        <div>
                            <h3 class="font-bold">{{ $benefit['title'] }}</h3>
                            @if (! empty($benefit['text']))<p class="mt-1 text-sm leading-relaxed">{{ $benefit['text'] }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
