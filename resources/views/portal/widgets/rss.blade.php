{{-- Widżet portalu: najnowsze wpisy z zewnętrznego kanału RSS (adres w ustawieniach kategorii). --}}
@if ($rssItems->isNotEmpty())
    @php $rssHost = preg_replace('/^www\./', '', (string) parse_url((string) $category->portal_rss_url, PHP_URL_HOST)); @endphp
    <section aria-labelledby="portal-w-rss">
        <h2 id="portal-w-rss" class="text-xl font-bold leading-tight text-ink md:text-2xl">{{ $category->portalWidgetTitle('rss') }}</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
            @foreach ($rssItems as $item)
                <li>
                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="group block rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <span class="block text-sm text-ink">{{ $rssHost }}@if ($item->date) · <time datetime="{{ $item->date->toDateString() }}">{{ $item->date->translatedFormat('j F Y') }}</time>@endif</span>
                        <span class="mt-0.5 block font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $item->title }}<span class="sr-only"> (otwiera się w nowej karcie)</span></span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
