{{--
    Kafelek zbiórki na liście. Cały kafelek klikalny przez rozciągnięty link w nagłówku
    (jeden link na kafelek — czytnik ekranu słyszy tytuł, nie powtórzone „Zobacz”).
    Parametry: $campaign, $texts, $headingLevel (2|3).
--}}
@php
    $url = route('kampanie.show', $campaign->slug);
    $h = 'h' . ($headingLevel ?? 2);
    $cardVars = $texts->vars();
@endphp
<li class="relative flex flex-col overflow-hidden rounded-2xl bg-gray-50 transition hover:bg-brand-light focus-within:ring-2 focus-within:ring-brand focus-within:ring-offset-2">
    @if ($campaign->imageUrl)
        <img src="{{ $campaign->imageUrl }}" alt="{{ $campaign->image_alt ?? '' }}" class="h-48 w-full object-cover" loading="lazy">
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if ($campaign->is_featured)
            <p class="mb-2 text-sm font-bold text-brand-dark">
                <i class="fa-solid fa-star mr-1" aria-hidden="true"></i>{{ $texts->get('list_featured_label', $cardVars) }}
            </p>
        @endif

        <{{ $h }} class="vm-display mb-2 text-lg text-ink">
            <a href="{{ $url }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $campaign->title }}</a>
        </{{ $h }}>

        @if ($campaign->excerpt)
            <p class="mb-4 flex-1 text-sm leading-relaxed text-ink">{{ $campaign->excerpt }}</p>
        @endif

        @include('campaigns.partials.progress', ['campaign' => $campaign, 'texts' => $texts, 'compact' => true])

        <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand-dark" aria-hidden="true">
            {{ $texts->get('list_cta', $cardVars) }} <i class="fa-solid fa-arrow-right"></i>
        </span>
    </div>
</li>
