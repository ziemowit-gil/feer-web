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
            $showSideNav = ($page->show_side_nav ?? true) && $menuSiblings->isNotEmpty();
            $canInlineEdit = auth()->check() && auth()->user()->canAccessModule('pages');
            // Treść z shortcode'em (np. osadzony formularz) nie może być edytowana "na żywo" —
            // contenteditable widzi tylko wyrenderowany HTML, zapisanie go z powrotem
            // zgubiłoby oryginalny zapis [formularz:slug]/[kafelki:slug].
            $contentHasShortcode = $page->content && preg_match('/\[(formularz|kafelki):[a-z0-9_\-]+\]/i', $page->content);
        @endphp

        <div @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
            @if ($canInlineEdit)
                @include('partials.inline-edit-bar')
            @endif

            <section class="mx-auto max-w-5xl px-4 py-12">
                <div class="grid gap-10 {{ $showSideNav ? 'md:grid-cols-[1fr_220px]' : '' }}">
                    <div>
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
                    </div>

                    @if ($showSideNav)
                        @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                    @endif
                </div>
            </section>
        </div>
        @endif
    @endif
@endsection
