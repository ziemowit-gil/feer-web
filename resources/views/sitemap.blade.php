@extends('layouts.site')

@section('title', 'Mapa strony — ' . $siteSettings->site_name)
@section('meta_description', 'Mapa strony ' . $siteSettings->site_name . ' — wszystkie działy i podstrony w jednym miejscu.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Mapa strony', 'url' => null],
    ]])
@endsection

@php
    $link = 'text-ink underline decoration-gray-300 underline-offset-4 transition hover:text-brand-dark hover:decoration-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
    $roots   = $tree->filter(fn ($n) => $n['children']->isNotEmpty());
    $singles = $tree->filter(fn ($n) => $n['children']->isEmpty());
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10" data-sitemap>
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-ink md:text-4xl">Mapa strony</h1>
            <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">Wszystkie działy i strony serwisu w jednym miejscu.</p>
        </header>

        {{-- Filtr (działa bez przeładowania; bez JavaScriptu mapa jest po prostu pełna). --}}
        <div class="mb-10 max-w-xl" data-sm-filter hidden>
            <label for="sm-q" class="mb-1 block text-sm font-bold text-ink">Szukaj na mapie</label>
            <div class="flex items-center overflow-hidden rounded-md border border-solid border-gray-300 bg-white focus-within:border-brand focus-within:ring-2 focus-within:ring-brand">
                <i class="fa-solid fa-magnifying-glass px-3 text-muted" aria-hidden="true"></i>
                <input id="sm-q" type="search" autocomplete="off" placeholder="np. dostępność, kontakt, szkolenia"
                       class="min-h-11 w-full border-0 bg-transparent pr-3 text-base text-ink placeholder:text-gray-600 focus:outline-none focus:ring-0">
            </div>
            <p class="mt-2 text-sm text-muted" data-sm-status aria-live="polite"></p>
        </div>

        {{-- Najważniejsze działy --}}
        <section class="mb-12" aria-labelledby="sm-sections" data-sm-group>
            <h2 id="sm-sections" class="text-2xl font-bold text-ink">Najważniejsze działy</h2>
            <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($sections as [$label, $url, $icon, $desc])
                    <li data-sm-item>
                        <a href="{{ $url }}" class="group flex h-full items-center gap-4 rounded-md bg-gray-50 p-4 transition hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-md bg-white text-lg text-brand-dark transition group-hover:bg-brand group-hover:text-white" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                            <span class="min-w-0">
                                <span class="block font-bold text-ink group-hover:text-brand-dark">{{ $label }}</span>
                                <span class="block text-sm text-muted">{{ $desc }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Strony serwisu: drzewo rodzic → podstrony --}}
        @if ($tree->isNotEmpty())
            <section class="mb-12" aria-labelledby="sm-pages" data-sm-group>
                <h2 id="sm-pages" class="text-2xl font-bold text-ink">Strony serwisu</h2>
                <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                <div class="gap-6 md:columns-2 lg:columns-3">
                    @foreach ($roots as $node)
                        <div class="mb-6 break-inside-avoid rounded-md border border-solid border-gray-200 bg-white p-5" data-sm-group>
                            <ul role="list">
                                <li data-sm-item>
                                    <a href="{{ $node['page']->publicUrl() }}" class="text-lg font-bold {{ $link }}">{{ $node['page']->title }}</a>
                                    @include('partials.sitemap-tree', ['nodes' => $node['children']])
                                </li>
                            </ul>
                        </div>
                    @endforeach
                    @if ($singles->isNotEmpty())
                        <div class="mb-6 break-inside-avoid rounded-md border border-solid border-gray-200 bg-white p-5" data-sm-group>
                            <h3 class="text-lg font-bold text-ink">{{ $roots->isNotEmpty() ? 'Pozostałe strony' : 'Strony' }}</h3>
                            <ul class="mt-2 space-y-1.5" role="list">
                                @foreach ($singles as $node)
                                    <li data-sm-item><a href="{{ $node['page']->publicUrl() }}" class="{{ $link }}">{{ $node['page']->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <div class="grid gap-10 lg:grid-cols-2">
            @if ($projects->isNotEmpty())
                <section aria-labelledby="sm-projects" data-sm-group>
                    <h2 id="sm-projects" class="text-2xl font-bold text-ink">Działania</h2>
                    <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                    <ul class="space-y-1.5" role="list">
                        @foreach ($projects as $project)
                            <li data-sm-item><a href="{{ route('projects.show', $project) }}" class="{{ $link }}">{{ $project->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($news->isNotEmpty())
                <section aria-labelledby="sm-news" data-sm-group>
                    <h2 id="sm-news" class="text-2xl font-bold text-ink">Ostatnie aktualności</h2>
                    <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                    <ul class="space-y-2" role="list">
                        @foreach ($news as $item)
                            <li data-sm-item>
                                <a href="{{ route('news.show', $item) }}" class="{{ $link }}">{{ $item->title }}</a>
                                @if ($item->published_at)
                                    <time datetime="{{ $item->published_at->toDateString() }}" class="ml-1 text-sm text-muted">· {{ $item->published_at->translatedFormat('j.m.Y') }}</time>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($newsUrl)
                        <p class="mt-4"><a href="{{ $newsUrl }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">Wszystkie aktualności</a></p>
                    @endif
                </section>
            @endif
        </div>

        @if ($people->isNotEmpty())
            <section class="mt-12" aria-labelledby="sm-people" data-sm-group>
                <h2 id="sm-people" class="text-2xl font-bold text-ink">Osoby</h2>
                <span class="mb-6 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                <ul class="flex flex-wrap gap-2" role="list">
                    @foreach ($people as $person)
                        <li data-sm-item>
                            <a href="{{ $person->publicUrl() }}" class="inline-flex min-h-10 items-center gap-2 rounded-full bg-gray-50 px-4 text-ink transition hover:bg-brand-light hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                <i class="fa-solid fa-user text-xs text-muted" aria-hidden="true"></i>{{ $person->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="mt-12 border-t border-solid border-gray-200 pt-6 text-sm text-muted" data-sm-keep>
            Wersja dla wyszukiwarek: <a href="{{ route('sitemap') }}" class="text-brand-dark underline underline-offset-4">sitemap.xml</a> (generowana automatycznie).
        </p>
        <p class="mt-6 hidden text-lg text-ink" data-sm-empty>Nic nie pasuje do wpisanego tekstu. Spróbuj innego słowa albo <a href="{{ route('search') }}" class="font-bold text-brand-dark underline underline-offset-4">użyj wyszukiwarki serwisu</a>.</p>
    </div>

    <script>
        (function () {
            const root = document.querySelector('[data-sitemap]');
            const box = root && root.querySelector('[data-sm-filter]');
            if (!box) return;
            box.hidden = false;
            const input = box.querySelector('input');
            const status = box.querySelector('[data-sm-status]');
            const empty = root.querySelector('[data-sm-empty]');
            const items = Array.from(root.querySelectorAll('[data-sm-item]'));
            const groups = Array.from(root.querySelectorAll('[data-sm-group]'));
            const norm = (s) => s.toLocaleLowerCase('pl').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ł/g, 'l');
            const ownText = (li) => norm((li.querySelector('a') || li).textContent);

            function apply() {
                const q = norm(input.value.trim());
                let shown = 0;
                // Od najgłębszych: element widoczny, gdy pasuje sam albo któryś z jego potomków.
                items.slice().reverse().forEach(function (li) {
                    const match = !q || ownText(li).includes(q)
                        || Array.from(li.querySelectorAll('[data-sm-item]')).some((c) => !c.hidden);
                    li.hidden = !match;
                    if (match && q && ownText(li).includes(q)) shown++;
                });
                groups.slice().reverse().forEach(function (g) {
                    g.hidden = !!q && !g.querySelector('[data-sm-item]:not([hidden])');
                });
                empty.classList.toggle('hidden', !q || shown > 0);
                status.textContent = q ? (shown ? 'Pasujące pozycje: ' + shown + '.' : 'Brak pasujących pozycji.') : '';
            }
            input.addEventListener('input', apply);
        })();
    </script>
@endsection
