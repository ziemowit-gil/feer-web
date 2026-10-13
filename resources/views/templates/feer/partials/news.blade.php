{{-- Szablon FEER — Aktualności: jedna duża wyróżniona (najnowsza) i do trzech mniejszych obok. Płasko, bez cieni i przezroczystości. --}}
@if ($newsItems->isNotEmpty())
<section class="py-12" aria-labelledby="ngo-news-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-news-heading" class="text-2xl font-bold text-ink md:text-3xl">Aktualności</h2>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-x-5 gap-y-1">
                @include('partials.admin-manage-link', ['route' => 'admin.newsy.index', 'label' => 'Zarządzaj aktualnościami'])
                <a href="{{ site_route('news.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                   aria-label="Zobacz wszystkie aktualności">Wszystkie aktualności →</a>
            </div>
        </div>

        @php $lead = $newsItems->first(); $rest = $newsItems->slice(1)->take(3); @endphp
        <div class="grid gap-6 {{ $rest->isNotEmpty() ? 'lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' : '' }}">
            {{-- Wyróżniona --}}
            <article class="group relative flex flex-col overflow-hidden rounded-md bg-gray-50 transition-colors hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                @if ($lead->image_url)
                    <img src="{{ $lead->image_url }}" alt="{{ $lead->image_alt ?? '' }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
                @endif
                <div class="flex flex-1 flex-col gap-3 p-6 md:p-8">
                    @if ($lead->category)
                        <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $lead->category->name }}</span>
                    @endif
                    <h3 class="text-2xl font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4 md:text-3xl">
                        <a href="{{ site_route('news.show', $lead) }}" class="stretched-link focus-visible:outline-none">{{ $lead->title }}</a>
                    </h3>
                    @if (filled($lead->excerpt ?? null))
                        <p class="line-clamp-3 text-base leading-relaxed text-ink">{{ $lead->excerpt }}</p>
                    @endif
                    <time datetime="{{ $lead->published_at->toDateString() }}" class="mt-auto pt-2 text-sm text-muted">{{ $lead->published_at->translatedFormat('j F Y') }}</time>
                </div>
            </article>

            {{-- Pozostałe: lista z miniaturą --}}
            @if ($rest->isNotEmpty())
                <ul class="flex flex-col gap-4" role="list">
                    @foreach ($rest as $item)
                        <li class="flex-1">
                            <article class="group relative flex h-full gap-4 overflow-hidden rounded-md bg-gray-50 p-4 transition-colors hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" loading="lazy" class="aspect-[4/3] w-28 flex-none rounded object-cover sm:w-36">
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    @if ($item->category)
                                        <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $item->category->name }}</span>
                                    @endif
                                    <h3 class="text-base font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">
                                        <a href="{{ site_route('news.show', $item) }}" class="stretched-link focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
                                    <time datetime="{{ $item->published_at->toDateString() }}" class="mt-auto text-sm text-muted">{{ $item->published_at->translatedFormat('j F Y') }}</time>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
@endif
