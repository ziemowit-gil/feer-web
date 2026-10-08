@extends('layouts.site')

@section('title', ($project->meta_title ?: $project->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $project->meta_description ?: $project->excerpt)
@if ($project->image_url)
    @section('og_image', $project->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_values(array_filter([
        ['label' => 'Projekty', 'url' => route('projects.index')],
        ['label' => $project->category->name, 'url' => route('categories.show', $project->category)],
        $project->parent && $project->parent->is_published ? ['label' => $project->parent->title, 'url' => route('projects.show', $project->parent)] : null,
        ['label' => $project->title, 'url' => null],
    ]))])
@endsection

@section('content')
    @php
        $canInlineEdit = auth('web')->check() && auth('web')->user()->canAccessModule('projects');
        $customSections = collect($project->custom_sections ?? [])
            ->filter(fn ($s) => ! empty($s['title']) || ! empty($s['content']));
        $featuredSections = $customSections->filter(fn ($s) => ! empty($s['featured']));
        $regularSections = $customSections->reject(fn ($s) => ! empty($s['featured']));

        // Subpages attached to this project, grouped by how they should appear:
        // inline sections in the body, tabs, or just links in the sidebar.
        // Drzewo podstron (dowolna głębokość): na poziomie projektu liczą się tylko korzenie; potomków pokazuje menu boczne w zakładce.
        $pageRoots = $project->pageTree(true);
        $tabPages = $pageRoots->where('project_display', 'tab')->values();
        $inlinePages = $pageRoots->where('project_display', 'inline')->values();
        $accordionPages = $pageRoots->where('project_display', 'accordion')->values();
        // Zakładki (jak w kontakcie): „O projekcie” + sekcje własne (gdy włączono zakładki) + podstrony w trybie zakładki.
        $sectionTabs = $project->sections_as_tabs ? $customSections->values() : collect();
        $tabItems = collect([['id' => 'opis', 'label' => 'O projekcie']])
            ->merge($sectionTabs->map(fn ($sec, $i) => ['id' => 'sekcja-'.$i, 'label' => $sec['title'] ?: 'Sekcja '.($i + 1)]))
            ->merge($tabPages->map(fn ($sp, $i) => ['id' => 'podstrona-'.$i, 'label' => $sp->title]))
            ->all();
        $hasTabs = count($tabItems) > 1;
        // Zdania-ostrzeżenia („Ważne:", „Uwaga:") zamieniamy na wyróżnioną ramkę (callout), niezależnie od tego, jak wyrównał je edytor.
        $projectContentHtml = preg_replace('~<p((?![^>]*\bclass=)[^>]*)>(\s*(?:<(?:strong|b)[^>]*>)?\s*(?:Ważne|Ważna informacja|Uwaga)\b)~iu', '<p class="proj-callout"$1>$2', (string) $project->content);
        // Tryb nawigacji: pasek zakładek albo menu boczne (ustawienie serwisu lub własny wybór projektu).
        $navSidebar = $hasTabs && $project->sectionsNavMode() === 'sidebar';
        $linkPages = $pageRoots->whereNotIn('project_display', ['inline', 'tab', 'accordion'])->values();

        // A schedule ("harmonogram") page attached to this project — surfaced as a
        // call-to-action near the top; the button jumps to the inline section when
        // embedded, otherwise it opens the schedule's own page.
        $schedulePage = $project->publishedPages->first(fn ($p) => $p->isSchedule());
        $scheduleHref = $schedulePage
            ? ($schedulePage->project_display === 'inline' ? '#harmonogram-'.$schedulePage->id : route('page.show', $schedulePage))
            : null;
    @endphp
    <div @if ($canInlineEdit) x-data="inlineContentEditor('project', {{ $project->id }}, '{{ route('admin.inline-edit.update') }}', { engine: '{{ $siteSettings->contentEditorValue() }}', uploadUrl: '{{ route('admin.multimedia.upload-ajax') }}' })" @endif>
    @if ($canInlineEdit)
        @include('partials.inline-edit-bar')
    @endif
        @php
            $catName = trim($project->category->name);
            $forWhom = trim((string) $project->for_whom);
            // „Dla kogo" bywa tym samym, co kategoria (taksonomia projektów jest wg
            // odbiorcy, np. „Dla NGO"), więc pokazujemy je tylko, gdy wnosi coś ponad
            // nazwę kategorii — inaczej grupa docelowa dublowałaby się z plakietką.
            $showForWhom = $forWhom !== ''
                && mb_strtolower($forWhom) !== mb_strtolower($catName)
                && mb_strtolower($forWhom) !== mb_strtolower(trim(\Illuminate\Support\Str::after($catName, 'Dla ')));
        @endphp

    {{-- ══ HERO: kategoria, tytuł, status, zajawka, kluczowe fakty i zdjęcie ══ --}}
    <section class="border-b border-gray-100 bg-gray-50">
        <div class="relative mx-auto grid max-w-6xl items-center gap-8 px-4 py-12 sm:py-16 {{ $project->image_url ? 'lg:grid-cols-[minmax(0,1fr)_26rem]' : '' }}">
            <div class="min-w-0">
                <a href="{{ route('categories.show', $project->category) }}" class="inline-block text-xs font-bold uppercase tracking-widest text-brand hover:text-brand-dark">
                    {{ $project->category->name }}
                </a>
                @if ($project->parent && $project->parent->is_published)
                    <p class="mt-2 text-sm font-bold text-ink">Część działania: <a href="{{ route('projects.show', $project->parent) }}" class="text-brand underline underline-offset-2 hover:text-brand-dark">{{ $project->parent->title }}</a></p>
                @endif
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
                @if ($project->excerpt)
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink/80">{{ $project->excerpt }}</p>
                @endif
                @if ($showForWhom || $project->since)
                    <p class="mt-4 text-sm text-muted">
                        @if ($showForWhom)<span @if ($canInlineEdit) data-inline-field="for_whom" data-inline-kind="text" data-inline-multiline @endif><i class="fa-solid fa-users mr-1.5 text-brand" aria-hidden="true"></i>Dla kogo: <span class="font-medium text-ink">{{ $project->for_whom }}</span></span>@endif
                        @if ($showForWhom && $project->since)<span aria-hidden="true"> · </span>@endif
                        @if ($project->since)<span><i class="fa-solid fa-calendar-days mr-1.5 text-brand" aria-hidden="true"></i>Od kiedy: <span class="font-medium text-ink">{{ $project->since }}</span></span>@endif
                    </p>
                @endif
            </div>

            @if ($project->image_url)
                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: 'Zdjęcie ilustracyjne: '.$project->title }}" data-lightbox
                    class="aspect-[4/3] w-full rounded-lg object-cover shadow-sm ring-1 ring-gray-200">
            @endif
        </div>
    </section>

    <div x-data="{
            tabs: @js(array_column($tabItems, 'id')),
            tab: 'opis', node: null, openIds: [],
            move(step) { const i = this.tabs.indexOf(this.tab); this.tab = this.tabs[(i + step + this.tabs.length) % this.tabs.length]; this.focusActive(); },
            jump(id) { this.tab = id; this.focusActive(); },
            focusActive() { this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); },
        }">
    @if ($hasTabs && ! $navSidebar)
        @include('partials.tab-strip', ['tabItems' => $tabItems, 'tabsLabel' => 'Sekcje projektu'])
    @endif

    <section class="mx-auto max-w-6xl px-4 py-12">
        @php
            $showNews = $siteSettings->isModuleEnabled('news') && $project->publishedNews->isNotEmpty();
            $hasAside = $schedulePage || (! $project->is_completed && $project->showsCoordinator()) || $linkPages->isNotEmpty() || $showNews;
        @endphp
        {{-- Układ kolumn w zwykłym CSS (nie zależy od zbudowanych klas Tailwinda): menu boczne zawsze po lewej od lg. --}}
        <style>
            /* Czytelność: krótsza linia (ok. 70 znaków), ciemniejszy i grubszy tekst, spójne nagłówki, wyróżnione komunikaty. */
            .proj-measure { max-width: 46rem; }
            .proj-flow > * + * { margin-top: 2.5rem; }
            .proj-prose { color: #1d1d1a; font-size: 1.0625rem; line-height: 1.75; font-weight: 500; }
            .proj-prose p, .proj-prose li { color: #1d1d1a; }
            .proj-prose p + p { margin-top: 1rem; }
            .proj-prose strong { font-weight: 700; }
            .proj-prose ul, .proj-prose ol { padding-left: 1.4rem; }
            .proj-prose li { margin-top: .5rem; padding-left: .25rem; }
            .proj-prose li::marker { color: var(--color-brand); font-weight: 700; }
            .proj-prose a { color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; }
            .proj-prose h2, .proj-prose h3 { color: #1d1d1a; font-weight: 800; line-height: 1.3; }
            .proj-prose h2 { margin: 2.5rem 0 1rem; padding-left: .75rem; border-left: 4px solid var(--color-brand); font-size: 1.5rem; }
            .proj-prose h3 { margin: 1.75rem 0 .5rem; font-size: 1.2rem; }
            .proj-h2 { margin: 0 0 1rem; padding-left: .75rem; border-left: 4px solid var(--color-brand); font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
            .proj-callout, .proj-prose p[style*="text-align: center"], .proj-prose p[style*="text-align:center"] {
                position: relative; margin: 1.5rem 0; padding: 1rem 4.5rem 1rem 1.25rem; border: 2px solid var(--color-brand); border-radius: .5rem;
                background: #fff; text-align: left !important; font-weight: 600;
            }
            /* Ozdobny wykrzyknik w obwiedzionym kółku z prawej strony ramki (dekoracja — nie jest czytany). */
            .proj-callout::after, .proj-prose p[style*="text-align: center"]::after, .proj-prose p[style*="text-align:center"]::after {
                content: "!"; position: absolute; top: 50%; right: 1rem; transform: translateY(-50%);
                display: flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem;
                border: 2px solid var(--color-brand); border-radius: 9999px; color: var(--color-brand); background: #fff;
                font-size: 1.25rem; font-weight: 800; line-height: 1;
            }
            .proj-news a { display: block; padding: .6rem 0; }
            @media (min-width: 1024px) {
                .proj-cols { grid-template-columns: minmax(0, 1fr) 18rem; }
                .proj-cols > .proj-main { grid-column: 1; grid-row: 1 / span 3; }
                .proj-cols > .proj-nav, .proj-cols > .proj-aside { grid-column: 2; }
            }
        </style>
        <div @class(['grid items-start gap-10', 'proj-cols' => $hasAside || $navSidebar])>
        <div class="min-w-0 proj-main">
            <div id="panel-opis" role="tabpanel" aria-labelledby="tab-opis" x-show="tab === 'opis'" class="proj-measure proj-flow">
                @if ($sectionTabs->isNotEmpty())
                    {{-- Sekcje własne są w zakładkach (pasek pod nagłówkiem) --}}
                @else
                    @foreach ($featuredSections as $section)
                        <div class="mb-8 rounded-lg border border-brand/20 border-l-4 border-l-brand bg-brand-light/50 p-6">
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
                    <div class="mb-8 rounded-lg border-l-4 border-brand bg-brand-light p-5">
                        <div class="flex items-start gap-4">
                            <i class="fa-solid fa-clock-rotate-left mt-1 text-xl text-brand" aria-hidden="true"></i>
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

                @if ($project->content)
                    <h2 class="proj-h2">Opis projektu</h2>
                    <div class="prose proj-prose max-w-none" @if ($canInlineEdit) data-inline-field="content" data-inline-kind="rich" @endif>{!! $projectContentHtml !!}</div>
                @endif

                @if ($project->why)
                    <h2 class="proj-h2">Dlaczego to robimy</h2>
                    <div class="prose proj-prose max-w-none">{{ $project->why }}</div>
                @endif

                @php $subProjects = $project->publishedChildren; @endphp
                @if ($subProjects->isNotEmpty())
                    <section aria-labelledby="proj-children-h">
                        @php
                            $cnt = $subProjects->count();
                            $cntWord = [2 => 'dwóch', 3 => 'trzech', 4 => 'czterech'][$cnt] ?? (string) $cnt;
                        @endphp
                        <h2 id="proj-children-h" class="proj-h2">{{ $cnt === 1 ? 'Dostępne w osobnej wersji' : 'Możesz skorzystać w '.$cntWord.' wersjach' }}</h2>
                        @php
                            $hasPaid = $subProjects->contains('is_paid', true);
                            $hasFree = $subProjects->contains(fn ($x) => ! $x->is_paid);
                        @endphp
                        <p class="mb-4 text-base leading-relaxed text-ink">
                            @if ($hasPaid && $hasFree)
                                Część tych działań realizujemy <strong>bezpłatnie</strong>, a niektóre możemy też zrealizować <strong>odpłatnie</strong> — na przykład gdy potrzebujesz większego zakresu, własnego terminu lub wsparcia dla całej organizacji. Wybierz wersję, która odpowiada Twoim potrzebom.
                            @elseif ($hasPaid)
                                Te działania możemy zrealizować <strong>odpłatnie</strong> — wybierz wersję, która odpowiada Twoim potrzebom.
                            @else
                                Te działania realizujemy <strong>bezpłatnie</strong> — wybierz wersję, która odpowiada Twoim potrzebom.
                            @endif
                        </p>
                        <ul role="list" class="grid gap-3 sm:grid-cols-2">
                            @foreach ($subProjects as $sp)
                                <li>
                                    <a href="{{ route('projects.show', $sp) }}" class="group flex h-full flex-col gap-1 rounded-lg border-2 border-gray-200 bg-white p-4 transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-lg font-bold text-ink group-hover:text-brand">{{ $sp->title }}</span>
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $sp->is_paid ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-900' }}">{{ $sp->is_paid ? 'Płatne' : 'Bezpłatne' }}</span>
                                        </span>
                                        @if ($sp->excerpt)<span class="text-sm leading-snug text-ink">{{ \Illuminate\Support\Str::limit($sp->excerpt, 140) }}</span>@endif
                                        <span class="mt-auto pt-2 text-sm font-bold text-brand">Zobacz szczegóły <span aria-hidden="true">→</span></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @unless ($sectionTabs->isNotEmpty())
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
                    <section @if ($anchor) id="{{ $anchor }}" @endif class="mt-8 scroll-mt-24 rounded-lg border border-gray-200 p-6">
                        <h2 class="proj-h2">{{ $subpage->title }}</h2>
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

                {{-- Podstrony projektu jako rozwijane sekcje (akordeon) --}}
                @if ($accordionPages->isNotEmpty())
                    @include('partials.page-children-accordion', ['children' => $accordionPages, 'page' => (object) ['id' => 'p'.$project->id, 'title' => $project->title]])
                @endif

                {{-- Project subpages shown as tabs --}}
                @if ($project->outcomes)
                    <div class="mt-8 rounded-lg border border-emerald-200 bg-emerald-50/60 p-6">
                        <h2 class="proj-h2">Co udało się osiągnąć</h2>
                        <div class="prose proj-prose max-w-none">{!! $project->outcomes !!}</div>
                    </div>
                @endif

                @php $pricing = collect($project->pricing ?? [])->filter(fn ($p) => filled($p['item'] ?? null) || filled($p['price'] ?? null)); @endphp
                @if ($project->is_paid && $pricing->isNotEmpty())
                    <div class="mt-8 rounded-lg border border-gray-200 p-6">
                        <h2 class="proj-h2">Cennik</h2>
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

            </div>{{-- /panel-opis --}}

            {{-- Panele pozostałych zakładek --}}
            @foreach ($sectionTabs as $i => $section)
                <div id="panel-sekcja-{{ $i }}" role="tabpanel" aria-labelledby="tab-sekcja-{{ $i }}" tabindex="0" x-cloak
                     x-show="tab === 'sekcja-{{ $i }}'" class="prose proj-prose max-w-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                    @if (! empty($section['title']))<h2 class="mb-3 text-xl font-bold text-ink">{{ $section['title'] }}</h2>@endif
                    {!! $section['content'] ?? '' !!}
                </div>
            @endforeach
            @foreach ($tabPages as $i => $subpage)
                <div id="panel-podstrona-{{ $i }}" role="tabpanel" aria-labelledby="tab-podstrona-{{ $i }}" tabindex="0" x-cloak
                     x-show="tab === 'podstrona-{{ $i }}'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                    @include('projects.partials.tab-page', ['root' => $subpage, 'sidebarMode' => $navSidebar])
                </div>
            @endforeach
            </div>

            {{-- ══ PANEL BOCZNY: harmonogram, kontakt, strony projektu, powrót ══ --}}
            @if ($navSidebar)
                @include('projects.partials.sidebar-nav', ['sectionTabs' => $sectionTabs, 'tabPages' => $tabPages])
            @endif
            @if ($hasAside)
            <aside class="proj-aside space-y-5 lg:sticky lg:top-6" aria-label="Informacje o projekcie">
        @if ($schedulePage)
            <div class="flex flex-col gap-3 rounded-lg border border-brand/20 bg-brand-light p-5">
                <div class="flex items-start gap-3">
                                        <div>
                        <p class="flex items-center gap-2 font-bold text-ink"><i class="fa-solid fa-calendar-days text-brand" aria-hidden="true"></i> {{ $schedulePage->title }}</p>
                        <p class="text-sm text-muted">Sprawdź terminy zajęć i spotkań w ramach tego projektu.</p>
                    </div>
                </div>
                <a href="{{ $scheduleHref }}"
                    class="inline-flex flex-none items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Zobacz harmonogram <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif

                {{-- Kontakt w sprawie projektu — jako zwykła sekcja treści (jak „Opis
                     projektu"), w głównym nurcie i pełną szerokością, nie jako kafelek z boku. --}}
                @if (! $project->is_completed && $project->showsCoordinator())
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-ink">
                            <i class="fa-solid fa-envelope text-brand" aria-hidden="true"></i> Kontakt w sprawie projektu
                        </h2>
                        <div class="space-y-1.5 text-ink">
                            @if ($project->coordinator_name)
                                <p class="font-medium">{{ $project->coordinator_name }}</p>
                            @endif
                            <p>
                                <i class="fa-solid fa-envelope mr-1.5 text-brand" aria-hidden="true"></i><a href="mailto:{{ $project->contactEmail() }}" class="break-all font-medium text-brand hover:text-brand-dark">{{ $project->contactEmail() }}</a>
                            </p>
                            @if ($project->coordinator_phone)
                                <p>
                                    <i class="fa-solid fa-phone mr-1.5 text-brand" aria-hidden="true"></i><a href="tel:{{ $project->coordinator_phone }}" class="font-medium text-brand hover:text-brand-dark">{{ $project->coordinator_phone }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($linkPages->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-ink">
                            <i class="fa-solid fa-file-lines text-brand" aria-hidden="true"></i> Strony projektu
                        </h2>
                        <ul class="flex flex-col gap-2 text-sm">
                            @foreach ($linkPages as $projectPage)
                                <li>
                                    @if ($projectPage->isSchedule())
                                        {{-- Harmonogram wyróżnia się jako przycisk-wezwanie do działania. --}}
                                        <a href="{{ route('page.show', $projectPage) }}"
                                            class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $projectPage->title }}
                                        </a>
                                    @else
                                        <a href="{{ route('page.show', $projectPage) }}"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 font-medium text-ink transition hover:border-brand/40 hover:text-brand">
                                            {{ $projectPage->title }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($showNews)
                    <section class="rounded-lg border border-gray-200 bg-white p-5" aria-labelledby="proj-news-h">
                        <h2 id="proj-news-h" class="proj-h2" style="font-size:1.125rem">Aktualności projektu</h2>
                        <ul role="list" class="proj-news divide-y divide-gray-100">
                            @foreach ($project->publishedNews as $item)
                                <li>
                                    <a href="{{ route('news.show', $item) }}" class="group focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">{{ $item->published_at->format('d.m.Y') }}</span>
                                        <span class="block font-bold leading-snug text-ink group-hover:text-brand">{{ $item->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
            @endif
        </div>
    </section>
    </div>{{-- /x-data zakładek --}}

    </div>
@endsection
