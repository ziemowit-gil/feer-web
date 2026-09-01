@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Projekty</p>
        <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Nasze aktualne projekty
        </h1>

        @include('templates.federation.partials.projects-tabs')

        @if ($siteSettings->projects_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @php $anyProjects = $categories->some(fn ($c) => $c->publishedProjects->isNotEmpty()); @endphp

        @forelse ($categories as $category)
            @continue ($category->publishedProjects->isEmpty())
            <div class="mb-12">
                <div class="divide-y divide-gray-100 border-y border-gray-100">
                    @foreach ($category->publishedProjects as $i => $project)
                        @php $accent = $project->accent_color ?: $siteSettings->brandColorN(($i % 4) + 1); @endphp
                        <article class="py-6">
                            <h3 class="text-lg font-bold leading-snug text-ink">{{ $project->title }}</h3>
                            @if ($project->excerpt)
                                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $project->excerpt }}</p>
                            @endif
                            <a href="{{ route('projects.show', $project) }}"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                style="color:{{ $accent }}; --tw-ring-color:{{ $accent }}">
                                Dowiedz się więcej <span class="sr-only">o projekcie: {{ $project->title }}</span>
                                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
        @endforelse

        @unless ($anyProjects)
            <p class="text-muted">Brak aktualnych projektów.</p>
        @endunless
    </section>
@endsection
