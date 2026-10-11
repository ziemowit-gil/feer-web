{{--
    Typ „Oferta / usługa". Dwie kolumny: treść (treść główna → dla kogo → korzyści
    → jak działamy) | karta „Porozmawiajmy" (przycisk), kontakt i menu działu.
    Płasko: bez etykiet i obramowań, tylko kolory firmowe.
    Dane: $page->typeData() (kolumna type_data).
--}}
@php
    $td = $page->typeData();
    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    $hasCta = filled($td['cta_label'] ?? null) && $ctaUrl !== '';
    $ctaExternal = \Illuminate\Support\Str::startsWith($ctaUrl, 'http');
    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $hasCta || $hasContact || $localNav;

    $card = 'rounded-md bg-gray-50';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $btn = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $page->title,
            'description' => $td['lead'] ?? ($page->meta_description ?: null),
            'url' => $page->publicUrl(),
            'provider' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'audience' => $td['audience'] ? array_map(fn ($a) => ['@type' => 'Audience', 'audienceType' => $a['text']], $td['audience']) : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="service">
    @include('page.partials.typed._head', ['print' => false])

    {{-- Dwie kolumny: treść | karta kontaktu i menu działu (na telefonie karta pierwsza). --}}
    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Oferta — kontakt">
                <div class="lg:sticky lg:top-24">
                    @if ($hasCta)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="service-cta">
                            <h2 id="service-cta" class="text-lg font-bold text-ink">Porozmawiajmy</h2>
                            <p class="mt-2 text-ink">Napisz do nas — odpowiemy i dopasujemy ofertę do Twoich potrzeb.</p>
                            <a href="{{ $ctaUrl }}" class="{{ $btn }} mt-5" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                                {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                                @if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                            </a>
                        </section>
                    @endif
                    @if ($hasContact)
                        <section class="{{ $hasCta ? 'mt-6' : '' }} rounded-md bg-gray-50 p-6" aria-labelledby="service-contact">
                            <h2 id="service-contact" class="text-lg font-bold text-ink">Kontakt</h2>
                            <ul class="mt-3 space-y-1 text-ink">
                                @if (filled($td['contact_name'] ?? null))<li class="font-bold">{{ $td['contact_name'] }}</li>@endif
                                @if (filled($td['contact_email'] ?? null))
                                    <li><a href="mailto:{{ $td['contact_email'] }}" class="{{ $link }} break-all"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i>{{ $td['contact_email'] }}</a></li>
                                @endif
                                @if (filled($td['contact_phone'] ?? null))
                                    <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $td['contact_phone']) }}" class="{{ $link }}"><i class="fa-solid fa-phone mr-1.5" aria-hidden="true"></i>{{ $td['contact_phone'] }}</a></li>
                                @endif
                            </ul>
                        </section>
                    @endif
                    @if ($localNav)
                        <div class="{{ $hasCta || $hasContact ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['audience'])
                <section class="mt-10" aria-labelledby="service-audience">
                    <h2 id="service-audience" class="mb-4 text-2xl font-bold text-ink">Dla kogo</h2>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($td['audience'] as $row)
                            <li class="flex items-start gap-3 {{ $card }} px-4 py-3">
                                <i class="fa-solid fa-user-check mt-1 text-brand-dark" aria-hidden="true"></i>
                                <span class="text-ink">{{ $row['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['benefits'])
                <section class="mt-10" aria-labelledby="service-benefits">
                    <h2 id="service-benefits" class="mb-4 text-2xl font-bold text-ink">Co zyskujesz</h2>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($td['benefits'] as $row)
                            <li class="{{ $card }} p-5">
                                <i class="{{ filled($row['icon'] ?? null) ? $row['icon'] : 'fa-solid fa-check' }} mb-3 block text-xl text-brand-dark" aria-hidden="true"></i>
                                @if (filled($row['title'] ?? null))<h3 class="text-base font-bold text-ink">{{ $row['title'] }}</h3>@endif
                                @if (filled($row['text'] ?? null))<p class="mt-1 text-sm leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['steps'])
                <section class="mt-10" aria-labelledby="service-steps">
                    <h2 id="service-steps" class="mb-4 text-2xl font-bold text-ink">Jak działamy</h2>
                    <ol class="space-y-5">
                        @foreach ($td['steps'] as $i => $row)
                            <li>
                                <h3 class="text-base font-bold text-ink"><span class="sr-only">Etap </span>{{ $i + 1 }}. {{ $row['title'] ?? '' }}</h3>
                                @if (filled($row['text'] ?? null))<p class="mt-1 leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
