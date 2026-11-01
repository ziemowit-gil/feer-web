@if ($siteSettings->isModuleEnabled('news') && $newsItems->isNotEmpty())
<style>
    .z2-news { max-width: 1160px; margin: 3rem auto 0; padding: 0 1rem; }
    .z2-news h2 { font-size: 1rem; font-weight: 800; margin: 0 0 1rem; color: #1a1a1a; }
    .z2-news h2::before { content: "— "; }
    .z2-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
    .z2-card { display: block; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.1); text-decoration: none; color: #1a1a1a; }
    .z2-card:hover h3, .z2-card:focus-visible h3 { color: #0b4f9e; text-decoration: underline; }
    .z2-card img { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
    .z2-card div { padding: 1rem; }
    .z2-card time { font-size: .8rem; color: #4a5361; }
    .z2-card h3 { margin: .35rem 0 0; font-size: 1rem; font-weight: 800; line-height: 1.3; }
    .z2-more { text-align: center; margin: 2rem 0 0; }
    .z2-more a { display: inline-flex; gap: 1.5rem; align-items: center; background: #0b4f9e; color: #fff; font-weight: 700; padding: .8rem 1.5rem; text-decoration: none; }
    .z2-more a:hover { background: #083a75; }
    @media (max-width: 800px) { .z2-cards { grid-template-columns: 1fr; } }
</style>
<section class="z2-news" aria-labelledby="z2-news-h">
    <h2 id="z2-news-h">Aktualności</h2>
    <div class="z2-cards">
        @foreach ($newsItems->take(3) as $item)
            <a class="z2-card" href="{{ site_route('news.show', $item) }}">
                @if ($img = $item->imageUrlOrDefault())<img src="{{ $img }}" alt="" loading="lazy">@endif
                <div>
                    <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                    <h3>{{ $item->title }}</h3>
                </div>
            </a>
        @endforeach
    </div>
    <p class="z2-more"><a href="{{ site_route('news.index') }}">Wszystkie aktualności <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></p>
</section>
@endif
