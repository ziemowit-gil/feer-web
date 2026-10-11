@extends('layouts.site')

@section('title', 'To już zrobiliśmy — ' . $siteSettings->site_name)
@section('meta_description', 'Działania, które już zrealizowaliśmy.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Działania', 'url' => route('projects.index')],
        ['label' => 'To już zrobiliśmy', 'url' => null],
    ]])
@endsection

@section('content')
    <div>
        <div class="mx-auto max-w-6xl px-4 pb-4 pt-10 sm:pt-12">
            <p class="text-sm font-bold uppercase tracking-widest text-brand-dark">Archiwum</p>
            <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">To już zrobiliśmy</h1>
            @if ($archiveFilter ?? null)
                <p class="mt-3 max-w-2xl text-ink">Działania {{ $archiveFilter }}.</p>
                <p class="mt-3 text-sm"><a href="{{ route('projects.archive') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Pokaż wszystkie zrealizowane działania</a></p>
            @else
                <p class="mt-3 max-w-2xl text-ink">Działania, które już zrealizowaliśmy.</p>
            @endif
        </div>
    </div>

    <section class="mx-auto max-w-6xl px-4 py-12">
        @if ($projects->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    @include('projects._tile', ['project' => $project])
                @endforeach
            </div>
        @else
            <p class="text-muted">{{ ($archiveFilter ?? null) ? 'Brak działań w tym okresie.' : 'Nie mamy jeszcze zrealizowanych działań do pokazania.' }}</p>
        @endif

        <p class="mt-10">
            <a href="{{ route('projects.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">← Wróć do działań</a>
        </p>
    </section>
@endsection
