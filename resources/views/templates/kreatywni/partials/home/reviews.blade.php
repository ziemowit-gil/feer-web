{{--
    Opinie uczestników na żółtym tle jako „dymki”: ikona cudzysłowu w kółku na górnej krawędzi (nie nachodzi na tekst),
    pod dymkiem kółko z inicjałem i podpis; na końcu przycisk do opinii na Facebooku.
--}}
@php
    $kcReviews = $texts->rows('reviews_items', 2);
    $kcReviewsUrl = $texts->url('reviews_url', trim((string) $siteSettings->facebook_url) ?: null);
@endphp
@if ($kcReviews !== [])
<section id="opinie" class="kc-sec kc-sec-sun" aria-labelledby="kc-reviews-title">
    <div class="kc-wrap">
        <h2 id="kc-reviews-title" class="kc-h2 kc-head kc-center">{{ $texts->get('reviews_title') }}</h2>
        <ul class="kc-grid kc-grid-3 kc-reviews">
            @foreach ($kcReviews as [$quote, $author])
                <li>
                    <figure class="kc-review">
                        <blockquote class="kc-bubble">
                            <span class="kc-quote-icon" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></span>
                            <p>{{ $quote }}</p>
                        </blockquote>
                        @if ($author !== '')
                            <figcaption>
                                <span class="kc-review-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                                <span class="kc-review-name">{{ $author }}</span>
                            </figcaption>
                        @endif
                    </figure>
                </li>
            @endforeach
        </ul>
        @if ($kcReviewsUrl && $texts->get('reviews_button') !== '')
            <div class="kc-more">
                <a href="{{ $kcReviewsUrl }}" class="kc-btn" @if (preg_match('#^https?://#i', $kcReviewsUrl)) target="_blank" rel="noopener" @endif>
                    @if (str_contains($kcReviewsUrl, 'facebook.com'))<i class="bi bi-facebook" aria-hidden="true"></i>@endif
                    {{ $texts->get('reviews_button') }}@if (preg_match('#^https?://#i', $kcReviewsUrl))<span class="kc-sr"> (otwiera się w nowej karcie)</span>@endif
                </a>
            </div>
        @endif
    </div>
</section>
@endif
