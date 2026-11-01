{{--
    Aktualności: trzy najnowsze wpisy w jednym rzędzie (obraz opcjonalny, data, tytuł jako link na całą kartę) i link do
    wszystkich. Blok pokazuje się, gdy sekcja „Aktualności” jest włączona w Ustawieniach → Strona główna.
--}}
@php $newsItems ??= collect(); @endphp
@if ($newsItems->isNotEmpty() && $siteSettings->isHomepageSectionEnabled('news'))
    <section class="fis-sec fis-sec--tint" aria-labelledby="feer-is-news-heading">
        <div class="fis-wrap">
            <div class="fis-head">
                <h2 id="feer-is-news-heading">Aktualności</h2>
                <a class="fis-link" href="{{ site_route('news.index') }}">Wszystkie aktualności<span aria-hidden="true"> →</span></a>
            </div>
            <ul class="fis-news" role="list">
                @foreach ($newsItems as $item)
                    <li>
                        @if ($item->image_url)<img src="{{ $item->image_url }}" alt="{{ $item->image_alt ?? '' }}" loading="lazy">@endif
                        <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}@if ($item->category) · {{ $item->category->name }}@endif</time>
                        <h3><a href="{{ site_route('news.show', $item) }}">{{ $item->title }}</a></h3>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
