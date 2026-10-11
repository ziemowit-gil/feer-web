@extends('layouts.site')

{{--
    Szablon „Landing" — duży, jednolity pas brand-dark z tytułem i opisem strony
    (biały tekst ok. 7:1, bez gradientu i przezroczystości), pod nim wąska kolumna treści.
    Bez okruszków; komunikat „w przygotowaniu" nad pasem.
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

    <div data-page-template="landing" @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
        @if ($canInlineEdit)
            @include('partials.inline-edit-bar')
        @endif

        {{-- ===== HERO ===== --}}
        <header class="bg-brand-dark text-white">
            <div class="mx-auto max-w-6xl px-4 py-20 md:py-28">
                @if ($canInlineEdit)
                    <h1 data-inline-field="title" data-inline-kind="text"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-white rounded' : ''"
                        class="max-w-4xl text-4xl font-extrabold leading-tight tracking-tight md:text-6xl">{{ $page->title }}</h1>
                @else
                    <h1 class="max-w-4xl text-4xl font-extrabold leading-tight tracking-tight md:text-6xl">{{ $page->title }}</h1>
                @endif
                @if ($page->meta_description)
                    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white md:text-xl">{{ $page->meta_description }}</p>
                @endif
            </div>
        </header>

        {{-- ===== TREŚĆ ===== --}}
        <article class="mx-auto max-w-4xl px-4 py-16" aria-label="{{ $page->title }}">
            @include('partials.page-content-image')

            @if ($canInlineEdit && ! $contentHasShortcode)
                <div data-inline-field="content" data-inline-kind="rich"
                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                    class="prose prose-lg max-w-none text-ink">@shortcodes($page->content)</div>
            @elseif ($page->content)
                <div class="prose prose-lg max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </article>
    </div>

    @endif
@endsection
