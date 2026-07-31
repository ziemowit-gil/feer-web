@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12"
        x-data="{ view: localStorage.getItem('projects-view') || 'grid' }"
        x-init="$watch('view', v => localStorage.setItem('projects-view', v))">

        <div class="mb-6 flex items-center justify-between gap-4">
            <h1 class="{{ $siteSettings->projects_intro ? 'mb-0' : 'mb-0' }} text-3xl font-bold text-ink">Projekty</h1>

            <div class="flex gap-1 rounded-lg border border-gray-200 p-1" role="group" aria-label="Przełącz widok projektów">
                <button type="button" @click="view = 'grid'"
                    :class="view === 'grid' ? 'bg-brand text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-gray-100'"
                    class="flex h-8 w-8 items-center justify-center rounded transition"
                    aria-label="Widok siatki" :aria-pressed="view === 'grid'">
                    <i class="fa-solid fa-grip text-sm" aria-hidden="true"></i>
                </button>
                <button type="button" @click="view = 'list'"
                    :class="view === 'list' ? 'bg-brand text-white shadow-sm' : 'text-muted hover:text-ink hover:bg-gray-100'"
                    class="flex h-8 w-8 items-center justify-center rounded transition"
                    aria-label="Widok listy" :aria-pressed="view === 'list'">
                    <i class="fa-solid fa-list text-sm" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        @if ($siteSettings->projects_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @php $anyProjects = $categories->some(fn ($c) => $c->publishedProjects->isNotEmpty()); @endphp

        {{-- Widok siatki --}}
        <div x-show="view === 'grid'">
            @foreach ($categories as $category)
                @if ($category->publishedProjects->isNotEmpty())
                    <div class="mb-10">
                        <div class="mb-4 flex items-end justify-between gap-4">
                            <h2 class="text-xl font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-brand hover:text-brand-dark">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            @foreach ($category->publishedProjects as $project)
                                @include('partials.project-card', ['project' => $project])
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
                    <div class="mb-8">
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <h2 class="text-lg font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-brand hover:text-brand-dark">
                                Zobacz kategorię →
                            </a>
                        </div>

                        <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200">
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
@endsection
