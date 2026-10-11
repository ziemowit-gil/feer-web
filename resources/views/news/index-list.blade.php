{{--
    Wspólna lista aktualności w układzie portalu (news/index i news/index-feer).
    Lewa kolumna: na pierwszej stronie wyróżniony najnowszy wpis (zdjęcie po lewej, tekst
    po prawej, na szarym tle), pod nim siatka kart (zdjęcie, data · kategoria zwykłym tekstem,
    tytuł-link, zajawka) albo — przy ustawieniu „lista" — płaskie wiersze; paginacja.
    Prawa kolumna (od lg przyklejona): kategorie jako zwykłe linki z aria-current,
    wyszukiwarka aktualności, zapis na newsletter, archiwum, strefa banerów.
    Płasko: bez ramek, „pigułek", gradientów i przezroczystości.

    Dane: $news (paginator), $categories, $activeCategory, $newsletter (?array —
    PageController::sectionNewsletterDefaults()), $isFeer (bool, wariant szablonu FEER).
--}}
@php
    $isFeer ??= false;
    $newsletter ??= null;
    $featured = $news->onFirstPage() ? $news->first() : null;
    $rest = $featured ? $news->slice(1)->values() : $news->getCollection();
    $asRows = in_array($siteSettings->news_layout ?? 'grid', ['list', 'side'], true);
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $titleLink = 'stretched-link hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $catUrl = fn ($c) => $c ? site_route('news.index').'?kategoria='.$c->slug : site_route('news.index');
    $meta = function ($item) {
        $parts = [$item->category?->name];
        if ($item->is_legacy) {
            $parts[] = 'Stara strona';
        }

        return collect($parts)->filter()->implode(' · ');
    };
@endphp

