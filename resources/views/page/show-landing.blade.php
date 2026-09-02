@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('content')
    @if (! empty($preview))
        {{-- Pasek podglądu jest już w layoucie, tu nie powtarzamy --}}
    @endif

    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else

    @if ($page->wipIsNotice())
        <div class="px-4 pt-8">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    @php
        $canInlineEdit = auth()->check() && auth()->user()->canAccessModule('pages');
        $contentHasShortcode = $page->content && preg_match('/\[(formularz|kafelki):[a-z0-9_\-]+\]/i', $page->content);
    @endphp

    <div @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
        @if ($canInlineEdit)
            @include('partials.inline-edit-bar')
        @endif

        {{-- ===== HERO ===== --}}
        <header class="bg-brand text-white">
            <div class="mx-auto max-w-6xl px-4 py-20 md:py-28">
                @if ($canInlineEdit)
                    <h1 :contenteditable="editMode ? 'true' : 'false'" @blur="if (editMode) saveField('title', $el.innerText.trim())"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-white rounded' : ''"
                        class="text-4xl font-bold leading-tight md:text-6xl">{{ $page->title }}</h1>
                @else
                    <h1 class="text-4xl font-bold leading-tight md:text-6xl">{{ $page->title }}</h1>
                @endif
                @if ($page->meta_description)
                    <p class="mt-4 max-w-2xl text-lg text-white/85">{{ $page->meta_description }}</p>
                @endif
            </div>
        </header>

        {{-- ===== TREŚĆ ===== --}}
        <section class="mx-auto max-w-4xl px-4 py-16">
            @include('partials.page-content-image')

            @if ($canInlineEdit && ! $contentHasShortcode)
                <div :contenteditable="editMode ? 'true' : 'false'" @blur="if (editMode) saveField('content', $el.innerHTML.trim())"
                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                    class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @elseif ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </section>
    </div>

    @endif
@endsection
