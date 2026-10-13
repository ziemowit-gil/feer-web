@extends('layouts.site')

{{--
    Domyślny widok strony (page_template „default") — układ artykułu portalu miejskiego,
    spójny z wpisem „portal" (news/_portal) i stroną działu (show-section):
    nad tytułem nazwa działu, H1, szary pasek z datą aktualizacji, czasem czytania,
    drukowaniem i rozmiarem tekstu (A A A); lewa kolumna (3/4) — treść; prawa (1/4,
    przyklejona) — spis treści i menu działu; styl „drzewo" zostaje po lewej stronie.
    Pod spodem „Zobacz także w dziale", gdy menu działu nie jest pokazane.
    Typy stron z własnym widokiem renderuje page.partials.typed-content (bez zmian).
--}}

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Działania', 'url' => route('projects.index')] : null,
        $page->project && $page->project->category ? ['label' => $page->project->category->name, 'url' => route('categories.show', $page->project->category)] : null,
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null,
        // Pełna ścieżka działu (rootline): wszystkie strony nadrzędne, nie tylko bezpośredni rodzic.
        ...($page->ancestors()->isNotEmpty() ? [] : $page->menuTrail()),
        ...$page->ancestors()->map(fn ($a) => ['label' => $a->title, 'url' => $a->publicUrl()])->all(),
        ['label' => $page->title, 'url' => null],
    ])])
@endsection

