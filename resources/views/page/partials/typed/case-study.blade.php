{{--
    Typ „Studium przypadku": metryka (klient, sektor, okres) → wyzwanie/rozwiązanie
    → efekty w liczbach → cytat → treść rozwijająca → CTA.
--}}
@php
    $td = $page->typeData();
    $hasCta = filled($td['cta_label'] ?? null) && filled($td['cta_url'] ?? null);
    $meta = array_filter([
        'Klient' => $td['client'] ?? null,
        'Sektor' => $td['sector'] ?? null,
        'Okres'  => $td['period'] ?? null,
    ], 'filled');
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

<section class="mx-auto max-w-5xl px-4 py-12">
    <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i> Studium przypadku
    </span>
    <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
    @if (filled($td['lead'] ?? null))
        <p class="mb-6 max-w-3xl text-lg leading-relaxed text-muted">{{ $td['lead'] }}</p>
    @endif

    @if ($meta)
        <dl class="mb-10 grid gap-4 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm sm:grid-cols-3">
            @foreach ($meta as $label => $value)
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $value }}</dd></div>
            @endforeach
        </dl>
    @endif

    @include('partials.page-content-image')

    @if (filled($td['challenge'] ?? null) || filled($td['solution'] ?? null))
        <div class="grid gap-6 md:grid-cols-2">
            @if (filled($td['challenge'] ?? null))
                <section class="rounded-2xl border border-gray-200 p-6" aria-labelledby="cs-challenge">
                    <h2 id="cs-challenge" class="mb-3 flex items-center gap-2 text-xl font-bold text-ink"><i class="fa-solid fa-triangle-exclamation text-amber-500" aria-hidden="true"></i> Wyzwanie</h2>
                    <div class="leading-relaxed text-ink">{!! nl2br(e($td['challenge'])) !!}</div>
                </section>
            @endif
            @if (filled($td['solution'] ?? null))
                <section class="rounded-2xl border border-brand/30 bg-brand-light/40 p-6" aria-labelledby="cs-solution">
                    <h2 id="cs-solution" class="mb-3 flex items-center gap-2 text-xl font-bold text-ink"><i class="fa-solid fa-lightbulb text-brand" aria-hidden="true"></i> Rozwiązanie</h2>
                    <div class="leading-relaxed text-ink">{!! nl2br(e($td['solution'])) !!}</div>
                </section>
            @endif
        </div>
    @endif

    @if ($td['results'])
        <section class="mt-10" aria-labelledby="cs-results">
            <h2 id="cs-results" class="mb-4 text-2xl font-bold text-ink">Efekty</h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ min(4, max(2, count($td['results']))) }}">
                @foreach ($td['results'] as $row)
                    <li class="rounded-2xl bg-brand p-5 text-white">
                        <p class="text-3xl font-bold leading-none">{{ $row['value'] ?? '' }}</p>
                        <p class="mt-2 text-sm text-white/90">{{ $row['label'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (filled($td['quote'] ?? null))
        <figure class="mt-10 rounded-2xl border-l-4 border-brand bg-gray-50 p-6">
            <blockquote class="text-lg font-medium leading-relaxed text-ink">„{{ $td['quote'] }}"</blockquote>
            @if (filled($td['quote_author'] ?? null))
                <figcaption class="mt-3 text-sm font-bold text-muted">— {{ $td['quote_author'] }}</figcaption>
            @endif
        </figure>
    @endif

    @if ($page->content)
        <div class="prose mt-10 max-w-none text-ink">@shortcodes($page->content)</div>
    @endif

    @include('partials.page-gallery', ['page' => $page])
    @include('partials.attachments-list', ['attachments' => $page->attachments])

    @if ($hasCta)
        <div class="mt-12 text-center">
            <a href="{{ $td['cta_url'] }}" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-brand px-8 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
            </a>
        </div>
    @endif
</section>
