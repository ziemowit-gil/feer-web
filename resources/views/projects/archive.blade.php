@extends('layouts.site')

@section('title', 'To już zrobiliśmy — ' . $siteSettings->site_name)
@section('meta_description', 'Projekty, które już zrealizowaliśmy.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => 'To już zrobiliśmy', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12">
        <a href="{{ route('projects.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do projektów
        </a>
        <h1 class="mb-2 text-3xl font-bold text-ink">To już zrobiliśmy</h1>
        @if ($archiveFilter ?? null)
            <p class="mb-2 max-w-2xl text-muted">Projekty {{ $archiveFilter }}.</p>
            <p class="mb-8 text-sm"><a href="{{ route('projects.archive') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-md font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i> Pokaż wszystkie zrealizowane projekty</a></p>
        @else
            <p class="mb-8 max-w-2xl text-muted">Projekty, które już zrealizowaliśmy.</p>
        @endif

        @if ($projects->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($projects as $project)
                    @include('partials.project-card', ['project' => $project])
                @endforeach
            </div>
        @else
            <p class="text-muted">{{ ($archiveFilter ?? null) ? 'Brak projektów w tym okresie.' : 'Nie mamy jeszcze zrealizowanych projektów do pokazania.' }}</p>
        @endif
    </section>
@endsection
