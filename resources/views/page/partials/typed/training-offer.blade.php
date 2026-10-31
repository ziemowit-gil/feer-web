{{--
    Typ „Szkolenie”. Dwie kolumny: treść (lead, dla kogo, efekty, program, terminy, dodatkowe informacje, trenerzy)
    | karta szkolenia (czas, forma, miejsce, grupa, cena, certyfikat, zapisy, kontakt) i menu działu.
    Płasko, bez etykiet i obramowań; kontrast tekstu ≥ 4,5:1.
--}}
@php
    $td = $page->typeData();
    $safeUrl = fn ($u) => preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', trim((string) $u)) ? site_href(trim((string) $u)) : '';
    $ctaUrl = $safeUrl($td['cta_url'] ?? '');
    $hasCta = filled($td['cta_label'] ?? null) && $ctaUrl !== '';
    $modeLabels = ['stationary' => 'Stacjonarnie', 'online' => 'Online', 'hybrid' => 'Hybrydowo'];
    $statusLabels = ['open' => 'Zapisy otwarte', 'few' => 'Ostatnie miejsca', 'full' => 'Brak miejsc', 'planned' => 'Planowany'];
    $facts = array_filter([
        'Czas trwania' => $td['duration'] ?? null,
        'Forma' => $modeLabels[$td['mode'] ?? ''] ?? null,
        'Miejsce' => $td['place'] ?? null,
        'Wielkość grupy' => $td['group_size'] ?? null,
        'Cena' => $td['price'] ?? null,
        'Poziom' => $td['level'] ?? null,
        'Język' => $td['language'] ?? null,
        'Cena' => $td['price'] ?? null,
        'Dofinansowanie' => $td['funding'] ?? null,
        'Certyfikat' => $td['certificate'] ?? null,
    ], 'filled');
    $factIcons = ['Czas trwania' => 'fa-clock', 'Forma' => 'fa-chalkboard', 'Miejsce' => 'fa-location-dot', 'Wielkość grupy' => 'fa-users', 'Poziom' => 'fa-signal', 'Język' => 'fa-language', 'Cena' => 'fa-coins', 'Dofinansowanie' => 'fa-hand-holding-dollar', 'Certyfikat' => 'fa-award'];
    $closedUrl = $safeUrl($td['closed_url'] ?? '') ?: '/kontakt';
    $hasClosed = ($td['closed_available'] ?? '0') == '1';
    $closedLabel = filled($td['closed_label'] ?? null) ? $td['closed_label'] : 'Zapytaj o szkolenie dla Twojej instytucji';
    $faq = collect($td['faq'] ?? [])->filter(fn ($r) => filled($r['question'] ?? null) && filled($r['answer'] ?? null))->values();
    $testimonials = collect($td['testimonials'] ?? [])->filter(fn ($r) => filled($r['text'] ?? null))->values();
    $related = \App\Models\Page::forCurrentSite()->where('type', 'training_offer')->where('is_published', true)->where('id', '!=', $page->id)->orderBy('title')->get()
        ->filter(fn ($o) => filled($td['category'] ?? null) && ($o->typeData()['category'] ?? null) === $td['category'])->take(3)->values();
    $editions = collect($td['editions'] ?? [])->filter(fn ($e) => filled($e['date'] ?? null))->sortBy('date')->values();
    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $hasAside = $facts || $hasCta || $hasClosed || $hasContact || $localNav;
    $btn = 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
