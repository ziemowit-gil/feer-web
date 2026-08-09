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

    @if ($page->wipIsNotice())
        <div class="px-4 pt-6">
            @include('partials.page-wip-notice', ['message' => $page->wipMessage()])
        </div>
    @endif

    {{-- ===== HERO ===== --}}
    <header class="relative overflow-hidden bg-brand text-white">
        @if (filled($page->content_image))
            <img src="{{ $page->content_image }}"
                 alt="{{ $page->content_image_alt ?: 'Grafika ilustracyjna' }}"
                 class="absolute inset-0 h-full w-full object-cover opacity-35"
                 aria-hidden="true">
        @endif
        <div class="relative mx-auto max-w-6xl px-4 py-14 md:py-20">
            <h1 class="max-w-3xl text-3xl font-bold leading-tight md:text-5xl">{{ $page->title }}</h1>
            @if ($page->meta_description)
                <p class="mt-3 max-w-2xl text-base text-white/85 md:text-lg">{{ $page->meta_description }}</p>
            @endif
        </div>
    </header>

    @php
        $menuSiblings = $page->menuSiblings();
        $hasSidebar = $menuSiblings->isNotEmpty();
        $galleryImages = ($page->show_gallery ?? false)
            ? $page->images->filter(fn ($i) => $i->image_url)->values()
            : collect();
    @endphp

    {{-- ===== TREŚĆ GŁÓWNA + SIDEBAR ===== --}}
    <div class="mx-auto max-w-6xl px-4 py-12">
        <div class="grid gap-10 {{ $hasSidebar ? 'lg:grid-cols-[1fr_280px]' : '' }}">

            {{-- Lewa: główna treść --}}
            <main>
                @if ($page->content)
                    <div class="prose max-w-none text-ink">{!! $page->content !!}</div>
                @endif

                @include('partials.page-gallery', ['page' => $page])
                @include('partials.attachments-list', ['attachments' => $page->attachments])
            </main>

            {{-- Prawa: sidebar z podstronami sekcji --}}
            @if ($hasSidebar)
                <aside class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">
                            <i class="fa-solid fa-sitemap mr-1.5 text-brand" aria-hidden="true"></i>
                            W tej sekcji
                        </p>
                        @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
                    </div>
                </aside>
            @endif

        </div>
    </div>

    {{-- ===== GALERIA (pełna szerokość) ===== --}}
    @if ($galleryImages->isNotEmpty())
        <section class="border-t border-gray-100 bg-gray-50 py-12" aria-label="Galeria zdjęć">
            <div class="mx-auto max-w-6xl px-4">
                <h2 class="mb-6 flex items-center gap-2 text-2xl font-bold text-ink">
                    <i class="fa-solid fa-images text-brand" aria-hidden="true"></i>
                    Galeria zdjęć
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-lightbox>
                    @foreach ($galleryImages as $image)
                        <figure class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <a href="{{ $image->image_url }}" data-lightbox-src="{{ $image->image_url }}" class="block">
                                <img src="{{ $image->image_url }}"
                                     alt="{{ $image->alt }}"
                                     loading="lazy"
                                     class="h-44 w-full object-cover transition hover:opacity-90">
                            </a>
                            @if ($image->caption)
                                <figcaption class="px-3 py-2 text-xs text-muted">{{ $image->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== KONTAKT ===== --}}
    @if ($siteSettings->contact_address || $siteSettings->contact_email || $siteSettings->contact_phone)
        <section class="bg-brand text-white" aria-label="Dane kontaktowe">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <h2 class="mb-6 text-xl font-bold">Kontakt</h2>
                <div class="flex flex-wrap gap-10">
                    @if ($siteSettings->contact_address || $siteSettings->contact_city)
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15" aria-hidden="true">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-white/70">Adres</p>
                                <p class="font-medium">{{ $siteSettings->contact_address }}</p>
                                @if ($siteSettings->contact_city)
                                    <p class="text-white/85">{{ $siteSettings->contact_city }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($siteSettings->contact_email)
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15" aria-hidden="true">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-white/70">E-mail</p>
                                <a href="mailto:{{ $siteSettings->contact_email }}"
                                   class="font-medium hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                    {{ $siteSettings->contact_email }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if ($siteSettings->contact_phone)
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15" aria-hidden="true">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-white/70">Telefon</p>
                                <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                                   class="font-medium hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                    {{ $siteSettings->contact_phone }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @endif {{-- showsPlaceholder --}}
@endsection
