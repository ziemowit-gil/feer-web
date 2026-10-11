@extends('layouts.site')

{{--
    Szablon „Strona działu" (układ portalu miejskiego): lewa, szeroka kolumna —
    slider ze zdjęć strony, aktualności (wyróżniona duża + siatka po trzy), treść;
    prawa kolumna — menu działu (podstrony). Płasko: bez ramek i etykiet, kolory
    firmowe. Slider: przycisk wstrzymania (WCAG 2.2.2), strzałki, kropki,
    zatrzymanie przy najechaniu/fokusie, bez autoodtwarzania przy ograniczonym ruchu.
    Dane: $page, $sectionNews (PageController::sectionNews).
--}}

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->parent ? ['label' => $page->parent->title, 'url' => $page->parent->publicUrl()] : null,
        ['label' => $page->title, 'url' => null],
    ])])
@endsection

@section('content')
    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else

    @php
        // Strona działu z podstronami pokazuje własne podstrony; podstrona w tym szablonie — rodzeństwo.
        $ownChildren = $page->publishedChildren()->where('type', '!=', 'about_person')->get();
        $sectionRoot = $ownChildren->isNotEmpty() ? $page : ($page->parent ?: $page);
        $menuSiblings = $ownChildren->isNotEmpty() ? $ownChildren : $page->menuSiblings();
        $settings = $page->layout_data ?? [];
        $slides = ($settings['slider'] ?? true)
            ? $page->images->filter(fn ($i) => $i->image_url)->sortBy('order')->values()
            : collect();
        $sectionNews = $sectionNews ?? collect();
        $featured = $sectionNews->first();
        $restNews = $sectionNews->slice(1)->values();
        $newsCategory = ($settings['news_category_id'] ?? null) ? $featured?->category : null;
        $allNewsUrl = route('news.index', $newsCategory ? ['kategoria' => $newsCategory->slug] : []);
        $contentHtml = \App\Support\ShortcodeParser::render($page->content);
        $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
        $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $date = fn ($d) => $d?->locale('pl')->translatedFormat('j F Y');
    @endphp

    @if ($canInlineEdit)
        <div x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })">
            @include('partials.inline-edit-bar')
        </div>
    @endif

    @if ($page->wipIsNotice())
        <div class="px-4 pt-6">@include('partials.page-wip-notice', ['message' => $page->wipMessage()])</div>
    @endif

    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0">
                <h1 class="mb-6 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>

                {{-- ===== SLIDER ===== --}}
                @if ($slides->isNotEmpty())
                    <section class="mb-10" aria-roledescription="karuzela" aria-label="Zdjęcia: {{ $page->title }}"
                        x-data="{
                            i: 0, n: {{ $slides->count() }}, playing: {{ $slides->count() > 1 ? 'true' : 'false' }}, paused: false, timer: null,
                            init() {
                                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.playing = false;
                                this.timer = setInterval(() => { if (this.playing && ! this.paused) this.next(); }, 6000);
                            },
                            go(k) { this.i = (k + this.n) % this.n; },
                            next() { this.go(this.i + 1); },
                            prev() { this.go(this.i - 1); },
                        }"
                        @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false">
                        <div class="relative overflow-hidden rounded-md bg-gray-100">
                            @foreach ($slides as $k => $slide)
                                <figure x-show="i === {{ $k }}" @if ($k > 0) x-cloak @endif
                                    role="group" aria-roledescription="slajd" aria-label="{{ $k + 1 }} z {{ $slides->count() }}"
                                    :aria-hidden="(i !== {{ $k }}).toString()">
                                    <img src="{{ $slide->image_url }}" alt="{{ $slide->alt }}" class="aspect-[16/7] w-full object-cover" @if ($k > 0) loading="lazy" @endif>
                                    @if ($slide->caption)
                                        <figcaption class="bg-ink px-4 py-3 text-white">{{ $slide->caption }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                        @if ($slides->count() > 1)
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <button type="button" @click="playing = ! playing" :aria-pressed="(! playing).toString()"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-md px-2 text-sm font-bold uppercase tracking-wide text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'" aria-hidden="true"></i>
                                    <span x-text="playing ? 'Wstrzymaj' : 'Wznów'">Wstrzymaj</span>
                                </button>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="prev()" class="flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Poprzedni slajd"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                                    @foreach ($slides as $k => $slide)
                                        <button type="button" @click="go({{ $k }})" :aria-current="(i === {{ $k }}).toString()"
                                            class="flex h-11 w-8 items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                            aria-label="Slajd {{ $k + 1 }} z {{ $slides->count() }}">
                                            <span class="block h-3 w-3 rounded-full" :class="i === {{ $k }} ? 'bg-brand-dark' : 'bg-gray-400'" aria-hidden="true"></span>
                                        </button>
                                    @endforeach
                                    <button type="button" @click="next()" class="flex h-11 w-11 items-center justify-center rounded-md text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" aria-label="Następny slajd"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- ===== AKTUALNOŚCI ===== --}}
                @if ($featured)
                    <section class="mb-10" aria-labelledby="section-news-heading">
                        <h2 id="section-news-heading" class="mb-6 text-2xl font-bold text-ink">
                            Aktualności
                            <span class="mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                        </h2>

                        <article class="grid gap-6 rounded-md bg-gray-50 p-4 md:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] md:p-6">
                            @if ($img = $featured->imageUrlOrDefault())
                                <img src="{{ $img }}" alt="" class="aspect-[4/3] w-full rounded-md object-cover">
                            @endif
                            <div>
                                <p class="text-sm text-muted"><time datetime="{{ $featured->published_at?->toDateString() }}">{{ $date($featured->published_at) }}</time>@if ($featured->category) · {{ $featured->category->name }}@endif</p>
                                <h3 class="mt-2 text-2xl font-bold leading-tight text-ink">
                                    <a href="{{ route('news.show', $featured) }}" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $featured->title }}</a>
                                </h3>
                                @if ($featured->excerpt)
                                    <p class="mt-3 leading-relaxed text-ink">{{ $featured->excerpt }}</p>
                                @endif
                                <p class="mt-4"><a href="{{ route('news.show', $featured) }}" class="{{ $link }}">Pełna treść<span class="sr-only">: {{ $featured->title }}</span></a></p>
                            </div>
                        </article>

                        @if ($restNews->isNotEmpty())
                            <ul class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($restNews as $item)
                                    <li>
                                        <article>
                                            @if ($img = $item->imageUrlOrDefault())
                                                <img src="{{ $img }}" alt="" loading="lazy" class="mb-3 aspect-[3/2] w-full rounded-md object-cover">
                                            @endif
                                            <h3 class="font-bold leading-snug text-ink">
                                                <a href="{{ route('news.show', $item) }}" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $item->title }}</a>
                                            </h3>
                                            <p class="mt-1 text-sm text-muted"><time datetime="{{ $item->published_at?->toDateString() }}">{{ $date($item->published_at) }}</time></p>
                                        </article>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <p class="mt-6"><a href="{{ $allNewsUrl }}" class="{{ $link }}">Wszystkie aktualności{{ $newsCategory ? ': '.$newsCategory->name : '' }}</a></p>
                    </section>
                @endif

                {{-- ===== TREŚĆ ===== --}}
                @if ($page->content)
                    <div class="prose max-w-none text-ink">{!! $contentHtml !!}</div>
                @endif

                @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>

            {{-- ===== MENU DZIAŁU ===== --}}
            @if ($menuSiblings->isNotEmpty())
                <aside class="lg:sticky lg:top-24 lg:self-start" aria-label="Menu działu">
                    @include('partials.section-nav', ['page' => $page, 'menuSiblings' => $menuSiblings, 'sectionRoot' => $sectionRoot])
                </aside>
            @endif
        </div>
    </div>

    @endif {{-- showsPlaceholder --}}
@endsection
