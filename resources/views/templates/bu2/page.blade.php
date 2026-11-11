{{--
    Strona w szablonie „bu2”: menu boczne (layout BU2), tytuł z linią, treść (zakładki, pliki, tabele w wyglądzie BU2),
    galeria i kafle z edycji strony, „Pliki do pobrania”, kafle podstron (gałąź tej strony w Menu nawigacji, a gdy jej
    tam nie ma — opublikowane podstrony) i „Drukuj”. Typy stron z własnym widokiem renderuje page.partials.typed-content.
    Edycja na żywo i wersja ETR działają jak w zwykłym widoku strony.
--}}
@extends('templates.bu2.layout')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        ...($page->project ? ($page->project->category ? $page->project->category->breadcrumbTrail() : [['label' => 'Działania', 'url' => route('projects.index')]]) : []),
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null,
        ...($page->ancestors()->isNotEmpty() ? [] : $page->menuTrail()),
        ...$page->ancestors()->map(fn ($a) => ['label' => $a->title, 'url' => $a->publicUrl()])->all(),
        ['label' => $page->title, 'url' => null],
    ])])
@endsection

@section('bu2_main')
    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else
        @include('page.partials.typed-content')

        @if ($page->usesStandardLayout())
            @php
                $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
                $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
                $contentHtml = \App\Support\ShortcodeParser::render($page->content);
                $menuNode = \App\Support\Bu2Menu::currentNode();
                $tiles = $menuNode && $menuNode['children'] !== []
                    ? $menuNode['children']
                    : $page->publishedChildren()->orderBy('order')->orderBy('title')->get()
                        ->map(fn ($c) => ['label' => $c->title, 'url' => $c->publicUrl(), 'icon' => ($c->layout_data ?? [])['icon'] ?? null])->all();
                $pageTiles = (! $page->isTilesGrid() && $page->showsTiles())
                    ? collect($page->tiles ?? [])->filter(fn ($t) => filled($t['heading'] ?? null) || (filled($t['label'] ?? null) && filled($t['url'] ?? null)))->values()
                    : collect();
            @endphp

            <article @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
                @if ($canInlineEdit)
                    @include('partials.inline-edit-bar')
                @endif
                <div x-data="{ etr: false }">
                    @if ($canInlineEdit)
                        <h1 class="bu2-title" data-inline-field="title" data-inline-kind="text" :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''">{{ $page->title }}</h1>
                    @else
                        <h1 class="bu2-title">{{ $page->title }}</h1>
                    @endif

                    @include('partials.etr-toggle', ['etr' => $page->etr, 'title' => $page->title])
                    @include('partials.pjm-video', ['url' => $page->pjm_video_url, 'title' => $page->title])

                    <div x-show="!etr">
                        @if ($page->is_archived)
                            @include('partials.archival-notice', ['date' => $page->created_at, 'class' => 'mb-6'])
                        @endif

                        @include('partials.page-content-image')

                        @if ($pageTiles->isNotEmpty() && $page->tiles_content_position === 'below')
                            <div class="mb-8">@include('partials._tiles-sections', ['tiles' => $pageTiles->all(), 'label' => $page->title])</div>
                        @endif

                        @if ($canInlineEdit && ! $contentHasShortcode)
                            <div id="page-text" class="bu2-body prose" data-inline-field="content" data-inline-kind="rich" :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''">{!! $contentHtml !!}</div>
                        @else
                            <div id="page-text" class="bu2-body prose">{!! $contentHtml !!}</div>
                        @endif

                        @include('partials.page-gallery', ['page' => $page])

                        @if ($pageTiles->isNotEmpty() && $page->tiles_content_position !== 'below')
                            <div class="mt-10">@include('partials._tiles-sections', ['tiles' => $pageTiles->all(), 'label' => $page->title])</div>
                        @endif

                        @include('templates.bu2.partials.tiles', ['nodes' => $tiles])

                        @include('partials.attachments-list', ['attachments' => $page->attachments])
                    </div>
                </div>

                @include('templates.bu2.partials.tools')
            </article>
        @endif
    @endif
@endsection
