@extends('layouts.site')

{{--
    Szablon „Z nagłówkiem" — pełnej szerokości, jednolity pas w kolorze brand-dark
    z tytułem (biały tekst ok. 7:1, bez gradientu i przezroczystości), pod nim treść.
    Opcjonalnie nazwa działu nad tytułem i opis strony pod nim.
--}}

@section('title', ($page->meta_title ?: $page->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $page->meta_description ?: meta_excerpt($page->content))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_filter([
        $page->project ? ['label' => 'Działania', 'url' => route('projects.index')] : null,
        $page->project && $page->project->category ? ['label' => $page->project->category->name, 'url' => route('categories.show', $page->project->category)] : null,
        $page->project ? ['label' => $page->project->title, 'url' => route('projects.show', $page->project)]
            : ($page->parent ? ['label' => $page->parent->title, 'url' => route('page.show', $page->parent)] : null),
        ...($page->parent || $page->project ? [] : $page->menuTrail()),
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
        <div data-page-template="hero" @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <header class="bg-brand-dark text-white">
                <div class="mx-auto max-w-5xl px-4 py-12 md:py-16">
                    @if ($ppSection)
                        <p class="mb-3 text-sm font-bold uppercase tracking-widest">
                            <a href="{{ $ppSection['url'] }}" class="text-white underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{{ $ppSection['label'] }}</a>
                        </p>
                    @endif
                    @if ($canInlineEdit)
                        <h1 data-inline-field="title" data-inline-kind="text"
                            :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-white rounded' : ''"
                            class="text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
                    @else
                        <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
                    @endif
                    @if ($page->meta_description)
                        <p class="mt-4 max-w-2xl text-lg text-white">{{ $page->meta_description }}</p>
                    @endif
                </div>
            </header>

            <article class="mx-auto max-w-5xl px-4 py-12" aria-label="{{ $page->title }}">
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
