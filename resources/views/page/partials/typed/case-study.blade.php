{{--
    Typ „Studium przypadku". Dwie kolumny: treść (wyzwanie/rozwiązanie → efekty
    w liczbach → cytat → treść rozwijająca) | karta projektu (klient, sektor,
    okres, przycisk) i menu działu. Płasko: bez etykiet i obramowań, tylko kolory firmowe.
--}}
@php
    $td = $page->typeData();
    $ctaUrl = trim((string) ($td['cta_url'] ?? ''));
    $ctaUrl = preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $ctaUrl) ? $ctaUrl : '';
    $hasCta = filled($td['cta_label'] ?? null) && $ctaUrl !== '';
    $ctaExternal = \Illuminate\Support\Str::startsWith($ctaUrl, 'http');
    $meta = array_filter([
        'Klient' => $td['client'] ?? null,
        'Sektor' => $td['sector'] ?? null,
        'Okres'  => $td['period'] ?? null,
    ], 'filled');
    $metaIcons = ['Klient' => 'fa-building', 'Sektor' => 'fa-layer-group', 'Okres' => 'fa-calendar-days'];
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $meta || $hasCta || $localNav;

    $btn = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $td['lead'] ?? ($page->meta_description ?: null),
            'url' => $page->publicUrl(),
            'datePublished' => optional($page->created_at)->toIso8601String(),
            'dateModified' => optional($page->updated_at)->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'publisher' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'about' => filled($td['client'] ?? null) ? ['@type' => 'Organization', 'name' => $td['client']] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="case_study">
    @include('page.partials.typed._head', ['print' => false])

    {{-- Dwie kolumny: treść | karta projektu i menu działu (na telefonie karta pierwsza). --}}
    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Studium przypadku — najważniejsze informacje">
                <div class="lg:sticky lg:top-24">
                    @if ($meta || $hasCta)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="cs-summary">
                            <h2 id="cs-summary" class="sr-only">Najważniejsze informacje o projekcie</h2>
                            @if ($meta)
                                <dl class="grid gap-y-4">
                                    @foreach ($meta as $label => $value)
                                        <div class="flex gap-3">
                                            <i class="fa-solid {{ $metaIcons[$label] }} mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                            <div>
                                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                                                <dd class="font-semibold text-ink">{{ $value }}</dd>
                                            </div>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                            @if ($hasCta)
                                <a href="{{ $ctaUrl }}" class="{{ $btn }} {{ $meta ? 'mt-6' : '' }}" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                                    {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                                    @if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                                </a>
                            @endif
                        </section>
                    @endif
                    @if ($localNav)
                        <div class="{{ $meta || $hasCta ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @include('partials.page-content-image')

            @if (filled($td['challenge'] ?? null) || filled($td['solution'] ?? null))
                <div class="grid gap-6 md:grid-cols-2">
                    @if (filled($td['challenge'] ?? null))
                        <section class="rounded-md bg-gray-50 p-6" aria-labelledby="cs-challenge">
                            <h2 id="cs-challenge" class="mb-3 flex items-center gap-2 text-xl font-bold text-ink"><i class="fa-solid fa-triangle-exclamation text-brand-dark" aria-hidden="true"></i> Wyzwanie</h2>
                            <div class="leading-relaxed text-ink">{!! nl2br(e($td['challenge'])) !!}</div>
                        </section>
                    @endif
                    @if (filled($td['solution'] ?? null))
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="cs-solution">
                            <h2 id="cs-solution" class="mb-3 flex items-center gap-2 text-xl font-bold text-ink"><i class="fa-solid fa-lightbulb text-brand-dark" aria-hidden="true"></i> Rozwiązanie</h2>
                            <div class="leading-relaxed text-ink">{!! nl2br(e($td['solution'])) !!}</div>
                        </section>
                    @endif
                </div>
            @endif

            @if ($td['results'])
                <section class="mt-10" aria-labelledby="cs-results">
                    <h2 id="cs-results" class="mb-4 text-2xl font-bold text-ink">Efekty</h2>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($td['results'] as $row)
                            <li class="rounded-md bg-gray-50 p-5">
                                <p class="text-3xl font-bold leading-none text-brand-dark">{{ $row['value'] ?? '' }}</p>
                                <p class="mt-2 text-ink">{{ $row['label'] ?? '' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($td['quote'] ?? null))
                <figure class="mt-10 rounded-md bg-gray-50 p-6">
                    <i class="fa-solid fa-quote-left mb-3 block text-2xl text-brand-dark" aria-hidden="true"></i>
                    <blockquote class="text-lg font-medium leading-relaxed text-ink">„{{ $td['quote'] }}"</blockquote>
                    @if (filled($td['quote_author'] ?? null))
                        <figcaption class="mt-3 font-bold text-ink">— {{ $td['quote_author'] }}</figcaption>
                    @endif
                </figure>
            @endif

            @if ($page->content)
                <div class="prose mt-10 max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
