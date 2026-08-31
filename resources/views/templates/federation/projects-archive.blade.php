@extends('layouts.site')

@section('title', 'Projekty zrealizowane — ' . $siteSettings->site_name)
@section('meta_description', 'Projekty, które już zrealizowaliśmy.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty zrealizowane', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Projekty</p>
        <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Projekty zrealizowane
        </h1>

        @include('templates.federation.partials.projects-tabs')

        <p class="mb-10 max-w-2xl text-base leading-relaxed text-muted">
            Poznaj projekty, które {{ $siteSettings->site_name }} zrealizowało na przestrzeni lat na rzecz
            organizacji pozarządowych i mieszkańców Krakowa.
        </p>

        @if ($projects->isNotEmpty())
            <div class="divide-y divide-gray-100 border-y border-gray-100">
                @foreach ($projects as $project)
                    <article class="py-8">
                        @if ($project->category)
                            <p class="mb-2 text-xs font-extrabold uppercase tracking-widest text-muted">{{ $project->category->name }}</p>
                        @endif
                        <h2 class="text-xl font-bold leading-snug text-ink">{{ $project->title }}</h2>
                        @if ($project->excerpt)
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">{{ $project->excerpt }}</p>
                        @endif
                        <a href="{{ route('projects.show', $project) }}"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-brand transition hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            Dowiedz się więcej <span class="sr-only">o projekcie: {{ $project->title }}</span>
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-muted">Nie mamy jeszcze zrealizowanych projektów do pokazania.</p>
        @endif
    </section>

    @include('templates.federation.partials.home.cta-banner')
@endsection
