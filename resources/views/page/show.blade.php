@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Projekty', 'url' => route('projects.index')] : null,
        $page->project && $page->project->category ? ['label' => $page->project->category->name, 'url' => route('categories.show', $page->project->category)] : null,
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null,
        // Pełna ścieżka działu (rootline): wszystkie strony nadrzędne, nie tylko bezpośredni rodzic.
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
            $showLocalNav = ($page->show_side_nav ?? true) && $menuSiblings->isNotEmpty() && ! $tilesNav;
            $showTabsNav  = $showLocalNav && $page->sideNavStyle() === 'tabs';
            $showSideNav  = $showLocalNav && ! $showTabsNav;
            // Drzewo działu (TYPO3): szersza kolumna nawigacji po lewej stronie treści.
            $showTreeNav  = $showSideNav && $page->sideNavStyle() === 'tree';
            $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
            // Treść z shortcode'em (np. osadzony formularz) nie może być edytowana "na żywo" —
            // contenteditable widzi tylko wyrenderowany HTML, zapisanie go z powrotem
            // zgubiłoby oryginalny zapis [formularz:slug]/[kafelki:slug].
            $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
            // Spis treści z H2/H3 (nagłówki dostają id); kolumna boczna, gdy jest spis lub drzewo podstron.
            [$contentHtml, $toc] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content));
            $hasAside = $showSideNav || $toc !== [];
        @endphp

        <div @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <section class="mx-auto {{ $tilesNav ? 'max-w-6xl' : 'max-w-5xl' }} px-4 py-12" x-data="{ etr: false }">
                @if ($showTabsNav)
                    @include('partials.page-tabs-nav', ['menuSiblings' => $menuSiblings])
                @endif
                <div class="grid gap-10 {{ $hasAside ? ($showTreeNav ? 'md:grid-cols-[260px_1fr]' : 'md:grid-cols-[1fr_220px]') : '' }}">
                    <div class="min-w-0">
                        @include('partials.etr-toggle', ['etr' => $page->etr, 'title' => $page->title])

                        <div x-show="!etr" x-cloak>
                        @if ($canInlineEdit)
                            <h1 data-inline-field="title" data-inline-kind="text"
                                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                                class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>
                        @else
                            <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>
                        @endif

                        @include('partials.page-content-image')

                        @if (! $page->isTilesGrid() && $page->tiles_content_position === 'below')
                            @php $tilesFirst = collect($page->tiles ?? [])->filter(fn ($t) => filled($t['label'] ?? null) && filled($t['url'] ?? null))->values(); @endphp
                            @if ($tilesFirst->isNotEmpty())
                                <div class="mb-8">@include('partials._tiles-grid', ['tiles' => $tilesFirst, 'label' => $page->title])</div>
                            @endif
                        @endif

                        @if ($toc)
                            @include('partials.page-toc', ['toc' => $toc, 'variant' => 'mobile'])
                        @endif

                        @if ($canInlineEdit && ! $contentHasShortcode)
                            <div data-inline-field="content" data-inline-kind="rich"
                                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                                class="prose max-w-none text-ink">{!! $contentHtml !!}</div>
                        @else
                            <div class="prose max-w-none text-ink">{!! $contentHtml !!}</div>
                        @endif

                        @include('partials.page-gallery', ['page' => $page])

                        @if (! $page->isTilesGrid() && $page->tiles_content_position !== 'below')
                            @php $tilesAfter = collect($page->tiles ?? [])->filter(fn ($t) => filled($t['label'] ?? null) && filled($t['url'] ?? null))->values(); @endphp
                            @if ($tilesAfter->isNotEmpty())
                                <div class="mt-10">@include('partials._tiles-grid', ['tiles' => $tilesAfter, 'label' => $page->title])</div>
                            @endif
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
                    </div>

                    @if ($hasAside)
                        <div class="space-y-8 md:sticky md:top-24 md:self-start {{ $showTreeNav ? 'md:order-first' : '' }}">
                            @if ($toc)
                                @include('partials.page-toc', ['toc' => $toc, 'variant' => 'desktop'])
                            @endif
                            @if ($showSideNav)
                                @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        </div>
        @endif
    @endif
@endsection
