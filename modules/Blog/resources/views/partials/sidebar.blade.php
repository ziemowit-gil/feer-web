{{--
    Kolumna boczna bloga (układ portalu, jak „Strona działu"): wyszukiwarka,
    kategorie (panel jak partials.section-nav — bieżąca na jasnym tle marki,
    pogrubiona, z aria-current), popularne tagi (zwykłe odnośniki), newsletter, RSS.
    Na desktopie przyklejona; na telefonie pod listą.
    Najpopularniejsze (licznik wyświetleń) — tylko gdy kontroler przekaże $popular (lista wpisów).
    Zmienne: $categories, $tags, $newsletter, opcjonalnie $q, $category, $tag, $searchId, $popular.
--}}
@php
    $currentCategory = $category ?? null;
    $currentTag = $tag ?? null;
    $sbLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<aside class="space-y-6 lg:sticky lg:top-24 lg:self-start print:hidden" aria-label="Kolumna boczna Wiem FEER">
    @include('blog::partials.search-form', ['formId' => $searchId ?? 'blog-search-sidebar', 'q' => $q ?? ''])

    @if ($categories->isNotEmpty())
        <nav class="rounded-md bg-gray-50 p-5" aria-labelledby="blog-categories-heading">
            <h2 id="blog-categories-heading" class="text-lg font-bold text-ink">Kategorie</h2>
            <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <ul class="space-y-1">
                @php $allCurrent = ! $currentCategory && request()->routeIs('blog.index') && ! request()->query(); @endphp
                <li>
                    <a href="{{ route('blog.index') }}" @if ($allCurrent) aria-current="page" @endif
                        class="flex items-center justify-between gap-3 rounded-md px-3 py-2.5 text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $allCurrent ? 'bg-brand-light font-bold' : 'font-semibold' }}">
                        <span>Wszystkie wpisy</span>
                        <i class="fa-solid fa-chevron-right shrink-0 text-xs text-brand-dark" aria-hidden="true"></i>
                    </a>
                </li>
                @foreach ($categories as $item)
                    @php $current = $currentCategory && $currentCategory->is($item); @endphp
                    <li>
                        <a href="{{ $item->url() }}" @if ($current) aria-current="page" @endif
                            class="flex items-center justify-between gap-3 rounded-md px-3 py-2.5 text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $current ? 'bg-brand-light font-bold' : 'font-semibold' }}">
                            <span>{{ $item->name }}</span>
                            <i class="fa-solid fa-chevron-right shrink-0 text-xs text-brand-dark" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if (isset($popular) && $popular->isNotEmpty())
        <section class="rounded-md bg-gray-50 p-5" aria-labelledby="blog-popular-heading">
            <h2 id="blog-popular-heading" class="text-lg font-bold text-ink">Najpopularniejsze</h2>
            <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <ol class="list-decimal space-y-2 pl-5 text-sm marker:text-ink">
                @foreach ($popular as $item)
                    <li><a href="{{ route('blog.show', $item) }}" class="{{ $sbLink }}">{{ $item->title }}</a></li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($tags->isNotEmpty())
        <section class="rounded-md bg-gray-50 p-5" aria-labelledby="blog-tags-heading">
            <h2 id="blog-tags-heading" class="text-lg font-bold text-ink">Popularne tagi</h2>
            <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <ul class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach ($tags as $item)
                    @php $current = $currentTag && $currentTag->is($item); @endphp
                    <li>
                        <a href="{{ $item->url() }}" @if ($current) aria-current="page" @endif class="{{ $sbLink }} {{ $current ? 'rounded-sm bg-brand-light px-1' : '' }}">{{ $item->name }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @include('blog::partials.newsletter', ['newsletter' => $newsletter ?? null])

    <p class="px-1 text-sm">
        <a href="{{ route('blog.authors') }}" class="{{ $sbLink }}">Autorzy Wiem FEER</a>
    </p>
    <p class="px-1 text-sm">
        <a href="{{ route('blog.feed') }}" class="{{ $sbLink }}"><i class="fa-solid fa-rss mr-1" aria-hidden="true"></i>Kanał RSS</a>
    </p>
</aside>
