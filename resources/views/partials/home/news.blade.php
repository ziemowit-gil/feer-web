@if ($siteSettings->isModuleEnabled('news'))
@php $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark'; @endphp
<section id="aktualnosci" class="mx-auto max-w-6xl px-4 py-12" aria-labelledby="home-news-heading">

    @if ($siteSettings->homepageBannerIsVisible())
        <div role="region" aria-label="Ważna informacja"
            class="mb-8 flex items-start gap-3 rounded-md bg-brand-light px-5 py-4">
            <i class="fa-solid fa-circle-exclamation mt-0.5 flex-none text-xl text-brand-dark" aria-hidden="true"></i>
            <div class="flex-1">
                <p class="font-medium text-ink">{{ $siteSettings->homepage_banner_text }}</p>
                @if (filled($siteSettings->homepage_banner_link_label) && filled($siteSettings->homepage_banner_link_url))
                    <a href="{{ $siteSettings->homepage_banner_link_url }}" class="mt-2 inline-flex items-center gap-1.5 {{ $link }}">
                        {{ $siteSettings->homepage_banner_link_label }}
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 id="home-news-heading" class="text-2xl font-bold text-ink">Aktualności</h2>
        <a href="{{ site_route('news.index') }}" class="inline-flex min-h-11 items-center rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zobacz wszystkie<span class="sr-only"> aktualności</span></a>
    </div>

    {{-- Płaskie kafle: zdjęcie, kategoria zwykłym tekstem (bez kolorowej plakietki na zdjęciu), data, tytuł i zajawka. --}}
    <div class="grid gap-8 md:grid-cols-3">
        @forelse ($news as $item)
            <a href="{{ site_route('news.show', $item) }}"
                class="group block rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-4">
                @php $img = $item->imageUrlOrDefault(); @endphp
                @if ($img)
                    <img src="{{ $img }}" alt="" class="mb-3 h-32 w-full rounded-md bg-gray-100 object-cover">
                @endif
                <p class="text-xs font-bold uppercase tracking-widest text-muted">
                    @if ($item->category)<span class="text-brand-dark">{{ $item->category->name }}</span><span aria-hidden="true"> · </span>@endif
                    <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                </p>
                <h3 class="mt-1 font-bold text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $item->title }}</h3>
                @if ($item->excerpt)
                    <p class="mt-1 text-sm text-muted">{{ $item->excerpt }}</p>
                @endif
            </a>
        @empty
            <p class="text-muted">Brak opublikowanych newsów.</p>
        @endforelse
    </div>
</section>
@endif
