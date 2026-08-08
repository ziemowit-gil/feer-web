@extends('layouts.site')

@section('title', 'Mapa strony — ' . $siteSettings->site_name)
@section('meta_description', 'Mapa strony ' . $siteSettings->site_name . ' — wszystkie podstrony w jednym miejscu.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Mapa strony', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12">
        <h1 class="mb-8 text-3xl font-bold text-ink">Mapa strony</h1>

        <div class="grid gap-8 sm:grid-cols-2">
            <div>
                <h2 class="mb-3 text-lg font-bold text-ink">Główne</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-brand hover:text-brand-dark hover:underline">Strona główna</a></li>
                    @if ($projects->isNotEmpty() || \App\Models\SiteSetting::current()->isModuleEnabled('projects'))
                        <li><a href="{{ route('projects.index') }}" class="text-brand hover:text-brand-dark hover:underline">Projekty</a></li>
                    @endif
                    @if ($news->isNotEmpty() || \App\Models\SiteSetting::current()->isModuleEnabled('news'))
                        <li><a href="{{ route('news.index') }}" class="text-brand hover:text-brand-dark hover:underline">Aktualności</a></li>
                    @endif
                    @if ($materialsEnabled)
                        <li><a href="{{ route('materials.index') }}" class="text-brand hover:text-brand-dark hover:underline">Materiały edukacyjne</a></li>
                    @endif
                    @if ($supportEnabled)
                        <li><a href="{{ route('support.show') }}" class="text-brand hover:text-brand-dark hover:underline">Wesprzyj nas</a></li>
                    @endif
                    <li><a href="{{ route('contact.show') }}" class="text-brand hover:text-brand-dark hover:underline">Kontakt</a></li>
                    <li><a href="{{ route('newsletter.show') }}" class="text-brand hover:text-brand-dark hover:underline">Newsletter</a></li>
                </ul>
            </div>

            @if ($pages->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-bold text-ink">Podstrony</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($pages as $page)
                            <li><a href="{{ $page->publicUrl() }}" class="text-brand hover:text-brand-dark hover:underline">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($projects->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-bold text-ink">Projekty</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($projects as $project)
                            <li><a href="{{ route('projects.show', $project) }}" class="text-brand hover:text-brand-dark hover:underline">{{ $project->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($news->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-bold text-ink">Aktualności</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($news as $item)
                            <li><a href="{{ route('news.show', $item) }}" class="text-brand hover:text-brand-dark hover:underline">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <p class="mt-10 text-xs text-muted">
            Wersja dla wyszukiwarek: <a href="{{ route('sitemap') }}" class="text-brand hover:underline">sitemap.xml</a> (generowana automatycznie).
        </p>
    </section>
@endsection
