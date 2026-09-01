@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Projekty', 'url' => route('projects.index')] : null,
        $page->project && $page->project->category ? ['label' => $page->project->category->name, 'url' => route('categories.show', $page->project->category)] : null,
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)]
            : ($page->parent ? ['label' => $page->parent->title, 'url' => route('page.show', $page->parent)] : null),
        ['label' => $page->title, 'url' => null],
    ])])
@endsection

@section('content')
    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else
        @include('page.partials.typed-content')

        @if ($page->usesStandardLayout())
        @php
            $canInlineEdit = auth()->check() && auth()->user()->canAccessModule('pages');
            $contentHasShortcode = $page->content && preg_match('/\[(formularz|kafelki):[a-z0-9_\-]+\]/i', $page->content);
        @endphp
        <div @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <section class="mx-auto max-w-6xl px-4 py-12">
                @if ($canInlineEdit)
                    <h1 :contenteditable="editMode ? 'true' : 'false'" @blur="if (editMode) saveField('title', $el.innerText.trim())"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                        class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>
                @else
                    <h1 class="mb-6 text-3xl font-bold text-ink">{{ $page->title }}</h1>
                @endif

                @include('partials.page-content-image')

                @if ($canInlineEdit && ! $contentHasShortcode)
                    <div :contenteditable="editMode ? 'true' : 'false'" @blur="if (editMode) saveField('content', $el.innerHTML.trim())"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                        class="prose max-w-none text-ink">@shortcodes($page->content)</div>
                @else
                    <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
                @endif

                @include('partials.page-gallery', ['page' => $page])

                @include('partials.attachments-list', ['attachments' => $page->attachments])
            </section>
        </div>
        @endif
    @endif
@endsection