<div class="mx-auto max-w-6xl px-4 py-10" data-news-list>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">Aktualności</h1>
            @if ($activeCategory)
                <p class="mt-2 text-lg text-ink">Kategoria: <strong>{{ $activeCategory->name }}</strong></p>
            @endif
        </div>
        @include('partials.admin-manage-link', ['route' => 'admin.newsy.index', 'label' => 'Zarządzaj aktualnościami'])
    </div>

    {{-- Kategorie na telefonie/tablecie (od lg są w kolumnie bocznej). --}}
    <nav aria-label="Kategorie aktualności" class="mt-6 rounded-md bg-gray-50 px-4 py-3 lg:hidden">
        <ul class="flex flex-wrap gap-x-5 gap-y-1" role="list">
            <li><a href="{{ $catUrl(null) }}" @unless ($activeCategory) aria-current="page" @endunless
                   class="inline-flex min-h-11 items-center {{ $activeCategory ? $link : 'font-bold text-ink' }}">Wszystkie</a></li>
            @foreach ($categories as $category)
                @php $isActive = $activeCategory && $activeCategory->id === $category->id; @endphp
                <li><a href="{{ $catUrl($category) }}" @if ($isActive) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center {{ $isActive ? 'font-bold text-ink' : $link }}">{{ $category->name }}</a></li>
            @endforeach
            <li><a href="{{ route('news.archiwum') }}" class="inline-flex min-h-11 items-center {{ $link }}">Archiwum</a></li>
        </ul>
    </nav>

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="min-w-0">
            @if ($news->isEmpty())
                <p class="rounded-md bg-gray-50 p-5 text-ink">
                    @if ($activeCategory)
                        Brak aktualności w kategorii „{{ $activeCategory->name }}".
                        <a href="{{ $catUrl(null) }}" class="{{ $link }}">Zobacz wszystkie</a>
                    @else
                        Brak opublikowanych newsów.
                    @endif
                </p>
            @else
                {{-- ===== Wyróżniony najnowszy wpis ===== --}}
                @if ($featured)
                    @php $fImg = $featured->imageUrlOrDefault(); @endphp
                    <article class="{{ $isFeer ? 'feer-card ' : '' }}group relative mb-10 grid gap-6 rounded-md bg-gray-50 p-4 md:p-6 {{ $fImg ? 'md:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' : '' }}">
                        @if ($fImg)
                            <img src="{{ $fImg }}" alt="" class="aspect-[4/3] w-full rounded-md object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm text-muted">
                                <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->locale('pl')->translatedFormat('j F Y') }}</time>@if ($m = $meta($featured)) · {{ $m }}@endif
                            </p>
                            <h2 class="mt-2 {{ $isFeer ? 'text-2xl font-extrabold leading-tight text-ink md:text-4xl' : 'text-2xl font-bold leading-tight text-ink md:text-3xl' }}">
                                <a href="{{ site_route('news.show', $featured) }}" class="{{ $titleLink }}">{{ $featured->title }}</a>
                            </h2>
                            @if ($featured->excerpt)
                                <p class="mt-3 line-clamp-4 text-lg leading-relaxed text-ink">{{ $featured->excerpt }}</p>
                            @endif
                            <p class="mt-4 font-bold text-brand-dark underline underline-offset-4" aria-hidden="true">Czytaj więcej</p>
                        </div>
                    </article>
                @endif

                {{-- ===== Pozostałe wpisy ===== --}}
                @if ($rest->isNotEmpty())
                    @if ($featured)
                        <h2 class="sr-only">Wcześniejsze aktualności</h2>
                    @endif
                    @php $hTag = $featured ? 'h3' : 'h2'; @endphp

                    @if ($asRows)
                        <ul class="space-y-4" role="list">
                            @foreach ($rest as $item)
                                @php $img = $item->imageUrlOrDefault(); @endphp
                                <li>
                                    <article class="{{ $isFeer ? 'feer-card ' : '' }}group relative flex items-start gap-5 rounded-md bg-gray-50 p-4 hover:bg-gray-100">
                                        @if ($img)
                                            <img src="{{ $img }}" alt="" loading="lazy" class="hidden h-24 w-32 shrink-0 rounded-md object-cover sm:block">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-muted">
                                                <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>@if ($m = $meta($item)) · {{ $m }}@endif
                                            </p>
                                            <{{ $hTag }} class="mt-1 text-lg font-bold leading-snug text-ink">
                                                <a href="{{ site_route('news.show', $item) }}" class="{{ $titleLink }}">{{ $item->title }}</a>
                                            </{{ $hTag }}>
                                            @if ($item->excerpt)
                                                <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink">{{ $item->excerpt }}</p>
                                            @endif
                                        </div>
                                    </article>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" role="list">
                            @foreach ($rest as $item)
                                @php $img = $item->imageUrlOrDefault(); @endphp
                                <li>
                                    <article class="{{ $isFeer ? 'feer-card ' : '' }}group relative flex h-full flex-col {{ $img ? '' : 'rounded-md bg-gray-50 p-4' }}">
                                        @if ($img)
                                            <img src="{{ $img }}" alt="" loading="lazy" class="mb-3 aspect-[3/2] w-full rounded-md object-cover">
                                        @endif
                                        <p class="text-sm text-muted">
                                            <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>@if ($m = $meta($item)) · {{ $m }}@endif
                                        </p>
                                        <{{ $hTag }} class="mt-1 text-lg font-bold leading-snug text-ink">
                                            <a href="{{ site_route('news.show', $item) }}" class="{{ $titleLink }}">{{ $item->title }}</a>
                                        </{{ $hTag }}>
                                        @if ($item->excerpt)
                                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink">{{ $item->excerpt }}</p>
                                        @endif
                                    </article>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @endif

            @include('news.index-pagination', ['paginator' => $news, 'label' => 'Strony aktualności'])
        </div>

        {{-- ===== Kolumna boczna ===== --}}
        <aside aria-label="Kolumna boczna aktualności" class="space-y-6 print:hidden lg:sticky lg:top-24 lg:self-start">
            <nav aria-label="Kategorie aktualności" class="hidden rounded-md bg-gray-50 p-5 lg:block">
                <h2 class="text-lg font-bold text-ink">Kategorie</h2>
                <ul class="mt-3 space-y-1" role="list">
                    <li><a href="{{ $catUrl(null) }}" @unless ($activeCategory) aria-current="page" @endunless
                           class="flex min-h-11 items-center {{ $activeCategory ? $link : 'font-bold text-ink' }}">Wszystkie</a></li>
                    @foreach ($categories as $category)
                        @php $isActive = $activeCategory && $activeCategory->id === $category->id; @endphp
                        <li><a href="{{ $catUrl($category) }}" @if ($isActive) aria-current="page" @endif
                               class="flex min-h-11 items-center {{ $isActive ? 'font-bold text-ink' : $link }}">{{ $category->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('news.archiwum') }}" class="flex min-h-11 items-center {{ $link }}">Archiwum aktualności</a></li>
                </ul>
            </nav>

            <section class="rounded-md bg-gray-50 p-5" aria-labelledby="news-search-heading">
                <h2 id="news-search-heading" class="text-lg font-bold text-ink">Szukaj w aktualnościach</h2>
                <form method="GET" action="{{ route('search') }}" role="search" class="mt-3">
                    <input type="hidden" name="typ" value="aktualnosci">
                    <label for="news-search-q" class="block text-sm font-bold text-ink">Szukana fraza</label>
                    <div class="mt-1 flex gap-2">
                        <input type="search" id="news-search-q" name="q" minlength="2" required autocomplete="off"
                            class="min-h-11 w-full min-w-0 rounded-md border border-gray-500 bg-white px-3 text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md bg-brand-dark px-4 font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="sr-only">Szukaj</span>
                        </button>
                    </div>
                </form>
            </section>

            @if ($newsletter)
                {{-- Zapis na newsletter (firmowy formularz modułu Newsletter — jak w show-section). --}}
                <section class="rounded-md bg-brand-light p-5" aria-labelledby="news-newsletter-heading">
                    <h2 id="news-newsletter-heading" class="flex items-center gap-2 text-lg font-bold text-ink">
                        <i class="fa-solid fa-envelope-open-text text-brand-dark" aria-hidden="true"></i> {{ $newsletter['title'] }}
                    </h2>
                    @if (filled($newsletter['text']))
                        <p class="mt-2 text-sm text-ink">{{ $newsletter['text'] }}</p>
                    @endif
                    <a href="{{ $newsletter['url'] }}"
                        class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-4 py-2.5 text-center font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        {{ $newsletter['button'] }}
                    </a>
                </section>
            @endif

            <x-banner-zone name="sidebar" />
        </aside>
    </div>
</div>
