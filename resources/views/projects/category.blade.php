@extends('layouts.site')

@section('title', $category->name . ' — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $category->name, 'url' => null],
    ]])
@endsection

@section('content')
    @if (($siteSettings->site_template ?? 'default') === 'feer')
        <section class="mx-auto max-w-6xl px-4 py-10">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Kategoria</p>
            <h1 class="mt-1 text-2xl font-bold leading-tight text-ink md:text-3xl">{{ $category->name }}</h1>
            <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            @if ($siteSettings->projects_intro)
                <div class="prose mt-5 max-w-2xl text-lg text-ink">{!! $siteSettings->projects_intro !!}</div>
            @endif

            @if ($category->publishedProjects->isNotEmpty())
                <ul class="mt-8 space-y-1" role="list">
                    @foreach ($category->publishedProjects as $project)
                        @php
                            $accent = \App\Support\Color::isValid($project->accent_color ?? null)
                                ? $project->accent_color
                                : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : 'var(--color-brand)');
                        @endphp
                        <li>
                            <a href="{{ route('projects.show', $project) }}" class="feer-card group flex items-center gap-4 rounded-md py-4 pl-4 pr-3 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" style="border-left: 4px solid {{ $accent }}">
                                @if ($project->image_url)
                                    <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-14 w-20 flex-none rounded-md object-cover sm:h-16 sm:w-24">
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $project->title }}</span>
                                    <span class="block">@include('projects.partials.form-badge', ['project' => $project])</span>
                                    @if ($teaser = $project->teaser())
                                        <span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-muted">{{ $teaser }}</span>
                                    @endif
                                </span>
                                <span class="flex-none text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-8 text-muted">Brak aktualnych projektów w tej kategorii.</p>
            @endif

            <p class="mt-10 border-t border-gray-200 pt-6"><a href="{{ route('projects.index') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Wszystkie projekty</a></p>
        </section>
    @else
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
    @endif
@endsection
