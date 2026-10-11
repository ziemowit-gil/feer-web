{{--
    Blok typu wpisu (Aktualności): „Komunikat” — termin ważności, przycisk; „Test narzędzia” — karta
    z oceną dostępności i wynikiem audytu (góra), plusy i minusy oraz podsumowanie audytu (dół).
    Zmienne: $news, $part = 'top' | 'bottom'. Płasko, kolory marki, bez etykiet.
--}}
@php
    $part ??= 'top';
    $td = $news->typeData();
    $fmtDate = fn ($d) => $d?->locale('pl')->translatedFormat('j F Y, \g\o\d\z. H:i');
    $btn = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white no-underline hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
@endphp

@if ($news->isAnnouncement() && $part === 'top')
    @php
        $ctaUrl = \App\Models\FormDefinition::safeUrl($td['cta_url'] ?? null) ?? (str_starts_with((string) ($td['cta_url'] ?? ''), '#') ? $td['cta_url'] : null);
        $ctaExternal = $ctaUrl && str_starts_with($ctaUrl, 'http') && ! str_starts_with($ctaUrl, url('/'));
    @endphp
    <section class="not-prose mb-8 rounded-md p-5 {{ $news->isExpired() ? 'bg-gray-50' : 'bg-brand-light' }}" aria-labelledby="announcement-heading">
        <h2 id="announcement-heading" class="flex items-center gap-2 text-lg font-bold text-ink">
            <i class="fa-solid {{ $news->isExpired() ? 'fa-clock-rotate-left' : 'fa-bullhorn' }} text-brand-dark" aria-hidden="true"></i>
            @if ($news->isExpired())
                Komunikat nieaktualny
            @else
                {{ ! empty($td['important']) ? 'Ważny komunikat' : 'Komunikat' }}
            @endif
        </h2>
        @if ($news->expires_at)
            <p class="mt-1 text-ink">
                {{ $news->isExpired() ? 'Był ważny do' : 'Ważny do' }}
                <time datetime="{{ $news->expires_at->toIso8601String() }}" class="font-bold">{{ $fmtDate($news->expires_at) }}</time>.
            </p>
        @endif
        @if ($ctaUrl && filled($td['cta_label'] ?? null) && ! $news->isExpired())
            <a href="{{ $ctaUrl }}" class="{{ $btn }} mt-4" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                @if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
            </a>
        @endif
    </section>
@endif

@if ($news->isReview() && $part === 'top')
    @php $score = max(0, min(5, (int) ($td['score'] ?? 0))); @endphp
    <section class="not-prose mb-8 rounded-md bg-brand-light p-5" aria-labelledby="review-heading">
        <h2 id="review-heading" class="text-lg font-bold text-ink">
            Test narzędzia:
            @if (filled($td['tool_url'] ?? null))
                <a href="{{ $td['tool_url'] }}" target="_blank" rel="noopener" class="text-brand-dark underline underline-offset-4 hover:no-underline">{{ $td['tool_name'] ?? '' }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
            @else
                {{ $td['tool_name'] ?? '' }}
            @endif
            @if (filled($td['tool_version'] ?? null))<span class="font-normal text-ink"> ({{ $td['tool_version'] }})</span>@endif
        </h2>

        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            @if ($score)
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink">Ocena dostępności</dt>
                    <dd class="mt-1 flex items-center gap-3">
                        <span class="flex gap-1" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="block h-4 w-6 rounded-sm {{ $i <= $score ? 'bg-brand-dark' : 'bg-white' }}"></span>
                            @endfor
                        </span>
                        <span class="text-lg font-bold text-ink">{{ $score }} z 5</span>
                    </dd>
                </div>
            @endif
            @if (filled($td['compliance'] ?? null))
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink">Wynik audytu</dt>
                    <dd class="mt-1 flex items-center gap-2 font-bold text-ink">
                        <i class="fa-solid {{ ['full' => 'fa-circle-check', 'partial' => 'fa-circle-half-stroke', 'none' => 'fa-circle-xmark'][$td['compliance']] ?? 'fa-circle-info' }} text-brand-dark" aria-hidden="true"></i>
                        {{ \App\Models\News::REVIEW_COMPLIANCE[$td['compliance']] ?? '' }}
                    </dd>
                </div>
            @endif
            @if (filled($td['tested_with'] ?? null) || filled($td['tested_at'] ?? null))
                <div class="sm:col-span-2">
                    <dt class="text-xs font-bold uppercase tracking-wide text-ink">Jak testowaliśmy</dt>
                    <dd class="mt-1 text-ink">
                        {{ $td['tested_with'] ?? '' }}@if (filled($td['tested_with'] ?? null) && filled($td['tested_at'] ?? null)), @endif
                        @if (filled($td['tested_at'] ?? null))test z {{ \Illuminate\Support\Carbon::parse($td['tested_at'])->locale('pl')->translatedFormat('j F Y') }}@endif
                    </dd>
                </div>
            @endif
        </dl>

        @if (filled($td['verdict'] ?? null))
            <p class="mt-4 text-lg font-bold leading-relaxed text-ink">{{ $td['verdict'] }}</p>
        @endif
    </section>
@endif

@if ($news->isReview() && $part === 'bottom')
    @if ($td['pros'] || $td['cons'])
        <section class="not-prose mt-10 grid gap-6 sm:grid-cols-2" aria-label="Plusy i minusy">
            @foreach (['pros' => ['Plusy', 'fa-plus'], 'cons' => ['Minusy', 'fa-minus']] as $key => [$heading, $icon])
                @if ($td[$key])
                    <div class="rounded-md bg-gray-50 p-5">
                        <h2 class="text-lg font-bold text-ink">{{ $heading }}</h2>
                        <ul class="mt-3 space-y-2" role="list">
                            @foreach ($td[$key] as $row)
                                <li class="flex items-start gap-3 text-ink">
                                    <i class="fa-solid {{ $icon }} mt-1.5 w-4 text-center text-brand-dark" aria-hidden="true"></i>
                                    <span>{{ $row }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </section>
    @endif

    @if (filled($td['audit_summary'] ?? null))
        <section class="not-prose mt-6 rounded-md bg-gray-50 p-5" aria-labelledby="audit-heading">
            <h2 id="audit-heading" class="flex items-center gap-2 text-lg font-bold text-ink">
                <i class="fa-solid fa-universal-access text-brand-dark" aria-hidden="true"></i> Podsumowanie audytu dostępności
            </h2>
            <p class="mt-2 whitespace-pre-line text-ink">{{ $td['audit_summary'] }}</p>
        </section>
    @endif

    {{-- Dane strukturalne recenzji (schema.org Review) --}}
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $news->title,
            'itemReviewed' => array_filter(['@type' => 'SoftwareApplication', 'name' => $td['tool_name'] ?? null, 'url' => $td['tool_url'] ?? null, 'applicationCategory' => 'WebApplication']),
            'reviewRating' => ! empty($td['score']) ? ['@type' => 'Rating', 'ratingValue' => (int) $td['score'], 'bestRating' => 5, 'worstRating' => 1] : null,
            'reviewBody' => $td['verdict'] ?? null,
            'positiveNotes' => $td['pros'] ? ['@type' => 'ItemList', 'itemListElement' => collect($td['pros'])->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $p])->all()] : null,
            'negativeNotes' => $td['cons'] ? ['@type' => 'ItemList', 'itemListElement' => collect($td['cons'])->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $p])->all()] : null,
            'datePublished' => optional($news->published_at)->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $siteSettings->site_name ?? config('app.name')],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
