@extends('layouts.site')

{{--
    Portal kategorii działań (/portal/{adres}) — układ portalu tematycznego (wzór: działy serwisów miejskich):
    slider z przyciskiem „Zatrzymaj” + obok menu działań kategorii z rozwijanymi podstronami i „Na skróty”,
    niżej aktualności (wyróżniona + lista) i widżety w kolejności z panelu, na końcu „Pozostańmy w kontakcie”.
    Płasko i kontrastowo (brandbook FEER): bez ramek i etykiet, kolory marki, tekst ≥ 4,5:1.
--}}

@section('title', $title . ' — ' . $siteSettings->site_name)

@push('structured_data')
    <link rel="alternate" type="application/rss+xml" title="{{ $title }} — RSS" href="{{ route('portal.feed', $category->portal_slug) }}">
@endpush

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Działania', 'url' => route('projects.index')],
        ['label' => $title, 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $h2 = 'text-xl font-bold leading-tight text-ink md:text-2xl';
        $bar = 'mt-2 block h-1 w-12 bg-brand';
        $more = 'inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $lead = in_array('news', $widgets, true) ? $news->first() : null;
        $rest = $news->slice(1);
        // Bez slidera i bez aktualności kafelki działań przechodzą do głównej kolumny (obok menu), żeby nie stała pusta.
        $projectsInMain = $slides->isEmpty() && ! $lead && $projects->isNotEmpty() && in_array('projects', $widgets, true);
    @endphp

    <div class="mx-auto max-w-6xl px-4 pb-14 pt-6">
        <header class="mb-6">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Portal</p>
            <h1 class="mt-1 text-3xl font-bold leading-tight text-ink md:text-4xl">{{ $title }}</h1>
            <span class="{{ $bar }}" aria-hidden="true"></span>
            @if ($category->portal_intro)
                <p class="mt-4 max-w-3xl text-lg leading-relaxed text-ink">{{ $category->portal_intro }}</p>
            @endif
        </header>

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            {{-- ═════ Lewa kolumna: slider i aktualności ═════ --}}
            <div class="min-w-0 space-y-12">
                @if ($slides->isNotEmpty())
                    <div class="portal-slider -mx-4 overflow-hidden rounded-md bg-white sm:mx-0">
                        @include('templates.feer.partials.hero', ['slides' => $slides, 'sliderLabel' => 'Slider: ' . $title, 'sliderDuration' => $sliderDuration])
                    </div>
                @endif

                @if ($lead)
                    <section aria-labelledby="portal-news">
                        <h2 id="portal-news" class="{{ $h2 }}">Aktualności</h2>
                        <span class="{{ $bar }}" aria-hidden="true"></span>

                        <article class="mt-6 grid gap-5 md:grid-cols-2">
                            @if ($img = $lead->imageUrlOrDefault())
                                <img src="{{ $img }}" alt="{{ $lead->image_alt ?? '' }}" class="aspect-[16/10] w-full rounded-md object-cover" loading="lazy">
                            @endif
                            <div>
                                <p class="text-sm text-muted"><time datetime="{{ $lead->published_at->toDateString() }}">{{ $lead->published_at->translatedFormat('j F Y') }}</time></p>
                                <h3 class="mt-1 text-2xl font-bold leading-snug text-ink">
                                    <a href="{{ route('news.show', $lead) }}" class="hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $lead->title }}</a>
                                </h3>
                                @if ($excerpt = trim(strip_tags((string) ($lead->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $lead->content), 320)))))
                                    <p class="mt-3 leading-relaxed text-ink">{{ $excerpt }}</p>
                                @endif
                                <p class="mt-3"><a href="{{ route('news.show', $lead) }}" class="{{ $more }}">Pełna treść<span class="sr-only">: {{ $lead->title }}</span> <span aria-hidden="true">→</span></a></p>
                            </div>
                        </article>

                        @if ($rest->isNotEmpty())
                            <ul role="list" class="mt-6 grid gap-x-8 sm:grid-cols-2">
                                @foreach ($rest as $item)
                                    <li class="border-t border-gray-200 py-3">
                                        <a href="{{ route('news.show', $item) }}" class="group block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            <span class="block text-xs text-muted"><time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('j F Y') }}</time></span>
                                            <span class="mt-0.5 block font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $item->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (Route::has('news.index'))
                            <p class="mt-4"><a href="{{ route('news.index') }}" class="{{ $more }}">Wszystkie aktualności <span aria-hidden="true">→</span></a></p>
                        @endif
                    </section>
                @endif

                @if ($projectsInMain)
                    @include('portal.widgets.projects', ['cols' => 'sm:grid-cols-2'])
                @endif
            </div>

            {{-- ═════ Prawa kolumna: menu działań i „Na skróty” ═════ --}}
            <aside class="space-y-6 lg:sticky lg:top-4">
                <nav aria-labelledby="portal-nav" class="rounded-md bg-gray-50 p-5">
                    <h2 id="portal-nav" class="font-bold leading-snug text-ink">{{ $title }}</h2>
                    <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                    @if ($projects->isEmpty())
                        <p class="text-sm text-muted">Brak opublikowanych działań.</p>
                    @else
                        <ul role="list" class="space-y-0.5">
                            @foreach ($projects as $project)
                                @php $subs = $project->publishedPages; @endphp
                                <li @if ($subs->isNotEmpty()) x-data="{ open: false }" @endif>
                                    <div class="flex items-stretch">
                                        <a href="{{ route('projects.show', $project) }}" class="flex min-h-10 min-w-0 flex-1 items-center rounded-md px-3 py-2 text-[0.9375rem] text-ink hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $project->nav_title ?: $project->title }}</a>
                                        @if ($subs->isNotEmpty())
                                            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="portal-sub-{{ $project->id }}"
                                                class="flex w-10 flex-none items-center justify-center rounded-md text-xs text-ink hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                <i class="fa-solid fa-chevron-down transition-transform motion-reduce:transition-none" :class="open && 'rotate-180'" aria-hidden="true"></i>
                                                <span class="sr-only">Rozwiń: {{ $project->title }}</span>
                                            </button>
                                        @endif
                                    </div>
                                    @if ($subs->isNotEmpty())
                                        <ul id="portal-sub-{{ $project->id }}" role="list" class="side-sub mb-1 space-y-0.5" x-show="open" x-cloak>
                                            @foreach ($subs as $sub)
                                                <li><a href="{{ $sub->publicUrl() }}" class="flex min-h-9 items-center rounded-md px-3 py-1.5 text-sm text-ink hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $sub->title }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </nav>

                <nav aria-labelledby="portal-shortcuts" class="rounded-md bg-gray-50 p-5">
                    <h2 id="portal-shortcuts" class="font-bold text-ink">Na skróty</h2>
                    <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                    <ul role="list" class="space-y-0.5 text-[0.9375rem]">
                        @php
                            $shortcuts = array_filter([
                                ['Wszystkie działania w kategorii', route('categories.show', $category)],
                                Route::has('events.index') ? ['Kalendarz szkoleń i wydarzeń', route('events.index')] : null,
                                Route::has('newsletter.show') ? ['Zapisz się do newslettera', route('newsletter.show')] : null,
                                Route::has('contact.show') ? ['Kontakt z fundacją', route('contact.show')] : null,
                                ['Kanał RSS portalu', route('portal.feed', $category->portal_slug)],
                            ]);
                        @endphp
                        @foreach ($shortcuts as [$label, $url])
                            <li><a href="{{ $url }}" class="flex min-h-10 items-center justify-between gap-3 rounded-md px-3 py-2 text-ink hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $label }} <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        </div>

        {{-- ═════ Widżety w kolejności z panelu ═════ --}}
        <div class="mt-14 space-y-14">
            @foreach ($widgets as $widget)
                @continue($widget === 'projects' && $projectsInMain)
                @includeIf('portal.widgets.' . $widget)
            @endforeach
        </div>

        {{-- ═════ Pozostańmy w kontakcie ═════ --}}
        <section aria-labelledby="portal-stay" class="mt-14 rounded-md bg-brand-light p-6 md:p-8">
            <h2 id="portal-stay" class="{{ $h2 }}">Pozostańmy w kontakcie</h2>
            <span class="{{ $bar }}" aria-hidden="true"></span>
            <div class="mt-5 grid gap-6 md:grid-cols-3">
                @if (Route::has('newsletter.show'))
                    <div>
                        <h3 class="font-bold text-ink">Newsletter</h3>
                        <p class="mt-1 text-ink">Nowe szkolenia, materiały i wydarzenia prosto na Twoją skrzynkę.</p>
                        <p class="mt-2"><a href="{{ route('newsletter.show') }}" class="{{ $more }}">Zapisz się <span aria-hidden="true">→</span></a></p>
                    </div>
                @endif
                @if (Route::has('contact.show'))
                    <div>
                        <h3 class="font-bold text-ink">Napisz do nas</h3>
                        <p class="mt-1 text-ink">Masz pytanie o działania z tego portalu? Odpowiemy tak szybko, jak to możliwe.</p>
                        <p class="mt-2"><a href="{{ route('contact.show') }}" class="{{ $more }}">Formularz kontaktowy <span aria-hidden="true">→</span></a></p>
                    </div>
                @endif
                <div>
                    <h3 class="font-bold text-ink">RSS</h3>
                    <p class="mt-1 text-ink">Śledź aktualności i nowe działania tego portalu w czytniku RSS.</p>
                    <p class="mt-2"><a href="{{ route('portal.feed', $category->portal_slug) }}" class="{{ $more }}"><i class="fa-solid fa-rss" aria-hidden="true"></i> Kanał RSS</a></p>
                </div>
            </div>
        </section>
    </div>
@endsection
