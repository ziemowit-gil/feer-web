@extends('layouts.site')

{{--
    Szablon „Szeroki" — treść na całą szerokość kontenera, bez kolumny bocznej.
    Płasko, w stylu portalu (jak page/show): dział nad tytułem, H1, szary pasek
    z datą aktualizacji i drukowaniem; bez ramek, cieni i gradientów.
--}}

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Działania', 'url' => route('projects.index')] : null,
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
            $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
            $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
            $ppSection = $page->parent
                ? ['label' => $page->parent->title, 'url' => $page->parent->publicUrl()]
                : ($page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)] : null);
        @endphp
        <div data-page-template="wide" @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <article class="mx-auto max-w-6xl px-4 py-10">
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

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-md bg-gray-50 px-4 py-3">
                        <div class="no-print print:hidden">@include('partials.print-button')</div>
                    </div>
                </header>

                @include('partials.page-content-image')

                @if ($canInlineEdit && ! $contentHasShortcode)
                    <div data-inline-field="content" data-inline-kind="rich"
                        :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                        class="prose max-w-none text-ink">@shortcodes($page->content)</div>
                @else
                    <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
                @endif

                @include('partials.page-gallery', ['page' => $page])

                @include('partials.attachments-list', ['attachments' => $page->attachments])
            </article>
        </div>
        @endif
    @endif
@endsection
