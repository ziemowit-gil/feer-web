@extends('layouts.minimal')

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags(str_replace('<', ' <', $page->content))), 160))

@section('content')
    @if ($page->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $page])
    @else

    @if ($page->wipIsNotice())
        <div class="px-4 pt-8">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    <div class="mx-auto max-w-4xl px-4 py-12">
        <h1 class="mb-8 text-3xl font-bold text-ink">{{ $page->title }}</h1>

        @include('partials.page-content-image')

        @if ($page->content)
            <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
        @endif

        @include('partials.page-gallery', ['page' => $page])
        @include('partials.attachments-list', ['attachments' => $page->attachments])
    </div>

    @endif
@endsection
