@extends('layouts.site')

@section('title', $project->title . ' — ' . $siteSettings->site_name)
@section('meta_description', $project->excerpt)
@if ($project->image_url)
    @section('og_image', $project->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $project->category->name, 'url' => route('categories.show', $project->category)],
        ['label' => $project->title, 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $customSections = collect($project->custom_sections ?? [])
            ->filter(fn ($s) => ! empty($s['title']) || ! empty($s['content']));
        $featuredSections = $customSections->filter(fn ($s) => ! empty($s['featured']));
        $regularSections = $customSections->reject(fn ($s) => ! empty($s['featured']));

        // Subpages attached to this project, grouped by how they should appear:
        // inline sections in the body, tabs, or just links in the sidebar.
        $inlinePages = $project->publishedPages->where('project_display', 'inline')->values();
        $tabPages = $project->publishedPages->where('project_display', 'tab')->values();
        $linkPages = $project->publishedPages->whereNotIn('project_display', ['inline', 'tab'])->values();

        // A schedule ("harmonogram") page attached to this project — surfaced as a
        // call-to-action near the top; the button jumps to the inline section when
        // embedded, otherwise it opens the schedule's own page.
        $schedulePage = $project->publishedPages->first(fn ($p) => $p->isSchedule());
        $scheduleHref = $schedulePage
            ? ($schedulePage->project_display === 'inline' ? '#harmonogram-'.$schedulePage->id : route('page.show', $schedulePage))
            : null;
    @endphp
    <section class="mx-auto max-w-6xl px-4 py-12">
        <a href="{{ route('categories.show', $project->category) }}" class="mb-2 inline-block text-sm font-bold uppercase tracking-wide text-brand hover:text-brand-dark">
            {{ $project->category->name }}
        </a>
        <h1 class="{{ $project->is_completed ? 'mb-3' : 'mb-8' }} text-3xl font-bold text-ink">{{ $project->title }}</h1>
        @if ($project->is_completed)
            <p class="mb-8">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200">Projekt zrealizowany</span>
            </p>
        @endif

        @if ($schedulePage)
            <div class="mb-8 flex flex-col gap-3 rounded-xl border border-brand/20 bg-brand-light p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-calendar-days mt-0.5 text-xl text-brand" aria-hidden="true"></i>
                    <div>
                        <p class="font-bold text-ink">{{ $schedulePage->title }}</p>
                        <p class="text-sm text-muted">Sprawdź terminy zajęć i spotkań w ramach tego projektu.</p>
                    </div>
                </div>
                <a href="{{ $scheduleHref }}"
                    class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Zobacz harmonogram <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif

        <div class="grid gap-10 md:grid-cols-[1fr_280px]">
            <div class="min-w-0">
                @if ($project->sections_as_tabs && $customSections->isNotEmpty())
                    <div class="mb-8" data-project-tabs>
                        <div class="mb-5 flex flex-wrap gap-1 border-b border-gray-200" role="tablist">
                            @foreach ($customSections->values() as $i => $section)
                                <button type="button" data-project-tab-btn="{{ $i }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    class="-mb-px border-b-2 px-4 py-2 text-sm font-bold {{ $loop->first ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-brand' }}">
                                    {{ $section['title'] ?: 'Sekcja '.($i + 1) }}
                                </button>
                            @endforeach
                        </div>
                        @foreach ($customSections->values() as $i => $section)
                            <div data-project-tab-panel="{{ $i }}" role="tabpanel" class="prose max-w-none text-ink {{ $loop->first ? '' : 'hidden' }}">
                                {!! $section['content'] !!}
                            </div>
                        @endforeach
                    </div>
                @else
                    @foreach ($featuredSections as $section)
                        <div class="mb-8 rounded-xl border-2 border-brand/40 bg-brand-light/40 p-6">
                            @if (! empty($section['title']))
                                <h2 class="mb-3 text-xl font-bold text-ink">{{ $section['title'] }}</h2>
                            @endif
                            @if (! empty($section['content']))
                                <div class="prose max-w-none text-ink">{!! $section['content'] !!}</div>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if ($project->show_legacy_box)
                    <div class="mb-8 rounded-xl border-l-4 border-brand bg-brand-light p-5">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand text-xl text-white" aria-hidden="true">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                            <div>
                                <p class="text-base font-bold text-ink">To działanie realizowaliśmy przed uruchomieniem nowej strony.</p>
                                @if ($project->legacy_url)
                                    <a href="{{ $project->legacy_url }}" target="_blank" rel="noopener"
                                        class="mt-3 inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-dark">
                                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz informacje o projekcie
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if ($project->image_url)
                    <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: 'Zdjęcie ilustracyjne: '.$project->title }}" class="mb-8 h-72 w-full rounded-lg object-cover">
                @endif

                @if ($project->content)
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                        <i class="fa-solid fa-circle-info text-brand" aria-hidden="true"></i> Opis projektu
                    </h2>
                    <div class="prose mb-8 max-w-none text-ink">{!! $project->content !!}</div>
                @endif

                @if ($project->why)
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                        <i class="fa-solid fa-lightbulb text-brand" aria-hidden="true"></i> Dlaczego to robimy
                    </h2>
                    <div class="prose max-w-none text-ink">{{ $project->why }}</div>
                @endif

                @unless ($project->sections_as_tabs)
                    @foreach ($regularSections as $customSection)
                        <div class="mt-8">
                            @if (! empty($customSection['title']))
                                <h2 class="mb-3 text-xl font-bold text-ink">{{ $customSection['title'] }}</h2>
                            @endif
                            @if (! empty($customSection['content']))
                                <div class="prose max-w-none text-ink">{!! $customSection['content'] !!}</div>
                            @endif
                        </div>
                    @endforeach
                @endunless

                {{-- Project subpages shown inline as sections in the body --}}
                @foreach ($inlinePages as $subpage)
                    @php
                        $anchor = $subpage->isSchedule() ? 'harmonogram-'.$subpage->id : ($subpage->isFaq() ? 'faq-'.$subpage->id : null);
                        $subIcon = $subpage->isSchedule() ? 'fa-calendar-days' : ($subpage->isFaq() ? 'fa-circle-question' : 'fa-file-lines');
                    @endphp
                    <section @if ($anchor) id="{{ $anchor }}" @endif class="mt-8 scroll-mt-24 rounded-2xl border border-gray-200 p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid {{ $subIcon }} text-brand" aria-hidden="true"></i> {{ $subpage->title }}
                        </h2>
                        @if ($subpage->content)
                            <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
                        @endif
                        @if ($subpage->isSchedule())
                            @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
                        @elseif ($subpage->isFaq())
                            @include('partials.faq', ['page' => $subpage])
                        @endif
                        <a href="{{ route('page.show', $subpage) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                            Otwórz jako osobną stronę <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </section>
                @endforeach

                {{-- Project subpages shown as tabs --}}
                @if ($tabPages->isNotEmpty())
                    <div class="mt-8" data-subpage-tabs>
                        <div class="mb-5 flex flex-wrap gap-1 border-b border-gray-200" role="tablist">
                            @foreach ($tabPages as $i => $subpage)
                                <button type="button" data-subtab-btn="{{ $i }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    class="-mb-px border-b-2 px-4 py-2 text-sm font-bold {{ $loop->first ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-brand' }}">
                                    {{ $subpage->title }}
                                </button>
                            @endforeach
                        </div>
                        @foreach ($tabPages as $i => $subpage)
                            <div data-subtab-panel="{{ $i }}" role="tabpanel" class="{{ $loop->first ? '' : 'hidden' }}">
                                @if ($subpage->content)
                                    <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
                                @endif
                                @if ($subpage->isSchedule())
                                    @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
                                @elseif ($subpage->isFaq())
                                    @include('partials.faq', ['page' => $subpage])
                                @endif
                                <a href="{{ route('page.show', $subpage) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                                    Otwórz jako osobną stronę <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($project->outcomes)
                    <div class="mt-8 rounded-lg border border-emerald-200 bg-emerald-50/60 p-6">
                        <h2 class="mb-3 flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid fa-award text-emerald-600" aria-hidden="true"></i> Co udało się osiągnąć
                        </h2>
                        <div class="prose max-w-none text-ink">{!! $project->outcomes !!}</div>
                    </div>
                @endif

                @php $pricing = collect($project->pricing ?? [])->filter(fn ($p) => filled($p['item'] ?? null) || filled($p['price'] ?? null)); @endphp
                @if ($project->is_paid && $pricing->isNotEmpty())
                    <div class="mt-8 rounded-lg border border-gray-200 p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid fa-tag text-brand" aria-hidden="true"></i> Cennik
                        </h2>
                        <ul class="divide-y divide-gray-100">
                            @foreach ($pricing as $row)
                                <li class="flex items-baseline justify-between gap-4 py-2.5">
                                    <span class="min-w-0">
                                        <span class="font-medium text-ink">{{ $row['item'] }}</span>
                                        @if (filled($row['note'] ?? null))
                                            <span class="block text-sm text-muted">{{ $row['note'] }}</span>
                                        @endif
                                    </span>
                                    @if (filled($row['price'] ?? null))
                                        <span class="shrink-0 font-bold text-brand">{{ $row['price'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($siteSettings->isModuleEnabled('news') && $project->publishedNews->isNotEmpty())
                    <div class="mt-10 border-t border-gray-200 pt-8">
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid fa-newspaper text-brand" aria-hidden="true"></i> Aktualności projektu
                        </h2>
                        <ul class="space-y-4">
                            @foreach ($project->publishedNews as $item)
                                <li>
                                    <a href="{{ route('news.show', $item) }}"
                                        @class([
                                            'group flex gap-4 rounded-lg border p-4 transition hover:shadow-sm',
                                            'border-gray-200 hover:border-brand/40' => ! $item->is_featured,
                                            'border-2 border-amber-400 bg-amber-50/50' => $item->is_featured,
                                        ])>
                                        @php $itemImg = $item->imageUrlOrDefault(); @endphp
                                        @if ($itemImg)
                                            <img src="{{ $itemImg }}" alt="" loading="lazy" class="h-16 w-24 flex-none rounded object-cover">
                                        @endif
                                        <div class="min-w-0">
                                            @if ($item->is_featured)
                                                <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-bold text-amber-700">
                                                    <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżnione
                                                </span>
                                            @endif
                                            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ $item->published_at->format('d.m.Y') }}</p>
                                            <p class="font-bold text-ink group-hover:text-brand">{{ $item->title }}</p>
                                            @if ($item->excerpt)
                                                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $item->excerpt }}</p>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <aside aria-label="Informacje o projekcie" class="space-y-6 md:border-l md:border-gray-200 md:pl-8">
                @if ($project->for_whom || $project->since)
                    <div class="space-y-4 text-sm">
                        @if ($project->for_whom)
                            <div>
                                <p class="mb-0.5 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                    <i class="fa-solid fa-users text-brand" aria-hidden="true"></i> Dla kogo
                                </p>
                                <p class="text-ink">{{ $project->for_whom }}</p>
                            </div>
                        @endif
                        @if ($project->since)
                            <div>
                                <p class="mb-0.5 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                    <i class="fa-solid fa-calendar-days text-brand" aria-hidden="true"></i> Od kiedy
                                </p>
                                <p class="text-ink">{{ $project->since }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($linkPages->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                            <i class="fa-solid fa-file-lines text-brand" aria-hidden="true"></i> Strony projektu
                        </p>
                        <ul class="space-y-1.5 text-sm">
                            @foreach ($linkPages as $projectPage)
                                <li>
                                    @if ($projectPage->isSchedule())
                                        {{-- A schedule page stands out as a call-to-action button. --}}
                                        <a href="{{ route('page.show', $projectPage) }}"
                                            class="flex items-center justify-center gap-2 rounded-lg bg-brand px-3 py-2.5 text-center font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $projectPage->title }}
                                        </a>
                                    @else
                                        <a href="{{ route('page.show', $projectPage) }}" class="block rounded px-2 py-1.5 font-medium text-ink hover:bg-white hover:text-brand">
                                            {{ $projectPage->title }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! $project->is_completed && $project->showsCoordinator())
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
                        <p class="font-bold text-ink">Kontakt w sprawie projektu</p>
                        @if ($project->coordinator_name)
                            <p class="text-muted">{{ $project->coordinator_name }}</p>
                        @endif
                        <p class="mt-1"><a href="mailto:{{ $project->contactEmail() }}" class="break-all text-brand hover:text-brand-dark">{{ $project->contactEmail() }}</a></p>
                        @if ($project->coordinator_phone)
                            <p><a href="tel:{{ $project->coordinator_phone }}" class="text-brand hover:text-brand-dark">{{ $project->coordinator_phone }}</a></p>
                        @endif
                    </div>
                @endif

                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wszystkie projekty
                </a>
            </aside>
        </div>
    </section>

    <script>
        (function () {
            const wrap = document.querySelector('[data-project-tabs]');
            if (!wrap) return;
            const buttons = wrap.querySelectorAll('[data-project-tab-btn]');
            const panels = wrap.querySelectorAll('[data-project-tab-panel]');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        const active = b === btn;
                        b.classList.toggle('border-brand', active);
                        b.classList.toggle('text-brand', active);
                        b.classList.toggle('border-transparent', !active);
                        b.classList.toggle('text-muted', !active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    panels.forEach(function (p) {
                        p.classList.toggle('hidden', p.dataset.projectTabPanel !== btn.dataset.projectTabBtn);
                    });
                });
            });
        })();

        (function () {
            const wrap = document.querySelector('[data-subpage-tabs]');
            if (!wrap) return;
            const buttons = wrap.querySelectorAll('[data-subtab-btn]');
            const panels = wrap.querySelectorAll('[data-subtab-panel]');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        const active = b === btn;
                        b.classList.toggle('border-brand', active);
                        b.classList.toggle('text-brand', active);
                        b.classList.toggle('border-transparent', !active);
                        b.classList.toggle('text-muted', !active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    panels.forEach(function (p) {
                        p.classList.toggle('hidden', p.dataset.subtabPanel !== btn.dataset.subtabBtn);
                    });
                });
            });
        })();
    </script>
@endsection
