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
    <div class="border-b border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-14">
            <p class="text-xs font-bold uppercase tracking-widest text-brand">Archiwum</p>
            <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">To już zrobiliśmy</h1>
            @if ($archiveFilter ?? null)
                <p class="mt-3 max-w-2xl text-ink/80">Projekty {{ $archiveFilter }}.</p>
                <p class="mt-3 text-sm"><a href="{{ route('projects.archive') }}" class="font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Pokaż wszystkie zrealizowane projekty</a></p>
            @else
                <p class="mt-3 max-w-2xl text-ink/80">Projekty, które już zrealizowaliśmy.</p>
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
            <p class="text-muted">{{ ($archiveFilter ?? null) ? 'Brak projektów w tym okresie.' : 'Nie mamy jeszcze zrealizowanych projektów do pokazania.' }}</p>
        @endif

        <p class="mt-10 border-t border-gray-200 pt-6">
            <a href="{{ route('projects.index') }}" class="text-sm font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Wróć do projektów</a>
        </p>
    </section>
@endsection
