@extends('layouts.site')

@section('title', $category->name . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Projekty w kategorii ' . $category->name . ' — ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $category->name, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Kategoria</p>
        <h1 class="{{ $siteSettings->projects_intro ? 'mb-4' : 'mb-10' }} text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            {{ $category->name }}
        </h1>

        @if ($siteSettings->projects_intro)
            <div class="prose mb-10 max-w-2xl text-muted">{!! $siteSettings->projects_intro !!}</div>
        @endif

        @if ($category->publishedProjects->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($category->publishedProjects as $i => $project)
                    @php $accent = $project->accent_color ?: $siteSettings->brandColorN(($i % 4) + 1); @endphp
                    <article class="flex flex-col gap-3 rounded-lg border border-gray-100 p-6">
                        <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded text-sm font-extrabold" style="background:{{ $accent }}1a; color:{{ $accent }}" aria-hidden="true">
                            {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h2 class="text-lg font-bold leading-snug text-ink">{{ $project->title }}</h2>
                        @if ($project->excerpt)
                            <p class="flex-1 text-sm leading-relaxed text-muted">{{ $project->excerpt }}</p>
                        @endif
                        <a href="{{ route('projects.show', $project) }}"
                            class="mt-2 inline-flex w-fit items-center gap-1.5 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            style="color:{{ $accent }}; --tw-ring-color:{{ $accent }}">
                            Dowiedz się więcej <span class="sr-only">o projekcie: {{ $project->title }}</span>
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <p class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-muted">
                Brak aktualnych projektów w tej kategorii.
            </p>
        @endif

        <div class="mt-10">
            <a href="{{ route('projects.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                Wszystkie projekty
            </a>
        </div>
    </section>

    @include('templates.federation.partials.home.cta-banner')
@endsection
