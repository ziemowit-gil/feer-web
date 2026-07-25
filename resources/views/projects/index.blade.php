@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12">
        <h1 class="{{ $siteSettings->projects_intro ? 'mb-4' : 'mb-8' }} text-3xl font-bold text-ink">Projekty</h1>

        @if ($siteSettings->projects_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @foreach ($categories as $category)
            @if ($category->publishedProjects->isNotEmpty())
                <div class="mb-10">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <h2 class="text-xl font-bold text-ink">{{ $category->name }}</h2>
                        <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-brand hover:text-brand-dark">Zobacz kategorię →</a>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        @foreach ($category->publishedProjects as $project)
                            @include('partials.project-card', ['project' => $project])
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if ($categories->every(fn ($category) => $category->publishedProjects->isEmpty()))
            <p class="text-muted">Brak aktualnych projektów.</p>
        @endif

        @if ($hasArchive)
            <div class="mt-4 border-t border-gray-200 pt-8">
                <a href="{{ route('projects.archive') }}" class="inline-flex items-center gap-2 rounded border border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> To już zrobiliśmy — zobacz zrealizowane projekty
                </a>
            </div>
        @endif
    </section>
@endsection
