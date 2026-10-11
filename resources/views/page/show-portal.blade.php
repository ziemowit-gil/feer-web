@extends('layouts.site')

{{--
    Szablon „Portal" — pas tytułowy brand-dark (zdjęcie strony obok tytułu, nie jako
    półprzezroczyste tło), treść + przyklejona kolumna boczna (spis treści, menu działu),
    galeria na pełną szerokość i pas kontaktowy. Płasko: tła zamiast ramek, bez przezroczystości.
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

    @if ($page->wipIsNotice())
        <div class="px-4 pt-6">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    @php
        $menuSiblings = $page->menuSiblings();
        $portalTabs = $menuSiblings->isNotEmpty() && $page->sideNavStyle() === 'tabs';
        [$portalContentHtml, $portalToc] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content));
        $hasSidebar = ($menuSiblings->isNotEmpty() && ! $portalTabs) || $portalToc !== [];
        $galleryImages = ($page->show_gallery ?? false)
            ? $page->images->filter(fn ($i) => $i->image_url)->values()
            : collect();
        $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('pages');
        $contentHasShortcode = \App\Support\ShortcodeParser::has($page->content);
        $heroImage = filled($page->content_image);
        $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <div data-page-template="portal" @if ($canInlineEdit) x-data="inlineContentEditor('page', {{ $page->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
        @if ($canInlineEdit)
            @include('partials.inline-edit-bar')
        @endif

        {{-- ===== HERO: jednolity pas brand-dark (biały tekst ok. 7:1); zdjęcie obok, nie pod tekstem ===== --}}
        <header class="bg-brand-dark text-white">
            <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-12 md:py-16 {{ $heroImage ? 'md:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' : '' }}">
                <div class="min-w-0">
                    @if ($canInlineEdit)
                        <h1 data-inline-field="title" data-inline-kind="text"
                            :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-white rounded' : ''"
                            class="max-w-3xl text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
                    @else
                        <h1 class="max-w-3xl text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ $page->title }}</h1>
                    @endif
                    @if ($page->meta_description)
                        <p class="mt-4 max-w-2xl text-base text-white md:text-lg">{{ $page->meta_description }}</p>
                    @endif
                </div>
                @if ($heroImage)
                    <img src="{{ $page->content_image }}"
                         alt="{{ $page->content_image_alt ?: '' }}"
                         class="aspect-[4/3] w-full rounded-md object-cover">
                @endif
            </div>
        </header>

        {{-- ===== TREŚĆ GŁÓWNA + KOLUMNA BOCZNA ===== --}}
        <div class="mx-auto max-w-6xl px-4 py-12">
            @if ($portalTabs)
                @include('partials.page-tabs-nav', ['menuSiblings' => $menuSiblings])
            @endif
            <div class="grid gap-10 {{ $hasSidebar ? 'lg:grid-cols-[minmax(0,1fr)_18rem]' : '' }}">

                {{-- Lewa: główna treść --}}
                <article class="min-w-0" aria-label="{{ $page->title }}">
                    @if ($portalToc)
                        @include('partials.page-toc', ['toc' => $portalToc, 'variant' => 'mobile'])
                    @endif

                    @if ($canInlineEdit && ! $contentHasShortcode)
                        <div data-inline-field="content" data-inline-kind="rich"
                            :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                            class="prose max-w-none text-ink">{!! $portalContentHtml !!}</div>
                    @elseif ($page->content)
                        <div class="prose max-w-none text-ink">{!! $portalContentHtml !!}</div>
                    @endif

                    @include('partials.page-gallery', ['page' => $page])
                    @include('partials.attachments-list', ['attachments' => $page->attachments])
                    @include('partials.page-section-nav', ['page' => $page])
                </article>

                {{-- Prawa: spis treści + podstrony działu (płaskie panele na tle, bez ramek) --}}
                @if ($hasSidebar)
                    <aside aria-label="Kolumna boczna" class="space-y-6 print:hidden lg:sticky lg:top-24 lg:self-start">
                        @if ($portalToc)
                            @include('partials.page-toc', ['toc' => $portalToc, 'variant' => 'desktop'])
                        @endif
                        @if ($menuSiblings->isNotEmpty() && ! $portalTabs)
                            @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                        @endif
                    </aside>
                @endif

            </div>
        </div>
    </div>

    {{-- ===== GALERIA (pełna szerokość) ===== --}}
    @if ($galleryImages->isNotEmpty())
        <section class="bg-gray-50 py-12" aria-labelledby="portal-gallery-heading">
            <div class="mx-auto max-w-6xl px-4">
                <h2 id="portal-gallery-heading" class="mb-6 flex items-center gap-2 text-2xl font-bold text-ink">
                    <i class="fa-solid fa-images text-brand-dark" aria-hidden="true"></i>
                    Galeria zdjęć
                </h2>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-lightbox role="list">
                    @foreach ($galleryImages as $image)
                        <li>
                            <figure class="h-full overflow-hidden rounded-md bg-white">
                                <a href="{{ $image->image_url }}" data-lightbox-src="{{ $image->image_url }}" class="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    <img src="{{ $image->image_url }}"
                                         alt="{{ $image->alt }}"
                                         loading="lazy"
                                         class="h-44 w-full object-cover">
                                </a>
                                @if ($image->caption)
                                    <figcaption class="px-3 py-2 text-sm text-muted">{{ $image->caption }}</figcaption>
                                @endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===== KONTAKT: płaski pas na jasnym tle marki ===== --}}
    @if ($siteSettings->contact_address || $siteSettings->contact_email || $siteSettings->contact_phone)
        <section class="bg-brand-light" aria-labelledby="portal-contact-heading">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <h2 id="portal-contact-heading" class="mb-6 text-xl font-bold text-ink">Kontakt</h2>
                <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($siteSettings->contact_address || $siteSettings->contact_city)
                        <div>
                            <dt class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted"><i class="fa-solid fa-location-dot w-5 text-center text-brand-dark" aria-hidden="true"></i> Adres</dt>
                            <dd class="mt-1 pl-7 font-medium text-ink">
                                {{ $siteSettings->contact_address }}
                                @if ($siteSettings->contact_city)
                                    <span class="block">{{ $siteSettings->contact_city }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if ($siteSettings->contact_email)
                        <div>
                            <dt class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted"><i class="fa-solid fa-envelope w-5 text-center text-brand-dark" aria-hidden="true"></i> E-mail</dt>
                            <dd class="mt-1 pl-7"><a href="mailto:{{ $siteSettings->contact_email }}" class="{{ $link }}">{{ $siteSettings->contact_email }}</a></dd>
                        </div>
                    @endif

                    @if ($siteSettings->contact_phone)
                        <div>
                            <dt class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted"><i class="fa-solid fa-phone w-5 text-center text-brand-dark" aria-hidden="true"></i> Telefon</dt>
                            <dd class="mt-1 pl-7"><a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}" class="{{ $link }}">{{ $siteSettings->contact_phone }}</a></dd>
                        </div>
                    @endif
                </dl>
            </div>
        </section>
    @endif

    @endif {{-- showsPlaceholder --}}
@endsection