@endphp

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $page->title,
            'description' => $td['lead'] ?? ($page->meta_description ?: null),
            'url' => $page->publicUrl(),
            'provider' => ['@type' => 'Organization', 'name' => $siteSettings->site_name],
            'hasCourseInstance' => $editions->map(fn ($e) => array_filter([
                '@type' => 'CourseInstance',
                'courseMode' => $modeLabels[$td['mode'] ?? ''] ?? null,
                'startDate' => $e['date'],
                'location' => filled($e['place'] ?? null) ? ['@type' => 'Place', 'name' => $e['place']] : null,
            ]))->all() ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@if ($faq->isNotEmpty())
    @push('structured_data')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faq->map(fn ($r) => ['@type' => 'Question', 'name' => $r['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $r['answer']]])->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush
@endif

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="training_offer">
    @include('page.partials.typed._head', ['print' => false])

    <div class="grid gap-10 {{ $hasAside ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($hasAside)
            <aside class="lg:order-last" aria-label="Szkolenie: najważniejsze informacje">
                <div class="lg:sticky lg:top-24">
                    @if ($facts || $hasCta || $hasClosed || $hasContact)
                        <section class="rounded-md bg-brand-light p-6" aria-labelledby="to-summary">
                            <h2 id="to-summary" class="sr-only">Najważniejsze informacje o szkoleniu</h2>
                            @if ($facts)
                                <dl class="grid gap-y-4">
                                    @foreach ($facts as $label => $value)
                                        <div class="flex gap-3">
                                            <i class="fa-solid {{ $factIcons[$label] }} mt-1 w-5 text-center text-brand-dark" aria-hidden="true"></i>
                                            <div>
                                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                                                <dd class="font-semibold text-ink">{{ $value }}@if ($label === 'Cena' && filled($td['price_note'] ?? null))<span class="block text-sm font-normal">{{ $td['price_note'] }}</span>@endif</dd>
                                            </div>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                            @if ($hasCta)
                                <a href="{{ $ctaUrl }}" class="{{ $btn }} {{ $facts ? 'mt-6' : '' }}">{{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i></a>
                            @endif
                            @if ($hasClosed)
                                <a href="{{ $closedUrl }}" class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md border-2 border-brand-dark px-6 text-center text-base font-bold text-brand-dark transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 {{ $facts || $hasCta ? '' : 'mt-0' }}">{{ $closedLabel }}</a>
                            @endif
                            @if ($hasContact)
                                <p class="{{ $facts || $hasCta || $hasClosed ? 'mt-5' : '' }} text-sm text-ink">
                                    <span class="font-bold">Pytania?</span>
                                    {{ $td['contact_name'] ?? '' }}
                                    @if (filled($td['contact_email'] ?? null))<a href="mailto:{{ $td['contact_email'] }}" class="font-bold text-brand-dark underline underline-offset-4">{{ $td['contact_email'] }}</a>@endif
                                    @if (filled($td['contact_phone'] ?? null))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $td['contact_phone']) }}" class="font-bold text-brand-dark underline underline-offset-4">{{ $td['contact_phone'] }}</a>@endif
                                </p>
                            @endif
                        </section>
                    @endif
                    @if ($localNav)
                        <div class="{{ $facts || $hasCta || $hasClosed || $hasContact ? 'mt-6' : '' }}">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
                    @endif
                </div>
            </aside>
        @endif

        <div class="min-w-0">
            @include('partials.page-content-image')

            @if ($td['audience'])
                <section aria-labelledby="to-audience">
                    <h2 id="to-audience" class="mb-3 text-2xl font-bold text-ink">Dla kogo</h2>
                    <ul class="list-disc space-y-1 pl-5 text-ink">@foreach ($td['audience'] as $row)<li>{{ $row['text'] ?? '' }}</li>@endforeach</ul>
                </section>
            @endif

            @if ($td['outcomes'])
                <section class="mt-10" aria-labelledby="to-outcomes">
                    <h2 id="to-outcomes" class="mb-3 text-2xl font-bold text-ink">Czego się nauczysz</h2>
                    <ul class="space-y-2">
                        @foreach ($td['outcomes'] as $row)
                            <li class="flex gap-3 text-ink"><i class="fa-solid fa-check mt-1 text-brand-dark" aria-hidden="true"></i><span>{{ $row['text'] ?? '' }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['program'])
                <section class="mt-10" aria-labelledby="to-program">
                    <h2 id="to-program" class="mb-4 text-2xl font-bold text-ink">Program</h2>
                    <ol class="space-y-3">
                        @foreach ($td['program'] as $i => $row)
                            <li class="rounded-md bg-gray-50 p-5">
                                <h3 class="flex flex-wrap items-baseline justify-between gap-2 text-lg font-bold text-ink">
                                    <span>{{ $i + 1 }}. {{ $row['title'] ?? '' }}</span>
                                    @if (filled($row['hours'] ?? null))<span class="text-sm font-semibold text-ink">{{ $row['hours'] }} godz.</span>@endif
                                </h3>
                                @if (filled($row['text'] ?? null))<p class="mt-2 leading-relaxed text-ink">{{ $row['text'] }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($editions->isNotEmpty())
                <section class="mt-10" aria-labelledby="to-editions">
                    <h2 id="to-editions" class="mb-4 text-2xl font-bold text-ink">Najbliższe terminy</h2>
                    <ul class="divide-y divide-gray-200 rounded-md bg-gray-50" role="list">
                        @foreach ($editions as $e)
                            @php $eUrl = $safeUrl($e['url'] ?? '') ?: $ctaUrl; @endphp
                            <li class="flex flex-wrap items-center gap-x-6 gap-y-1 px-5 py-4">
                                <time datetime="{{ $e['date'] }}" class="w-32 shrink-0 font-bold text-ink">{{ \Illuminate\Support\Carbon::parse($e['date'])->translatedFormat('j F Y') }}</time>
                                <span class="min-w-0 flex-1 text-ink">{{ collect([$e['place'] ?? null, $statusLabels[$e['status'] ?? 'open'] ?? null])->filter()->implode(' · ') }}</span>
                                @if ($eUrl !== '' && ($e['status'] ?? 'open') !== 'full')
                                    <a href="{{ $eUrl }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Zapisz się</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($td['prerequisites'] ?? null))
                <section class="mt-10" aria-labelledby="to-prereq">
                    <h2 id="to-prereq" class="mb-3 text-2xl font-bold text-ink">Wymagania wstępne</h2>
                    <div class="leading-relaxed text-ink">{!! nl2br(e($td['prerequisites'])) !!}</div>
                </section>
            @endif

            @if ($page->content)
                <div class="prose mt-10 max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['trainers'])
                <section class="mt-10" aria-labelledby="to-trainers">
                    <h2 id="to-trainers" class="mb-4 text-2xl font-bold text-ink">{{ count($td['trainers']) > 1 ? 'Trenerzy' : 'Trener' }}</h2>
                    <ul class="grid gap-4 sm:grid-cols-2" role="list">
                        @foreach ($td['trainers'] as $t)
                            @php $tUrl = $safeUrl($t['url'] ?? ''); @endphp
                            <li class="rounded-md bg-gray-50 p-5">
                                <p class="font-bold text-ink">@if ($tUrl !== '')<a href="{{ $tUrl }}" class="text-brand-dark underline underline-offset-4 hover:no-underline">{{ $t['name'] ?? '' }}</a>@else{{ $t['name'] ?? '' }}@endif</p>
                                @if (filled($t['role'] ?? null))<p class="text-sm text-ink">{{ $t['role'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($testimonials->isNotEmpty())
                <section class="mt-10" aria-labelledby="to-testimonials">
                    <h2 id="to-testimonials" class="mb-4 text-2xl font-bold text-ink">Opinie uczestników</h2>
                    <ul class="grid gap-4 sm:grid-cols-2" role="list">
                        @foreach ($testimonials as $row)
                            <li>
                                <figure class="h-full rounded-md bg-gray-50 p-5">
                                    <blockquote class="leading-relaxed text-ink">„{{ $row['text'] }}”</blockquote>
                                    @if (filled($row['author'] ?? null))<figcaption class="mt-3 text-sm font-bold text-ink">{{ $row['author'] }}</figcaption>@endif
                                </figure>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($faq->isNotEmpty())
                <section class="mt-10" aria-labelledby="to-faq">
                    <h2 id="to-faq" class="mb-4 text-2xl font-bold text-ink">Najczęstsze pytania</h2>
                    <div class="space-y-2">
                        @foreach ($faq as $row)
                            <details class="group rounded-md bg-gray-50">
                                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark [&::-webkit-details-marker]:hidden">
                                    <span>{{ $row['question'] }}</span>
                                    <i class="fa-solid fa-chevron-down text-sm text-brand-dark transition-transform group-open:rotate-180" aria-hidden="true"></i>
                                </summary>
                                <div class="px-5 pb-4 leading-relaxed text-ink">{!! nl2br(e($row['answer'])) !!}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($related->isNotEmpty())
                <section class="mt-10" aria-labelledby="to-related">
                    <h2 id="to-related" class="mb-4 text-2xl font-bold text-ink">Zobacz także</h2>
                    <ul class="grid gap-4 sm:grid-cols-3" role="list">
                        @foreach ($related as $o)
                            <li class="group relative rounded-md bg-gray-50 p-4 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                                <a href="{{ $o->publicUrl() }}" class="stretched-link font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">{{ $o->title }}</a>
                                @if (filled($o->typeData()['duration'] ?? null))<p class="mt-1 text-sm text-ink">{{ $o->typeData()['duration'] }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
            @include('partials.page-section-nav', ['page' => $page])
        </div>
    </div>
</section>