@section('content')
    @if ($page->isAccessRestricted() && $page->access_mode === 'microsoft' && auth('member')->check())
        <div class="border-b border-brand/20 bg-brand-light">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                <span class="flex items-center gap-2 text-brand-dark">
                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                    Strefa wewnętrzna — zalogowano jako <strong>{{ auth('member')->user()->email }}</strong>
                </span>
                <form method="POST" action="{{ route('member.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 font-medium text-brand transition hover:text-brand-dark">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Wyloguj ze strefy
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else
        @include('page.partials.typed-content')

        @if ($page->usesStandardLayout())
        @php
            $menuSiblings = $page->menuSiblings();
            // „Nawigacja kafelkowa": podstrony jako kafelki pod treścią (lista boczna byłaby duplikatem).
            $tilesNav     = $page->sideNavStyle() === 'tiles' && $page->publishedChildren->isNotEmpty();
            // „Akordeon": podstrony działu jako rozwijane sekcje pod treścią strony działu (lista boczna byłaby duplikatem).
            $accordionNav = $page->sideNavStyle() === 'accordion' && $page->publishedChildren->isNotEmpty();
            $showLocalNav = ($page->show_side_nav ?? true) && $menuSiblings->isNotEmpty() && ! $tilesNav && ! $accordionNav;
            $showTabsNav  = $showLocalNav && $page->sideNavStyle() === 'tabs';
            $showSideNav  = $showLocalNav && ! $showTabsNav;
            // Drzewo działu (TYPO3): szersza kolumna nawigacji po lewej stronie treści.
            $showTreeNav  = $showSideNav && $page->sideNavStyle() === 'tree';
            $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
            // Treść z shortcode'em (np. osadzony formularz) nie może być edytowana "na żywo" —
            // contenteditable widzi tylko wyrenderowany HTML, zapisanie go z powrotem
            // zgubiłoby oryginalny zapis [formularz:slug]/[kafelki:slug].
            $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
            // Spis treści z H2/H3 (nagłówki dostają id); kolumna boczna, gdy jest spis lub menu działu.
            [$contentHtml, $toc] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content));
            $hasAside = $showSideNav || $toc !== [];

            // Nagłówek w stylu portalu: dział nad tytułem, data aktualizacji, czas czytania.
            $ppSection = $page->parent
                ? ['label' => $page->parent->title, 'url' => $page->parent->publicUrl()]
                : ($page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null);
            $ppFeer = ($siteSettings->site_template ?? 'default') === 'feer';
            $ppWords = count(preg_split('/\s+/u', trim(strip_tags(str_replace('<', ' <', (string) $page->content))), -1, PREG_SPLIT_NO_EMPTY));
            $ppReadMin = max(1, (int) ceil($ppWords / 200));
            $ppSizeBtn = 'inline-flex h-11 w-11 items-center justify-center rounded-md font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
            $ppSizes = [
                'normal' => ['label' => 'Tekst normalny', 'class' => 'text-sm'],
                'large' => ['label' => 'Większy tekst', 'class' => 'text-base'],
                'larger' => ['label' => 'Największy tekst', 'class' => 'text-xl'],
            ];

            // „Zobacz także w dziale": rodzeństwo strony, gdy menu działu (boczne/zakładki) nie jest pokazane.
            $seeAlso = (! $showLocalNav && ($page->parent_id || $page->project_id))
                ? $menuSiblings->reject(fn ($s) => $s->is($page))->take(4)->values()
                : collect();

            // Układ kolumn: drzewo po lewej (jak dotąd), inaczej treść 3/4 + kolumna boczna 1/4.
            $gridClass = ! $hasAside ? '' : ($showTreeNav
                ? 'grid gap-10 md:grid-cols-[260px_1fr]'
                : 'grid gap-10 md:grid-cols-[minmax(0,1fr)_14rem] lg:grid-cols-4');
        @endphp

        <style>
            /* Rozmiar tekstu strony (przyciski A A A) — skaluje tylko treść, nie tytuł ani menu. */
            .page-portal-article { --pp-scale: 1; }
            .page-portal-article.pp-size-large { --pp-scale: 1.2; }
            .page-portal-article.pp-size-larger { --pp-scale: 1.4; }
            .page-portal-article .page-portal-body { font-size: calc(1.125rem * var(--pp-scale)); }
        </style>

        <div @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <section class="mx-auto max-w-6xl px-4 py-10" x-data="{ etr: false }">
                @if ($showTabsNav)
                    @include('partials.page-tabs-nav', ['menuSiblings' => $menuSiblings])
                @endif
                <div class="{{ $gridClass }}">
                    <article class="page-portal-article pp-size-normal min-w-0 {{ $hasAside && ! $showTreeNav ? 'lg:col-span-3' : '' }}"
                        x-data="{
                            size: 'normal',
                            init() {
                                try {
                                    const s = localStorage.getItem('feer-page-text-size');
                                    if (['normal', 'large', 'larger'].includes(s)) this.size = s;
                                } catch (e) {}
                            },
                            setSize(s) {
                                this.size = s;
                                try { localStorage.setItem('feer-page-text-size', s); } catch (e) {}
                            },
                        }"
                        :class="'pp-size-' + size">
                        @include('partials.etr-toggle', ['etr' => $page->etr, 'title' => $page->title])

                        <div x-show="!etr" x-cloak>
                        <header class="mb-8">
                            @if ($ppSection)
                                <p class="text-sm font-bold uppercase tracking-widest">
                                    <a href="{{ $ppSection['url'] }}" class="text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $ppSection['label'] }}</a>
                                </p>
                            @endif

                            @if ($canInlineEdit)
                                <h1 data-inline-field="title" data-inline-kind="text"
                                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                                    class="{{ $ppSection ? 'mt-3' : '' }} text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $page->title }}</h1>
                            @else
                                <h1 class="{{ $ppSection ? 'mt-3' : '' }} text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $page->title }}</h1>
                            @endif
                            @if ($ppFeer)<span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-md bg-gray-50 px-4 py-3">
                                <p class="text-sm text-muted">
                                    {{ $ppReadMin }} min czytania
                                </p>
                                <div class="flex flex-wrap items-center gap-3 no-print print:hidden">
                                    @include('partials.print-button')
                                    <div class="flex items-center gap-1" x-cloak role="group" aria-label="Rozmiar tekstu strony">
                                        @foreach ($ppSizes as $key => $opt)
                                            <button type="button" @click="setSize('{{ $key }}')"
                                                aria-pressed="{{ $key === 'normal' ? 'true' : 'false' }}"
                                                :aria-pressed="(size === '{{ $key }}').toString()"
                                                :class="size === '{{ $key }}' ? 'bg-ink text-white hover:bg-ink' : 'bg-white text-ink hover:bg-gray-200'"
                                                class="{{ $ppSizeBtn }} {{ $opt['class'] }}"
                                                title="{{ $opt['label'] }}">
                                                <span aria-hidden="true">A</span><span class="sr-only">{{ $opt['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </header>

                        @include('partials.page-content-image')

                        @if (! $page->isTilesGrid() && $page->showsTiles() && $page->tiles_content_position === 'below')
                            @php $tilesFirst = collect($page->tiles ?? [])->filter(fn ($t) => filled($t['heading'] ?? null) || (filled($t['label'] ?? null) && filled($t['url'] ?? null)))->values(); @endphp
                            @if ($tilesFirst->isNotEmpty())
                                <div class="mb-8">@include('partials._tiles-sections', ['tiles' => $tilesFirst->all(), 'label' => $page->title])</div>
                            @endif
                        @endif

                        @if ($toc)
                            @include('partials.page-toc', ['toc' => $toc, 'variant' => 'mobile'])
                        @endif

                        @if ($canInlineEdit && ! $contentHasShortcode)
                            <div data-inline-field="content" data-inline-kind="rich"
                                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                                class="page-portal-body prose max-w-none text-ink">{!! $contentHtml !!}</div>
                        @else
                            <div class="page-portal-body prose max-w-none text-ink">{!! $contentHtml !!}</div>
                        @endif

                        @include('partials.page-gallery', ['page' => $page])

                        @if (! $page->isTilesGrid() && $page->showsTiles() && $page->tiles_content_position !== 'below')
                            @php $tilesAfter = collect($page->tiles ?? [])->filter(fn ($t) => filled($t['heading'] ?? null) || (filled($t['label'] ?? null) && filled($t['url'] ?? null)))->values(); @endphp
                            @if ($tilesAfter->isNotEmpty())
                                <div class="mt-10">@include('partials._tiles-sections', ['tiles' => $tilesAfter->all(), 'label' => $page->title])</div>
                            @endif
                        @endif

                        @if ($accordionNav)
                            @include('partials.page-children-accordion', ['children' => $page->publishedChildren()->orderBy('order')->orderBy('title')->get(), 'page' => $page])
                        @endif

                        @if ($tilesNav)
                            @include('partials.page-tiles-nav', ['tiles' => $page->publishedChildren()->orderBy('order')->orderBy('title')->get()])
                        @endif

                        @include('partials.attachments-list', ['attachments' => $page->attachments])

                        {{-- Blok „W tym dziale" dubluje boczne menu, więc pokazujemy go tylko, gdy menu nie ma. --}}
                        @unless ($showSideNav)
                            @include('partials.page-section-nav', ['page' => $page])
                        @endunless
                        </div>
                    </article>

                    @if ($hasAside)
                        {{-- Kolumna boczna: na telefonie pod treścią; od md przyklejona (jak warianty spisu treści). --}}
                        <aside aria-label="Kolumna boczna" class="space-y-6 print:hidden md:sticky md:top-24 md:self-start {{ $showTreeNav ? 'md:order-first' : '' }}">
                            @if ($toc)
                                @include('partials.page-toc', ['toc' => $toc, 'variant' => 'desktop'])
                            @endif
                            @if ($showSideNav)
                                @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                            @endif
                        </aside>
                    @endif
                </div>

                @if ($seeAlso->isNotEmpty())
                    @include('page.partials.portal-see-also', ['items' => $seeAlso])
                @endif
            </section>
        </div>
        @endif
    @endif
@endsection
