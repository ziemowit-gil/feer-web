{{--
    Szablon FEER — Aktualności na stronie głównej w układzie redakcyjnym: po lewej wyróżniony najnowszy wpis (duże zdjęcie,
    tytuł, zajawka), po prawej dwa kolejne jako zwarte pozycje (miniatura + kategoria + tytuł + data). Bez ramek i cieni;
    kontrast: ink/muted na jasnoszarym, kategorie brand-dark.
--}}
@if ($newsItems->isNotEmpty())
@php
    $lead = $newsItems->first();
    $others = $newsItems->slice(1)->take(2);
    $leadImg = $lead->image_url;
@endphp
<section class="py-12" aria-labelledby="ngo-news-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-news-heading" class="text-2xl font-bold text-ink md:text-3xl">Aktualności</h2>
            <a href="{{ site_route('news.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
               aria-label="Zobacz wszystkie aktualności">Wszystkie aktualności →</a>
        </div>

        <div class="grid gap-6 {{ $others->isNotEmpty() ? 'lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' : '' }}">
            {{-- Wyróżniony --}}
            <article class="feer-card group relative flex flex-col overflow-hidden rounded-lg bg-gray-50 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand">
                @if ($leadImg)
                    <img src="{{ $leadImg }}" alt="{{ $lead->image_alt ?? '' }}" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                @endif
                <div class="flex flex-1 flex-col gap-2 p-6 md:p-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                        {{ $lead->category?->name ?? 'Aktualności' }}
                        <span class="font-medium text-muted"> · <time datetime="{{ $lead->published_at->toDateString() }}">{{ $lead->published_at->translatedFormat('j F Y') }}</time></span>
                    </p>
                    <h3 class="text-2xl font-extrabold leading-tight text-ink group-hover:text-brand-dark md:text-3xl">
                        <a href="{{ site_route('news.show', $lead) }}" class="stretched-link focus-visible:outline-none">{{ $lead->title }}</a>
                    </h3>
                    @if ($lead->excerpt)<p class="line-clamp-3 text-base leading-relaxed text-muted">{{ $lead->excerpt }}</p>@endif
                </div>
            </article>

            {{-- Dwa kolejne --}}
            @if ($others->isNotEmpty())
                <ul class="flex flex-col gap-6" role="list">
                    @foreach ($others as $item)
                        <li class="flex-1">
                            <article class="feer-card group relative flex h-full items-stretch overflow-hidden rounded-lg bg-gray-50 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->image_alt ?? '' }}" loading="lazy" class="hidden w-36 flex-none object-cover sm:block">
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col justify-center gap-1 p-5">
                                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                                        {{ $item->category?->name ?? 'Aktualności' }}
                                        <span class="font-medium text-muted"> · <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time></span>
                                    </p>
                                    <h3 class="text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">
                                        <a href="{{ site_route('news.show', $item) }}" class="stretched-link focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
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
