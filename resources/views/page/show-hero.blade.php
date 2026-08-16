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
        <header class="bg-brand text-white">
            <div class="mx-auto max-w-5xl px-4 py-10">
                <h1 class="text-3xl font-bold leading-tight md:text-4xl">{{ $page->title }}</h1>
            </div>
        </header>

        <section class="mx-auto max-w-5xl px-4 py-12">
            @include('partials.page-content-image')

            <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>

            @include('partials.page-gallery', ['page' => $page])

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </section>
        @endif
    @endif
@endsection
