@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

{{--
    Lista projektów w układzie kafelkowym (opcja „Nawigacja kafelkowa" w Ustawienia → Treści): projekty jako duże,
    kolorowe kafelki pogrupowane w kategorie. Kolor kafelka: własny kolor akcentu projektu albo kolejne kolory marki;
    kolor tekstu dobiera Color::button (kontrast ≥ 4,5:1). Działa w każdym szablonie.
--}}
@section('content')
    @php
        $filled = $categories->filter(fn ($c) => $c->publishedProjects->isNotEmpty())->values();
        $fallback = ['#1a56a4', '#166534', '#7e22ce', '#c2410c'];
        $n = 0;
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Projekty</h1>
            @if ($siteSettings->projects_intro)
                <div class="prose mt-4 max-w-2xl text-lg text-ink">{!! $siteSettings->projects_intro !!}</div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($filled->isEmpty())
            <p class="text-muted">Brak aktualnych projektów.</p>
        @else
            <div class="space-y-14">
                @foreach ($filled as $category)
                    <section aria-labelledby="kat-t-{{ $category->id }}">
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <h2 id="kat-t-{{ $category->id }}" class="text-2xl font-bold text-ink">{{ $category->name }}</h2>
                            <a href="{{ route('categories.show', $category) }}" class="shrink-0 text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Zobacz kategorię →</a>
                        </div>
                        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
                            @foreach ($category->publishedProjects as $project)
                                @php
                                    $base = \App\Support\Color::isValid($project->accent_color ?? null) ? $project->accent_color : $siteSettings->brandColorN(($n % 4) + 1);
                                    if (! \App\Support\Color::isValid($base)) {
                                        $base = $fallback[$n % 4];
                                    }
                                    $n++;
                                    $pal = \App\Support\Color::button($base);
                                @endphp
                                <li>
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="feer-card group flex h-full min-h-40 flex-col justify-between rounded-md p-6 transition hover:-translate-y-0.5 hover:opacity-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2"
                                       style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}">
                                        <span class="text-xl font-bold leading-snug">{{ $project->title }}</span>
                                        <span class="mt-4 flex items-end justify-between gap-3">
                                            <span class="text-sm leading-snug">{{ \Illuminate\Support\Str::limit((string) $project->excerpt, 90) }}</span>
                                            <span class="flex-none text-2xl transition group-hover:translate-x-1" aria-hidden="true">→</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif

        @if ($hasArchive)
            <p class="mt-14"><a href="{{ route('projects.archive') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">To już zrobiliśmy — zrealizowane projekty →</a></p>
        @endif
    </div>
@endsection
