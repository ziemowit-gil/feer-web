{{-- Opinie uczestników na żółtym tle i przycisk do opinii na Facebooku. --}}
@php
    $kcReviews = $texts->rows('reviews_items', 2);
    $kcReviewsUrl = $texts->url('reviews_url', trim((string) $siteSettings->facebook_url) ?: null);
@endphp
@if ($kcReviews !== [])
<section id="opinie" class="kc-sec kc-sec-sun" aria-labelledby="kc-reviews-title">
    <div class="kc-wrap">
        <h2 id="kc-reviews-title" class="kc-h2 kc-head kc-center">{{ $texts->get('reviews_title') }}</h2>
        <ul class="kc-grid kc-grid-3">
            @foreach ($kcReviews as [$quote, $author])
                <li class="kc-card">
                    <figure class="kc-quote">
                        <blockquote><p>{{ $quote }}</p></blockquote>
                        @if ($author !== '')<figcaption>{{ $author }}</figcaption>@endif
                    </figure>
                </li>
            @endforeach
        </ul>
        @if ($kcReviewsUrl && $texts->get('reviews_button') !== '')
            <div class="kc-more">
                <a href="{{ $kcReviewsUrl }}" class="kc-btn" @if (preg_match('#^https?://#i', $kcReviewsUrl)) target="_blank" rel="noopener" @endif>
                    {{ $texts->get('reviews_button') }}@if (preg_match('#^https?://#i', $kcReviewsUrl))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                </a>
            </div>
        @endif
    </div>
</section>
@endif
