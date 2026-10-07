{{-- Szablon FEER — Aktualności: trzy równe, płaskie karty (zdjęcie 16:10, kategoria, tytuł, data). Bez cieni i przezroczystości. --}}
@if ($newsItems->isNotEmpty())
<section class="py-12" aria-labelledby="ngo-news-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-news-heading" class="text-2xl font-bold text-ink md:text-3xl">Aktualności</h2>
            <a href="{{ site_route('news.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
               aria-label="Zobacz wszystkie aktualności">Wszystkie aktualności →</a>
        </div>

        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($newsItems as $item)
                <li>
                    <article class="feer-card group relative flex h-full flex-col overflow-hidden rounded-lg bg-gray-50 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->image_alt ?? '' }}" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        @endif
                        <div class="flex flex-1 flex-col gap-2 p-6">
                            @if ($item->category)
                                <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $item->category->name }}</span>
                            @endif
                            <h3 class="text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">
                                <a href="{{ site_route('news.show', $item) }}" class="stretched-link focus-visible:outline-none">{{ $item->title }}</a>
                            </h3>
                            <time datetime="{{ $item->published_at->toDateString() }}" class="mt-auto pt-2 text-sm text-muted">{{ $item->published_at->translatedFormat('j F Y') }}</time>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
