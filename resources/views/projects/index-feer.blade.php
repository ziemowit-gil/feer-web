@extends('layouts.site')

@section('title', 'Projekty — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => null],
    ]])
@endsection

{{--
    Lista projektów w układzie FEER: jasny nagłówek, po lewej przyklejona nawigacja po kategoriach (z licznikami),
    po prawej projekty jako lekkie wiersze — bez ramek, kolor projektu jako pasek po lewej. Na telefonie nawigacja
    kategorii jest poziomym paskiem nad listą. Kontrast: tekst ink/muted na bieli (≥ 4,5:1), linki brand-dark.
--}}
@section('content')
    @php $filled = $categories->filter(fn ($c) => $c->publishedProjects->isNotEmpty())->values(); @endphp

    <section>
        <div class="mx-auto max-w-6xl px-4 pb-6 pt-8 md:pb-8 md:pt-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Projekty</h1>
                @include('partials.admin-manage-link', ['route' => 'admin.projekty.index', 'label' => 'Zarządzaj projektami'])
            </div>
            <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            @if ($siteSettings->projects_intro)
                <div class="prose mt-4 max-w-2xl text-lg text-ink">{!! $siteSettings->projects_intro !!}</div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($filled->isEmpty())
            <p class="text-muted">Brak aktualnych projektów.</p>
        @else
            <div class="grid gap-10 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-14">

                <nav aria-label="Kategorie projektów" class="lg:sticky lg:top-6 lg:self-start">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Kategorie</p>
                    <ul class="feer-pills-row flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0" role="list">
                        @foreach ($filled as $cat)
                            <li class="shrink-0">
                                <a href="#kategoria-{{ $cat->id }}"
                                   class="flex min-h-11 items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-2 text-sm font-bold text-ink transition hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:bg-transparent lg:px-3">
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-muted">{{ $cat->publishedProjects->count() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($hasArchive)
                        <a href="{{ route('projects.archive') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">To już zrobiliśmy →</a>
                    @endif
                </nav>

                <div class="min-w-0 space-y-14">
                    @foreach ($filled as $category)
                        <section id="kategoria-{{ $category->id }}" class="scroll-mt-24" aria-labelledby="kat-h-{{ $category->id }}">
                            <h2 id="kat-h-{{ $category->id }}" class="mb-4 text-2xl font-bold text-ink">
                                <a href="{{ route('categories.show', $category) }}" class="group inline-flex items-center gap-2 rounded-sm hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    {{ $category->name }}
                                    <span class="text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                                </a>
                            </h2>

                            <ul class="space-y-1" role="list">
                                @foreach ($category->publishedProjects as $project)
                                    @php
                                        $accent = \App\Support\Color::isValid($project->accent_color ?? null)
                                            ? $project->accent_color
                                            : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : 'var(--color-brand)');
                                    @endphp
                                    <li>
                                        <a href="{{ route('projects.show', $project) }}"
                                           class="group flex items-center gap-4 rounded-md py-4 pl-4 pr-3 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                           style="border-left: 4px solid {{ $accent }}">
                                            @if ($project->image_url)
                                                <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-14 w-20 flex-none rounded-md object-cover sm:h-16 sm:w-24">
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $project->title }}</span>
                                                @if ($teaser = $project->teaser())
                                                    <span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-muted">{{ $teaser }}</span>
                                                @endif
                                            </span>
                                            <span class="flex-none text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
