@if ($newsItems->isNotEmpty())
<section class="py-14" aria-labelledby="ngo-news-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        {{-- Section header --}}
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-news-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                Aktualności
            </h2>
            <a href="{{ route('news.index') }}"
                class="shrink-0 text-sm font-semibold text-brand hover:underline"
                aria-label="Zobacz wszystkie aktualności">
                Wszystkie aktualności &rarr;
            </a>
        </div>

        {{-- Grid: 1st item large, rest small --}}
        @php $first = $newsItems->first(); $rest = $newsItems->skip(1); @endphp

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">

            {{-- First (featured) card --}}
            @if ($first)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md md:col-span-2 lg:col-span-1">
                @if ($first->image_url)
                    <a href="{{ route('news.show', $first) }}" tabindex="-1" aria-hidden="true">
                        <div class="aspect-[16/9] overflow-hidden">
                            <img src="{{ $first->image_url }}" alt="{{ $first->image_alt ?? $first->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    </a>
                @endif
                <div class="flex flex-1 flex-col gap-2 p-5">
                    @if ($first->category)
                        <span class="text-xs font-semibold uppercase tracking-wide text-brand">
                            {{ $first->category->name }}
                        </span>
                    @endif
                    <h3 class="text-base font-extrabold leading-snug text-gray-900 group-hover:text-brand">
                        <a href="{{ route('news.show', $first) }}" class="stretched-link">{{ $first->title }}</a>
                    </h3>
                    <time datetime="{{ $first->published_at->toDateString() }}"
                        class="mt-auto text-xs text-gray-400">
                        {{ $first->published_at->translatedFormat('j F Y') }}
                    </time>
                </div>
            </article>
            @endif

            {{-- Rest --}}
            @foreach ($rest as $item)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                @if ($item->image_url)
                    <a href="{{ route('news.show', $item) }}" tabindex="-1" aria-hidden="true">
                        <div class="aspect-[16/9] overflow-hidden">
                            <img src="{{ $item->image_url }}" alt="{{ $item->image_alt ?? $item->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    </a>
                @endif
                <div class="flex flex-1 flex-col gap-2 p-5">
                    @if ($item->category)
                        <span class="text-xs font-semibold uppercase tracking-wide text-brand">
                            {{ $item->category->name }}
                        </span>
                    @endif
                    <h3 class="text-sm font-bold leading-snug text-gray-900 group-hover:text-brand">
                        <a href="{{ route('news.show', $item) }}" class="stretched-link">{{ $item->title }}</a>
                    </h3>
                    <time datetime="{{ $item->published_at->toDateString() }}"
                        class="mt-auto text-xs text-gray-400">
                        {{ $item->published_at->translatedFormat('j F Y') }}
                    </time>
                </div>
            </article>
            @endforeach

        </div>
    </div>
</section>
@endif
