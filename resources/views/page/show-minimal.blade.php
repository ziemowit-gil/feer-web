@extends('layouts.minimal')

{{--
    Szablon „Minimalny" — uproszczony układ (layouts.minimal), wąska kolumna treści.
    Płasko: H1, szary pasek z datą aktualizacji, treść; bez ramek i kolorowych wyróżnień.
--}}

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('content')
    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else

    @if ($page->wipIsNotice())
        <div class="px-4 pt-8">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    @php
        $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
        $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
    @endphp
    <div data-page-template="minimal" @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
        @if ($canInlineEdit)
            @include('partials.inline-edit-bar')
        @endif

        <article class="mx-auto max-w-3xl px-4 py-12">
            <header class="mb-8">
                @if ($canInlineEdit)
                    <h1 data-inline-field="title" data-inline-kind="text"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                        class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $page->title }}</h1>
                @else
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $page->title }}</h1>
                @endif
            </header>
            @include('partials.pjm-video', ['url' => $page->pjm_video_url, 'title' => $page->title])

            @include('partials.page-content-image')

            @if ($canInlineEdit && ! $contentHasShortcode)
                <div data-inline-field="content" data-inline-kind="rich"
                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                    class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @elseif ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </article>
    </div>

    @endif
@endsection
