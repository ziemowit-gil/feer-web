{{--
    Akordeon FAQ (pytania i odpowiedzi). Reużywany na stronie typu „FAQ"
    (page/show) oraz przy osadzeniu strony FAQ w projekcie (projects/show).

    Parametry:
      $page      — model Page z polami faq_intro / faq_items
      $faqLdJson — czy dołączyć dane strukturalne FAQPage (domyślnie false,
                   aby nie dublować ich przy osadzeniu w projekcie)
--}}
@php
    $faqItems = collect($page->faq_items ?? [])
        ->filter(fn ($i) => ! empty($i['question']) || ! empty($i['answer']))
        ->values();
    $faqLdJson = $faqLdJson ?? false;
@endphp

@if (filled($page->faq_intro))
    <p class="mb-6 text-lg leading-relaxed text-muted">{!! nl2br(e($page->faq_intro)) !!}</p>
@endif

@if ($faqItems->isNotEmpty())
    <div class="grid items-start gap-4 md:grid-cols-2">
        @foreach ($faqItems as $item)
            <details class="group overflow-hidden rounded-xl border border-gray-200 [&_summary::-webkit-details-marker]:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left font-bold text-ink hover:bg-gray-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand">
                    <span>{{ $item['question'] ?? '' }}</span>
                    <i class="fa-solid fa-chevron-down flex-none text-muted transition-transform duration-200 group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                @if (! empty($item['answer']))
                    <div class="prose max-w-none border-t border-gray-100 px-5 py-4 leading-relaxed text-muted">{!! $item['answer'] !!}</div>
                @endif
            </details>
        @endforeach
    </div>

    @if ($faqLdJson)
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems->map(fn ($i) => [
                    '@type' => 'Question',
                    'name' => (string) ($i['question'] ?? ''),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags((string) ($i['answer'] ?? '')))],
                ])->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif
@endif
