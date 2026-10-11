{{--
    Układ wpisu „portal" (article_layout = portal), wspólny dla news.show i news.show-feer:
    lewa kolumna (3/4) — artykuł: dział, tytuł, data, rozmiar tekstu (A A A), pogrubiona zajawka,
    zdjęcie na całą szerokość (punkt ostrości z panelu), treść, tagi, załączniki, narzędzia;
    prawa kolumna (1/4, przyklejona na desktopie, pod artykułem na telefonie) — najnowsze wpisy
    działu i powiązane działanie; pod spodem na całą szerokość „Zobacz także" (4 karty).
    Płasko: bez ramek i plakietek, grupowanie tłem. Kontrast: brand-dark/ink na bieli i gray-50.
    Wymaga: $news, $canQuickEdit, $related, $categoryNews (z NewsController::show).
--}}
@php
    $pImg = $news->imageUrlOrDefault();
    $pImgAlt = $news->image_alt ?: 'Zdjęcie ilustracyjne';
    $pFocal = (int) ($news->image_focal_x ?? 50).'% '.(int) ($news->image_focal_y ?? 50).'%';
    $pCategory = $news->category;
    $pCategoryUrl = $pCategory ? site_route('news.index').'?kategoria='.urlencode($pCategory->slug) : site_route('news.index');
    $pProject = ($news->project && $news->project->is_published) ? $news->project : null;
    $pReadMin = max(1, (int) ceil(str_word_count(strip_tags((string) $news->content)) / 200));
    $pFeer = ($siteSettings->site_template ?? 'default') === 'feer';
    $pRelated = $related ?? collect();
    $pCategoryNews = $categoryNews ?? collect();

    $pLink = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $pNavLink = 'flex min-h-11 items-start gap-2 rounded-md px-3 py-2 text-sm font-bold leading-snug text-ink transition hover:bg-white hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $pTool = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $pSize = 'inline-flex h-11 w-11 items-center justify-center rounded-md font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $pSizes = [
        'normal' => ['label' => 'Tekst normalny', 'class' => 'text-sm'],
        'large' => ['label' => 'Większy tekst', 'class' => 'text-base'],
        'larger' => ['label' => 'Największy tekst', 'class' => 'text-xl'],
    ];
@endphp

<style>
    /* Rozmiar tekstu artykułu (przyciski A A A) — skaluje tylko zajawkę i treść, nie tytuł ani menu. */
    .news-portal-article { --np-scale: 1; }
    .news-portal-article.np-size-large { --np-scale: 1.2; }
    .news-portal-article.np-size-larger { --np-scale: 1.4; }
    .news-portal-article .news-portal-lead { font-size: calc(1.25rem * var(--np-scale)); }
    .news-portal-article .news-portal-body { font-size: calc(1.125rem * var(--np-scale)); }
</style>

