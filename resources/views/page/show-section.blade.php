@extends('layouts.site')

{{--
    Szablon „Strona działu" (układ portalu miejskiego): lewa, szeroka kolumna —
    slider ze zdjęć strony, aktualności (wyróżniona duża + siatka po trzy), treść;
    prawa kolumna — menu działu (podstrony). Płasko: bez ramek i etykiet, kolory
    firmowe. Slider: przycisk wstrzymania (WCAG 2.2.2), strzałki, kropki,
    zatrzymanie przy najechaniu/fokusie, bez autoodtwarzania przy ograniczonym ruchu.
    Dane: $page, $sectionPosts (PageController::sectionPosts — aktualności albo Wiem FEER).
    W prawej kolumnie opcjonalnie przycisk zapisu na newsletter (strona zapisu modułu Newsletter).
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
        $posts = $sectionPosts ?? ['items' => collect(), 'heading' => '', 'allUrl' => null, 'allLabel' => ''];
        $featured = $posts['items']->first();
        $restNews = $posts['items']->slice(1)->values();
        $newsletter = $sectionNewsletter ?? null;
        $showNewsletter = (bool) $newsletter;
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

                {{-- ===== WPISY (aktualności albo Wiem FEER) ===== --}}
                @if ($featured)
                    <section class="mb-10" aria-labelledby="section-news-heading">
                        <h2 id="section-news-heading" class="mb-6 text-2xl font-bold text-ink">
                            {{ $posts['heading'] }}
                            <span class="mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                        </h2>

                        <article class="grid gap-6 rounded-md bg-gray-50 p-4 md:p-6 {{ $featured['image'] ? 'md:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' : '' }}">
                            @if ($featured['image'])
                                <img src="{{ $featured['image'] }}" alt="" class="aspect-[4/3] w-full rounded-md object-cover">
                            @endif
                            <div>
                                <p class="text-sm text-muted"><time datetime="{{ $featured['date']?->toDateString() }}">{{ $date($featured['date']) }}</time>@if ($featured['meta']) · {{ $featured['meta'] }}@endif</p>
                                <h3 class="mt-2 text-2xl font-bold leading-tight text-ink">
                                    <a href="{{ $featured['url'] }}" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $featured['title'] }}</a>
                                </h3>
                                @if ($featured['excerpt'])
                                    <p class="mt-3 leading-relaxed text-ink">{{ $featured['excerpt'] }}</p>
                                @endif
                                <p class="mt-4"><a href="{{ $featured['url'] }}" class="{{ $link }}">Pełna treść<span class="sr-only">: {{ $featured['title'] }}</span></a></p>
                            </div>
                        </article>

                        @if ($restNews->isNotEmpty())
                            <ul class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($restNews as $item)
                                    <li>
                                        <article class="{{ $item['image'] ? '' : 'h-full rounded-md bg-gray-50 p-4' }}">
                                            @if ($item['image'])
                                                <img src="{{ $item['image'] }}" alt="" loading="lazy" class="mb-3 aspect-[3/2] w-full rounded-md object-cover">
                                            @endif
                                            <h3 class="font-bold leading-snug text-ink">
                                                <a href="{{ $item['url'] }}" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $item['title'] }}</a>
                                            </h3>
                                            <p class="mt-1 text-sm text-muted"><time datetime="{{ $item['date']?->toDateString() }}">{{ $date($item['date']) }}</time>@if (! $item['image'] && $item['meta']) · {{ $item['meta'] }}@endif</p>
                                            @if (! $item['image'] && $item['excerpt'])
                                                <p class="mt-2 text-sm leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($item['excerpt'], 140) }}</p>
                                            @endif
                                        </article>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($posts['allUrl'])
                            <p class="mt-6"><a href="{{ $posts['allUrl'] }}" class="{{ $link }}">{{ $posts['allLabel'] }}</a></p>
                        @endif
                    </section>
                @endif

                {{-- ===== TREŚĆ ===== --}}
                @if ($page->content)
                    <div class="prose max-w-none text-ink">{!! $contentHtml !!}</div>
                @endif

                @include('partials.attachments-list', ['attachments' => $page->attachments])
            </div>

            {{-- ===== MENU DZIAŁU + NEWSLETTER ===== --}}
            @if ($menuSiblings->isNotEmpty() || $showNewsletter)
                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="Kolumna boczna działu">
                    @include('partials.section-nav', ['page' => $page, 'menuSiblings' => $menuSiblings, 'sectionRoot' => $sectionRoot])
                    @if ($showNewsletter)
                        {{-- Zapis na newsletter: teksty ze strony albo z firmowego formularza (PageController::sectionNewsletter). --}}
                        <section class="rounded-md bg-brand-light p-5" aria-labelledby="section-newsletter-heading">
                            <h2 id="section-newsletter-heading" class="flex items-center gap-2 text-lg font-bold text-ink">
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
                </aside>
            @endif
        </div>
    </div>

    @endif {{-- showsPlaceholder --}}
@endsection
