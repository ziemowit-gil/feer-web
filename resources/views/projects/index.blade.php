@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

@section('content')
    <div x-data="{ view: localStorage.getItem('projects-view') || 'grid' }"
        x-init="$watch('view', v => localStorage.setItem('projects-view', v))">

    @php
        $anyProjects = $categories->some(fn ($c) => $c->publishedProjects->isNotEmpty());
        $filledCategories = $categories->filter(fn ($c) => $c->publishedProjects->isNotEmpty())->values();
    @endphp

    {{-- ══ HERO: tytuł, wstęp, przełącznik widoku i skróty do kategorii ══ --}}
    <div class="relative overflow-hidden border-b border-gray-100"
        style="background: linear-gradient(135deg, color-mix(in srgb, var(--color-brand) 10%, #fff) 0%, #fff 70%)">
        <span class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full" aria-hidden="true"
            style="background: color-mix(in srgb, var(--color-brand) 8%, transparent)"></span>
        <div class="relative mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand">Nasze działania</p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl">Projekty</h1>
                    @if ($siteSettings->projects_intro)
                        <div class="prose mt-4 max-w-2xl text-ink/80">{!! $siteSettings->projects_intro !!}</div>
                    @endif
                </div>

                <div class="flex gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm" role="group" aria-label="Przełącz widok projektów">
                    <button type="button" @click="view = 'grid'"
                        :class="view === 'grid' ? 'bg-brand text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-gray-100'"
                        class="flex h-9 w-9 items-center justify-center rounded-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        aria-label="Widok siatki" :aria-pressed="view === 'grid'">
                        <i class="fa-solid fa-grip text-sm" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="view = 'list'"
                        :class="view === 'list' ? 'bg-brand text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-gray-100'"
                        class="flex h-9 w-9 items-center justify-center rounded-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        aria-label="Widok listy" :aria-pressed="view === 'list'">
                        <i class="fa-solid fa-list text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            @if ($filledCategories->count() > 1)
                <nav aria-label="Przejdź do kategorii" class="mt-8 flex flex-wrap gap-2">
                    @foreach ($filledCategories as $chip)
                        <a href="#kategoria-{{ $chip->id }}"
                            class="rounded-full border border-brand/30 bg-white px-4 py-1.5 text-sm font-bold text-brand transition hover:bg-brand hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            {{ $chip->name }}
                            <span class="ml-1 font-medium opacity-70">{{ $chip->publishedProjects->count() }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>

    <section class="mx-auto max-w-6xl px-4 py-12">


        {{-- Widok siatki --}}
        <div x-show="view === 'grid'">
            @foreach ($categories as $category)
                @if ($category->publishedProjects->isNotEmpty())
                    <div class="mb-12 scroll-mt-24" id="kategoria-{{ $category->id }}">
                        <div class="mb-5 flex items-end justify-between gap-4 border-b border-gray-200 pb-3">
                            <h2 class="text-2xl font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-brand hover:text-brand-dark">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($category->publishedProjects as $project)
                                @include('projects._tile', ['project' => $project, 'categoryName' => $category->name])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @unless ($anyProjects)
                <p class="text-muted">Brak aktualnych projektów.</p>
            @endunless
        </div>

        {{-- Widok listy --}}
        <div x-show="view === 'list'" x-cloak>
            @foreach ($categories as $category)
                @if ($category->publishedProjects->isNotEmpty())
                    <div class="mb-8 scroll-mt-24">
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <h2 class="text-lg font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-brand hover:text-brand-dark">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <ul class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            @foreach ($category->publishedProjects as $project)
                                @php
                                    $accentHex = $project->accent_color
                                        ?: ($project->audience === 'ngo'
                                            ? $siteSettings->audienceColor('ngo')
                                            : null);
                                @endphp
                                <li>
                                    <a href="{{ route('projects.show', $project) }}"
                                        class="group flex items-center gap-4 px-4 py-3.5 transition hover:bg-brand-light/30">

                                        @if ($accentHex)
                                            <span class="hidden flex-none self-stretch w-1 rounded-full sm:block"
                                                style="background-color: {{ $accentHex }};"></span>
                                        @else
                                            <span class="hidden flex-none self-stretch w-1 rounded-full bg-brand/20 sm:block"></span>
                                        @endif

                                        @if ($project->image_url)
                                            <div class="hidden h-14 w-20 flex-none overflow-hidden rounded-md bg-gray-100 md:block">
                                                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}"
                                                    class="h-full w-full object-cover">
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-ink group-hover:text-brand truncate">{{ $project->title }}</p>
                                            @if ($project->excerpt)
                                                <p class="mt-0.5 line-clamp-1 text-sm text-muted">{{ $project->excerpt }}</p>
                                            @endif
                                        </div>

                                        <i class="fa-solid fa-chevron-right flex-none text-xs text-gray-300 group-hover:text-brand transition" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach

            @unless ($anyProjects)
                <p class="text-muted">Brak aktualnych projektów.</p>
            @endunless
        </div>

        @if ($hasArchive)
            <div class="mt-4 border-t border-gray-200 pt-8">
                <a href="{{ route('projects.archive') }}" class="inline-flex items-center gap-2 rounded border border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> To już zrobiliśmy — zobacz zrealizowane projekty
                </a>
            </div>
        @endif

    </section>
    </div>
@endsection