<section class="mx-auto max-w-6xl px-4 py-10" x-data="audioPlayer()">
    @if ($news->is_legacy)
        @include('partials.legacy-notice')
    @elseif ($news->is_archived)
        @include('partials.archival-notice', ['date' => $news->published_at])
    @endif

    @include('partials.etr-toggle', ['etr' => $news->etr, 'title' => $news->title])

    <div x-show="!etr" x-cloak>
        <div class="grid gap-10 lg:grid-cols-4">
            {{-- ── Lewa kolumna: artykuł ── --}}
            <article class="news-portal-article np-size-normal min-w-0 lg:col-span-3"
                x-data="{
                    size: 'normal',
                    init() {
                        try {
                            const s = localStorage.getItem('feer-news-text-size');
                            if (['normal', 'large', 'larger'].includes(s)) this.size = s;
                        } catch (e) {}
                    },
                    setSize(s) {
                        this.size = s;
                        try { localStorage.setItem('feer-news-text-size', s); } catch (e) {}
                    },
                }"
                :class="'np-size-' + size">
                <header>
                    <p class="text-sm font-bold uppercase tracking-widest">
                        <a href="{{ $pCategoryUrl }}" class="text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $pCategory?->name ?? 'Aktualności' }}</a>
                    </p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">
                        @if ($canQuickEdit)
                            <span x-show="!editMode" x-cloak>{{ $news->title }}</span>
                            <input x-show="editMode" x-cloak x-model="form.title" type="text" maxlength="255"
                                class="w-full rounded border-gray-400 text-3xl font-bold focus:border-brand focus:ring-brand" aria-label="Tytuł aktualności">
                        @else
                            {{ $news->title }}
                        @endif
                    </h1>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-md bg-gray-50 px-4 py-3">
                        <p class="text-sm text-muted">
                            <time datetime="{{ $news->published_at->toDateString() }}" class="font-bold text-ink">{{ $news->published_at->translatedFormat('j F Y') }}</time>
                            @if ($news->updated_at->gt($news->published_at))
                                · zaktualizowano <time datetime="{{ $news->updated_at->toDateString() }}">{{ $news->updated_at->format('d.m.Y') }}</time>
                            @endif
                            · {{ $pReadMin }} min czytania
                        </p>
                        <div class="flex items-center gap-1 print:hidden" x-cloak role="group" aria-label="Rozmiar tekstu artykułu">
                            @foreach ($pSizes as $key => $opt)
                                <button type="button" @click="setSize('{{ $key }}')"
                                    aria-pressed="{{ $key === 'normal' ? 'true' : 'false' }}"
                                    :aria-pressed="(size === '{{ $key }}').toString()"
                                    :class="size === '{{ $key }}' ? 'bg-ink text-white hover:bg-ink' : 'bg-white text-ink hover:bg-gray-200'"
                                    class="{{ $pSize }} {{ $opt['class'] }}"
                                    title="{{ $opt['label'] }}">
                                    <span aria-hidden="true">A</span><span class="sr-only">{{ $opt['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($news->excerpt)
                        <p class="news-portal-lead mt-6 font-bold leading-relaxed text-ink">{{ $news->excerpt }}</p>
                    @endif
                </header>

                @if ($pImg)
                    <figure class="mt-6">
                        <img src="{{ $pImg }}" alt="{{ $pImgAlt }}" data-lightbox
                            class="aspect-[16/9] w-full rounded-md object-cover" style="object-position: {{ $pFocal }}">
                    </figure>
                @endif

                @include('news._post-type', ['part' => 'top'])<div id="article-text" data-news-content class="news-portal-body {{ $pFeer ? 'news-feer-body' : '' }} prose prose-lg mt-8 max-w-none text-ink">@shortcodes($news->content)</div>@include('news._post-type', ['part' => 'bottom'])

                @if ($news->tags->isNotEmpty())
                    <p class="mt-8 text-sm text-muted">
                        <span class="font-bold text-ink">Tagi:</span>
                        {{ $news->tags->pluck('name')->join(', ') }}
                    </p>
                @endif

                @include('partials.attachments-list', ['attachments' => $news->attachments])

                <div class="mt-8 flex flex-wrap gap-2 print:hidden" role="group" aria-label="Opcje artykułu">
                    <button type="button" x-show="supported" x-cloak @click="play()" :aria-pressed="playing.toString()"
                        class="{{ $pTool }}" :class="playing ? 'bg-ink text-white hover:bg-ink' : ''">
                        <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-volume-high'" aria-hidden="true"></i>
                        <span x-text="playing ? 'Zatrzymaj odczyt' : 'Odsłuchaj artykuł'">Odsłuchaj artykuł</span>
                    </button>
                    <button type="button" onclick="window.print()" class="{{ $pTool }}">
                        <i class="fa-solid fa-print" aria-hidden="true"></i> Drukuj
                    </button>
                    <a href="{{ site_route('news.pdf', $news) }}" target="_blank" rel="noopener" class="{{ $pTool }}">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Tekst w PDF<span class="sr-only"> (otwiera się w nowej karcie)</span>
                    </a>
                </div>
            </article>

            {{-- ── Prawa kolumna: dział i działanie (na telefonie pod artykułem) ── --}}
            <aside class="space-y-6 print:hidden lg:sticky lg:top-24 lg:self-start" aria-label="Więcej w dziale {{ $pCategory?->name ?? 'Aktualności' }}">
                <div class="rounded-md bg-gray-50 p-5">
                    <h2 class="mb-3 text-lg font-bold text-ink">{{ $pCategory?->name ?? 'Aktualności' }}</h2>
                    @if ($pCategoryNews->isNotEmpty())
                        <ul class="space-y-1" role="list">
                            @foreach ($pCategoryNews as $item)
                                <li>
                                    <a href="{{ site_route('news.show', $item) }}" class="{{ $pNavLink }}">
                                        <i class="fa-solid fa-chevron-right mt-1 text-xs text-brand-dark" aria-hidden="true"></i>
                                        <span>{{ $item->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-muted">Brak innych wpisów w tym dziale.</p>
                    @endif
                    <p class="mt-4 text-sm">
                        <a href="{{ $pCategoryUrl }}" class="{{ $pLink }}">{{ $pCategory ? 'Wszystkie w kategorii '.$pCategory->name : 'Wszystkie aktualności' }}</a>
                    </p>
                </div>

                @if ($pProject)
                    <div class="rounded-md bg-brand-light p-5">
                        <h2 class="mb-3 text-lg font-bold text-ink">Działanie</h2>
                        <a href="{{ route('projects.show', $pProject) }}" class="{{ $pNavLink }} hover:bg-white">
                            <i class="fa-solid fa-chevron-right mt-1 text-xs text-brand-dark" aria-hidden="true"></i>
                            <span>{{ $pProject->title }}</span>
                        </a>
                    </div>
                @endif
            </aside>
        </div>

        {{-- ── Zobacz także: pełna szerokość pod obiema kolumnami ── --}}
        @if ($pRelated->isNotEmpty())
            <section class="mt-16 print:hidden" aria-labelledby="see-also-heading">
                <h2 id="see-also-heading" class="mb-6 text-2xl font-bold text-ink">Zobacz także</h2>
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                    @foreach ($pRelated as $item)
                        @php $itemImg = $item->imageUrlOrDefault(); @endphp
                        <li>
                            <article class="group relative flex h-full flex-col overflow-hidden rounded-md bg-gray-50 transition hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                                @if ($itemImg)
                                    <img src="{{ $itemImg }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover"
                                        style="object-position: {{ (int) ($item->image_focal_x ?? 50) }}% {{ (int) ($item->image_focal_y ?? 50) }}%">
                                @endif
                                <div class="flex flex-1 flex-col p-4">
                                    <h3 class="text-base font-bold leading-snug text-ink">
                                        <a href="{{ site_route('news.show', $item) }}" class="stretched-link group-hover:text-brand-dark group-hover:underline focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
                                    <p class="mt-auto pt-3 text-sm text-muted">
                                        <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time>
                                    </p>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</section>
