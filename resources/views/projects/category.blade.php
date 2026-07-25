@extends('layouts.site')

@section('title', $category->name . ' — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $category->name, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12">
        <p class="mb-2 text-sm font-bold uppercase tracking-wide text-brand">Kategoria</p>
        <h1 class="{{ $siteSettings->projects_intro ? 'mb-4' : 'mb-8' }} text-3xl font-bold text-ink">{{ $category->name }}</h1>

        @if ($siteSettings->projects_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @if ($category->publishedProjects->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($category->publishedProjects as $project)
                    @include('partials.project-card', ['project' => $project])
                @endforeach
            </div>
        @else
            <p class="text-muted">Brak aktualnych projektów w tej kategorii.</p>
        @endif
    </section>
@endsection
