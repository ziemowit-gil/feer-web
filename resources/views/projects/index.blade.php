@extends('layouts.site')

@section('title', 'Działania — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Działania', 'url' => null],
    ]])
@endsection

@section('content')
    <div x-data="{ view: localStorage.getItem('projects-view') || 'grid' }"
        x-init="$watch('view', v => localStorage.setItem('projects-view', v))">

    @php
        $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $anyProjects = $categories->some(fn ($c) => $c->publishedProjects->isNotEmpty());
        $filledCategories = $categories->filter(fn ($c) => $c->publishedProjects->isNotEmpty())->values();
    @endphp

    {{-- ══ NAGŁÓWEK: tytuł, wstęp, przełącznik widoku i skróty do kategorii — płasko, na białym ══ --}}
    <div>
        <div class="mx-auto max-w-6xl px-4 pb-4 pt-10 sm:pt-12">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-sm font-bold uppercase tracking-widest text-brand-dark">Nasze działania</p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">Działania</h1>
                    @if ($siteSettings->projects_intro)
                        <div class="prose mt-4 max-w-2xl text-ink">{!! $siteSettings->projects_intro !!}</div>
                    @endif
                </div>

                <div class="flex gap-1 rounded-md bg-gray-50 p-1" role="group" aria-label="Przełącz widok działań">
                    <button type="button" @click="view = 'grid'"
                        :class="view === 'grid' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-gray-200'"
                        class="flex h-11 w-11 items-center justify-center rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                        aria-label="Widok siatki" :aria-pressed="view === 'grid'">
                        <i class="fa-solid fa-grip text-sm" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="view = 'list'"
                        :class="view === 'list' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-gray-200'"
                        class="flex h-11 w-11 items-center justify-center rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                        aria-label="Widok listy" :aria-pressed="view === 'list'">
                        <i class="fa-solid fa-list text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            @if ($filledCategories->count() > 1)
                {{-- Skróty do kategorii zwykłymi odnośnikami (bez „chipów"), liczba działań w nawiasie. --}}
                <nav aria-label="Przejdź do kategorii" class="mt-8 rounded-md bg-gray-50 px-5 py-4">
                    <ul class="flex flex-wrap gap-x-6 gap-y-2" role="list">
                        @foreach ($filledCategories as $chip)
                            <li>
                                <a href="#kategoria-{{ $chip->id }}" class="inline-flex min-h-9 items-center {{ $link }}">{{ $chip->name }}</a>
                                <span class="text-sm text-muted">({{ $chip->publishedProjects->count() }})</span>
                            </li>
                        @endforeach
                    </ul>
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
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <h2 class="text-2xl font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="shrink-0 text-sm {{ $link }}">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
                            <a href="{{ route('categories.show', $category) }}" class="shrink-0 text-sm {{ $link }}">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <ul class="space-y-2" role="list">
                            @foreach ($category->publishedProjects as $project)
                                <li>
                                    <a href="{{ route('projects.show', $project) }}"
                                        class="group flex items-center gap-4 rounded-md bg-gray-50 px-4 py-3.5 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                        @if ($project->image_url)
                                            <div class="hidden h-14 w-20 flex-none overflow-hidden rounded-md bg-gray-100 md:block">
                                                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}"
                                                    class="h-full w-full object-cover">
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-bold text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $project->title }}</p>
                                            <span class="block text-ink">@include('projects.partials.form-badge', ['project' => $project])</span>
                                            @if ($project->excerpt)
                                                <p class="mt-0.5 line-clamp-1 text-sm text-muted">{{ $project->excerpt }}</p>
                                            @endif
                                        </div>
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
            <div class="mt-4 pt-4">
                <a href="{{ route('projects.archive') }}" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    To już zrobiliśmy: zobacz zrealizowane działania
                </a>
            </div>
        @endif

    </section>
    </div>
@endsection
