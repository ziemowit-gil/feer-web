{{--
    Aktualności: tytuł wyśrodkowany, pigułki kategorii (filtr na stronie: pokazuje trzy pierwsze wpisy z wybranej kategorii
    spośród dziewięciu najnowszych; bez JS są to zwykłe linki do listy aktualności z filtrem), trzy karty i przycisk
    „Wszystkie aktualności”. Dane: $newsItems (do 9 wpisów z kategorią). Sekcja wg przełącznika „Aktualności” w Ustawieniach.
--}}
@php
    $items = ($newsItems ?? collect())->values();
    $cats = $items->pluck('category')->filter()->unique('id')->values();
    $filterData = $items->map(fn ($n) => ['id' => $n->id, 'cat' => $n->category?->slug ?? ''])->all();
@endphp
@include('templates.test3.partials.home.css')
@if ($siteSettings->isModuleEnabled('news') && $siteSettings->isHomepageSectionEnabled('news') && $items->isNotEmpty())
<section class="z3 z3-sec z3-news" aria-labelledby="z3-news-h"
         x-data="{ items: @js($filterData), cat: '',
                   get shown() { return this.items.filter(i => this.cat === '' || i.cat === this.cat).slice(0, 3).map(i => i.id); } }">
    <div class="z3-wrap">
        <h2 id="z3-news-h">Aktualności</h2>

        @if ($cats->count() > 1)
            <ul class="z3-pills" role="list" aria-label="Kategorie aktualności">
                <li><a href="{{ site_route('news.index') }}" :class="cat === '' ? 'is-on' : ''" :aria-current="(cat === '').toString()" @click.prevent="cat = ''">Wszystkie</a></li>
                @foreach ($cats as $category)
                    <li><a href="{{ site_route('news.index', ['kategoria' => $category->slug]) }}" :class="cat === @js($category->slug) ? 'is-on' : ''" :aria-current="(cat === @js($category->slug)).toString()" @click.prevent="cat = @js($category->slug)">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        @endif
        <p class="sr-only" role="status" x-text="'Liczba pokazanych aktualności: ' + shown.length"></p>

        <ul class="z3-cards" role="list">
            @foreach ($items as $item)
                @php $image = $item->imageUrlOrDefault(); @endphp
                <li class="z3-card" x-cloak x-show="shown.includes({{ $item->id }})">
                    @if ($image)<img src="{{ $image }}" alt="{{ $item->image_alt ?? '' }}" loading="lazy">@endif
                    <div class="z3-card-body">
                        <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                        <h3><a href="{{ site_route('news.show', $item) }}">{{ $item->title }}</a></h3>
                        @if (filled($item->excerpt))<p class="z3-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($item->excerpt), 180) }}</p>@endif
                        <span class="z3-more" aria-hidden="true"><i class="fa-solid fa-circle-arrow-right"></i></span>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="z3-center"><a class="z3-btn" href="{{ site_route('news.index') }}">Wszystkie aktualności</a></p>
    </div>
</section>
@endif
