{{--
    Panel zapisu na newsletter (jak na „Stronie działu"). Dane z
    PageController::sectionNewsletterDefaults(); null = nic nie renderujemy.
    Zmienne: $newsletter, opcjonalnie $headingId.
--}}
@if (! empty($newsletter))
    @php $headingId = $headingId ?? 'blog-newsletter-heading'; @endphp
    <section class="rounded-md bg-brand-light p-5" aria-labelledby="{{ $headingId }}">
        <h2 id="{{ $headingId }}" class="flex items-center gap-2 text-lg font-bold text-ink">
            <i class="fa-solid fa-envelope-open-text text-brand-dark" aria-hidden="true"></i> {{ $newsletter['title'] }}
        </h2>
        @if (filled($newsletter['text'] ?? null))
            <p class="mt-2 text-sm text-ink">{{ $newsletter['text'] }}</p>
        @endif
        <a href="{{ $newsletter['url'] }}"
            class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-4 py-2.5 text-center font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            {{ $newsletter['button'] }}
        </a>
    </section>
@endif
