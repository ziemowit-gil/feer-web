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
    {{-- Portal to samodzielna sekcja: Strona główna › portal (bez pośredniego „Działania”). --}}
    @include('partials.breadcrumbs', ['items' => [
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
        // Bez slidera kafelki (wiersze) działań stają obok menu, żeby główna kolumna nie była pusta.
        $projectsInMain = $slides->isEmpty() && $projects->isNotEmpty() && in_array('projects', $widgets, true);
        // Menu jak na stronie działania (projects/partials/sidebar-nav): pozycje na całą szerokość ze strzałką.
        $navItem = 'proj-nav-item flex min-h-10 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-[0.9375rem] text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <div class="mx-auto max-w-6xl px-4 pb-14 pt-6">
        <header class="mb-8">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Portal</p>
            <h1 class="mt-1 text-3xl font-bold leading-tight text-ink md:text-4xl">{{ $title }}</h1>
            <span class="{{ $bar }}" aria-hidden="true"></span>
            @if ($category->portal_intro)
                <p class="mt-4 max-w-3xl text-lg leading-relaxed text-ink">{{ $category->portal_intro }}</p>
            @endif
        </header>

        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            {{-- ═════ Lewa kolumna: slider (albo działania) ═════ --}}
            <div class="min-w-0">
                @if ($slides->isNotEmpty())
                    @include('portal.partials.slider', ['slides' => $slides, 'label' => 'Slider: ' . $title, 'duration' => $sliderDuration])
                @elseif ($projectsInMain)
                    @include('portal.widgets.projects', ['cols' => ''])
                @endif
            </div>

            {{-- ═════ Prawa kolumna: menu działań i „Na skróty” (wygląd menu bocznego działania) ═════ --}}
            <div class="space-y-4 lg:sticky lg:top-4">
                <nav aria-labelledby="portal-nav" class="rounded-md bg-gray-50 p-5">
                    <p id="portal-nav" class="text-base font-bold leading-snug text-ink">{{ $title }}</p>
                    <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                    @if ($projects->isEmpty())
                        <p class="px-3 text-sm text-ink">Brak opublikowanych działań.</p>
                    @else
                        <ul role="list" class="space-y-0.5">
                            @foreach ($projects as $project)
                                @php $subs = $project->publishedPages; @endphp
                                <li @if ($subs->isNotEmpty()) x-data="{ open: false }" @endif>
                                    @if ($subs->isEmpty())
                                        <a href="{{ route('projects.show', $project) }}" class="{{ $navItem }}">
                                            <span class="min-w-0">{{ $project->nav_title ?: $project->title }}</span>
                                            <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="portal-sub-{{ $project->id }}" class="{{ $navItem }}">
                                            <span class="min-w-0">{{ $project->nav_title ?: $project->title }}</span>
                                            <i class="fa-solid fa-chevron-right flex-none text-xs transition-transform motion-reduce:transition-none" :class="open && 'rotate-90'" aria-hidden="true"></i>
                                        </button>
                                        <ul id="portal-sub-{{ $project->id }}" role="list" class="side-sub mt-0.5 space-y-0.5" x-show="open" x-cloak>
                                            <li><a href="{{ route('projects.show', $project) }}" class="{{ $navItem }} font-bold"><span class="min-w-0">O działaniu</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                                            @foreach ($subs as $sub)
                                                <li><a href="{{ $sub->publicUrl() }}" class="{{ $navItem }}"><span class="min-w-0">{{ $sub->title }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </nav>

                <nav aria-labelledby="portal-shortcuts" class="rounded-md bg-gray-50 p-5">
                    <p id="portal-shortcuts" class="text-base font-bold text-ink">Na skróty</p>
                    <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                    @php
                        $shortcuts = array_filter([
                            ['Wszystkie działania w kategorii', route('categories.show', $category)],
                            Route::has('events.index') ? ['Kalendarz szkoleń i wydarzeń', route('events.index')] : null,
                            Route::has('newsletter.show') ? ['Zapisz się do newslettera', route('newsletter.show')] : null,
                            Route::has('contact.show') ? ['Kontakt z fundacją', route('contact.show')] : null,
                            ['Kanał RSS portalu', route('portal.feed', $category->portal_slug)],
                        ]);
                    @endphp
                    <ul role="list" class="space-y-0.5">
                        @foreach ($shortcuts as [$label, $url])
                            <li><a href="{{ $url }}" class="{{ $navItem }}"><span class="min-w-0">{{ $label }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>

        {{-- ═════ Aktualności: wyróżniona po lewej, pozostałe w kolumnie obok ═════ --}}
        @if ($lead)
            <section aria-labelledby="portal-news" class="mt-14">
                <h2 id="portal-news" class="{{ $h2 }}">Aktualności</h2>
                <span class="{{ $bar }}" aria-hidden="true"></span>
                <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <article class="grid gap-6 md:grid-cols-2">
                        @if ($img = $lead->imageUrlOrDefault())
                            <img src="{{ $img }}" alt="{{ $lead->image_alt ?? '' }}" class="aspect-[4/3] w-full rounded-md object-cover" loading="lazy">
                        @endif
                        <div>
                            <p class="text-sm text-ink"><time datetime="{{ $lead->published_at->toDateString() }}">{{ $lead->published_at->translatedFormat('j F Y') }}</time></p>
                            <h3 class="mt-1 text-xl font-bold leading-snug text-ink md:text-2xl">
                                <a href="{{ route('news.show', $lead) }}" class="hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $lead->title }}</a>
                            </h3>
                            @php $excerpt = trim(html_entity_decode(strip_tags((string) ($lead->excerpt ?: $lead->content)), ENT_QUOTES | ENT_HTML5)); @endphp
                            @if ($excerpt !== '')
                                <p class="mt-3 line-clamp-5 leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($excerpt, 360) }}</p>
                            @endif
                            <p class="mt-3"><a href="{{ route('news.show', $lead) }}" class="{{ $more }}">Pełna treść<span class="sr-only">: {{ $lead->title }}</span> <span aria-hidden="true">→</span></a></p>
                        </div>
                    </article>

                    <div>
                        @if ($rest->isNotEmpty())
                            <ul role="list">
                                @foreach ($rest as $item)
                                    <li class="border-b border-gray-200 py-3 first:pt-0">
                                        <a href="{{ route('news.show', $item) }}" class="group block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            <span class="block text-xs text-ink"><time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('j F Y') }}</time></span>
                                            <span class="mt-0.5 block font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $item->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (Route::has('news.index'))
                            <p class="mt-2"><a href="{{ route('news.index') }}" class="{{ $more }}">Wszystkie aktualności <span aria-hidden="true">→</span></a></p>
                        @endif
                    </div>
                </div>
            </section>
        @endif
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
