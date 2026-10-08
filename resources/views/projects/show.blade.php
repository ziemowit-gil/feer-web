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
                @if ($project->isPaidOffer())
                    <p class="mt-3"><span class="proj-status is-paid-offer"><i class="fa-solid fa-coins mr-1.5" aria-hidden="true"></i>Usługa odpłatna</span></p>
                @endif
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
                @if ($siteSettings->projects_stages_enabled && ($project->status || $project->starts_on))
                    <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink">
                        @if ($project->status)<span class="proj-status is-{{ $project->status }}">{{ \App\Models\Project::STATUSES[$project->status] ?? '' }}</span>@endif
                        @if ($project->starts_on)<span><i class="fa-solid fa-calendar-days mr-1.5 text-brand" aria-hidden="true"></i>{{ $project->starts_on->locale('pl')->isoFormat('D MMMM YYYY') }}@if ($project->ends_on) – {{ $project->ends_on->locale('pl')->isoFormat('D MMMM YYYY') }}@endif</span>@endif
                    </p>
                @endif
                @php $extrasOn = $siteSettings->projects_extras_enabled; $mainCta = $extrasOn ? (array) ($project->main_cta ?? []) : []; @endphp
                @if ($project->excerpt)
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink/80">{{ $project->excerpt }}</p>
                @endif
                @if (! empty($mainCta['label']) && ! empty($mainCta['url']))
                    <p class="mt-5"><a href="{{ $mainCta['url'] }}" class="proj-cta">{{ $mainCta['label'] }} <span aria-hidden="true">→</span></a></p>
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
            $showTeamFunding = $siteSettings->projects_team_funding_enabled;
            $projPartners = $showTeamFunding ? $project->partners()->orderBy('order')->orderBy('name')->get() : collect();
            $projFunding = $showTeamFunding ? (array) ($project->funding ?? []) : [];
            $hasFunding = $showTeamFunding && (! empty($projFunding['sources']) || (! empty($projFunding['budget']) && ! empty($projFunding['budget_public'])) || filled($project->funding_notice));
            $hasAside = $schedulePage || (! $project->is_completed && $project->showsCoordinator()) || $linkPages->isNotEmpty() || $showNews || $projPartners->isNotEmpty() || $hasFunding;
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
            .proj-note { position: relative; margin: 0 0 1rem; padding: 1rem 4.5rem 1rem 1.25rem; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; color: #1d1d1a; font-size: 1.0625rem; line-height: 1.6; font-weight: 600; }
                                    /* Sekcje informacyjne bez ramek: zwykły nagłówek z paskiem marki (jak „Opis projektu"); ramki zostają tylko dla komunikatów (.proj-note, .proj-callout). */
            .proj-frame { margin-top: 2.5rem; padding: 0; border: 0; background: transparent; }
            .proj-frame-side { margin-top: 0; margin-bottom: 1.5rem; }
            .proj-frame-side .proj-frame-h { font-size: 1.125rem; }
            .proj-frame-h { display: block; margin: 0 0 1rem; padding-left: .75rem; border-left: 4px solid var(--color-brand); font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
            .proj-frame-h i { display: none; }
            .proj-stages { list-style: none; margin: 0; padding: 0; display: grid; gap: .9rem; }
            .proj-stage { display: flex; gap: .85rem; }
            .proj-stage-dot { display: inline-flex; flex: none; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: 9999px; border: 2px solid #6b7280; background: #fff; color: #4b5563; font-size: .7rem; }
            .proj-stage.is-done .proj-stage-dot { background: #166534; border-color: #166534; color: #fff; }
            .proj-stage.is-current .proj-stage-dot { background: var(--color-brand); border-color: var(--color-brand); color: #fff; }
            .proj-stage-body { display: grid; gap: .1rem; }
            .proj-stage-title { font-weight: 800; color: #1d1d1a; } .proj-stage-state { font-weight: 600; color: #4b5563; font-size: .85em; }
            .proj-stage-date { font-size: .85rem; color: #374151; } .proj-stage-text { font-size: .95rem; line-height: 1.5; color: #1d1d1a; }
            .proj-team { list-style: none; margin: 0; padding: 0; display: grid; gap: .9rem 1.5rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
            .proj-team li { display: grid; gap: .1rem; } .proj-team-name { font-weight: 800; } .proj-team-role { font-size: .9rem; font-weight: 700; color: var(--color-brand); } .proj-team-text { font-size: .9rem; line-height: 1.45; }
            .proj-partners { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr)); align-items: center; }
            .proj-partners img { display: block; max-height: 3.5rem; max-width: 100%; object-fit: contain; } .proj-partners a { display: block; } .proj-partners span { font-weight: 700; font-size: .9rem; }
            .proj-fund { list-style: none; margin: 0 0 .75rem; padding: 0; display: grid; gap: .6rem; } .proj-fund-name { display: block; font-weight: 800; } .proj-fund-name a { color: var(--color-brand); text-decoration: underline; text-underline-offset: 2px; } .proj-fund-text { display: block; font-size: .9rem; line-height: 1.45; }
            .proj-fund-budget { margin: 0 0 .5rem; font-size: .95rem; } .proj-fund-notice { margin: 0; padding-top: .6rem; border-top: 1px solid #e5e7eb; font-size: .85rem; line-height: 1.5; color: #1d1d1a; }
            .proj-status { display: inline-flex; align-items: center; border-radius: 9999px; padding: .15rem .75rem; font-size: .8rem; font-weight: 800; border: 2px solid #1d1d1a; background: #fff; color: #1d1d1a; }
            .proj-status.is-paid-offer { border-color: #92400e; color: #92400e; }
            .proj-status.is-active { border-color: var(--color-brand); color: var(--color-brand); } .proj-status.is-completed { border-color: #166534; color: #166534; } .proj-status.is-planned { border-color: #92400e; color: #92400e; }
                        .proj-menu-box { position: relative; padding: 1.5rem; background: #f3f4f6; }
            .proj-menu-line { position: absolute; top: 0; left: 0; display: block; width: 4rem; height: .25rem; background: var(--color-brand); }
            .proj-menu-title { margin: 0 0 1rem; padding-bottom: .75rem; border-bottom: 1px solid #111827; font-size: 1.125rem; font-weight: 700; color: #1d1d1a; }
            .proj-menu-list { list-style: none; margin: 0; padding: 0; }
            .proj-menu-list li { border-bottom: 1px solid #d1d5db; } .proj-menu-list li:last-child { border-bottom: 0; }
            .proj-menu-list a { display: block; padding: .75rem 0; color: #1d1d1a; text-decoration: none; }
            .proj-menu-list a:hover .proj-menu-item { color: var(--color-brand); }
            .proj-menu-list a:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
            .proj-menu-date { display: block; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #374151; }
            .proj-menu-item { display: block; font-size: 1rem; font-weight: 400; line-height: 1.4; }
                        .proj-ref-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
            .proj-ref-link { display: inline-flex; flex-wrap: wrap; align-items: baseline; gap: .15rem; padding: .15rem 0; border: 0; background: none; font: inherit; font-weight: 800; color: var(--color-brand); text-align: left; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
            .proj-ref-link:hover { color: #1d1d1a; } .proj-ref-link:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 3px; border-radius: .25rem; }
            .proj-ref-kind { font-weight: 500; color: #374151; text-decoration: none; display: inline-block; }
                        .proj-paid-info { font-weight: 500; }
            .proj-paid-info summary { cursor: pointer; font-weight: 800; color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; }
            .proj-paid-info summary:hover { color: #1d1d1a; } .proj-paid-info summary:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 3px; border-radius: .25rem; }
            .proj-paid-link { font-weight: 800; color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; }
            .proj-paid-link:hover { color: #1d1d1a; } .proj-paid-link:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 3px; border-radius: .25rem; }
            .proj-paid-body { margin-top: .6rem; padding-left: .85rem; border-left: 3px solid var(--color-brand); font-size: .95rem; line-height: 1.6; font-weight: 500; }
            .proj-paid-body p { margin: 0 0 .6rem; } .proj-paid-body p:last-child { margin-bottom: 0; }
                        .proj-price-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
            .proj-price-card { display: flex; flex-direction: column; gap: .35rem; min-height: 8rem; padding: 1rem 1.1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; }
            .proj-price-item { font-size: 1rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
            .proj-price-note { font-size: .9rem; line-height: 1.45; color: #1d1d1a; }
            .proj-price-amount { margin-top: auto; padding-top: .5rem; font-size: 1.5rem; font-weight: 800; line-height: 1.1; color: var(--color-brand); }
                        .proj-terms { margin: 0; display: grid; gap: .6rem; }
            .proj-terms-row { display: grid; gap: .15rem 1rem; grid-template-columns: minmax(8rem, 12rem) 1fr; padding-bottom: .6rem; border-bottom: 1px solid #e5e7eb; }
            .proj-terms-row:last-child { padding-bottom: 0; border-bottom: 0; }
            .proj-terms-row dt { font-weight: 800; color: #1d1d1a; } .proj-terms-row dd { margin: 0; line-height: 1.5; color: #1d1d1a; }
            @media (max-width: 40rem) { .proj-terms-row { grid-template-columns: 1fr; } }
                        .proj-cta { display: inline-flex; align-items: center; gap: .6rem; min-height: 3rem; padding: .6rem 1.4rem; border: 2px solid var(--color-brand); border-radius: .375rem; background: var(--color-brand); color: #fff; font-size: 1.05rem; font-weight: 800; text-decoration: none; }
            .proj-cta:hover { background: #1d1d1a; border-color: #1d1d1a; } .proj-cta:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
            .proj-easy-text { margin: 0; font-size: 1.15rem; line-height: 1.7; font-weight: 600; color: #1d1d1a; }
            .proj-metrics { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); }
            .proj-metrics li { display: grid; gap: .15rem; text-align: center; padding: .75rem .5rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; }
            .proj-metric-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: var(--color-brand); } .proj-metric-label { font-size: .9rem; line-height: 1.35; color: #1d1d1a; }
            .proj-tests { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; }
            .proj-tests blockquote { margin: 0; padding: .25rem 0 .25rem 1rem; border-left: 4px solid var(--color-brand); } .proj-tests blockquote p { margin: 0; font-size: 1.05rem; line-height: 1.6; font-style: italic; color: #1d1d1a; } .proj-tests footer { margin-top: .35rem; font-size: .9rem; font-weight: 700; color: #374151; }
            .proj-faq details { border-bottom: 1px solid #d1d5db; } .proj-faq details:last-child { border-bottom: 0; }
            .proj-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 3rem; padding: .5rem 0; cursor: pointer; list-style: none; font-weight: 800; color: #1d1d1a; } .proj-faq summary::-webkit-details-marker { display: none; }
            .proj-faq summary:hover { color: var(--color-brand); } .proj-faq summary:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; border-radius: .25rem; }
            .proj-faq details[open] summary i { transform: rotate(180deg); } .proj-faq summary i { flex: none; font-size: .8rem; transition: transform .15s; }
            .proj-faq-a { padding: 0 2rem .9rem 0; line-height: 1.6; color: #1d1d1a; }
            @media (prefers-reduced-motion: reduce) { .proj-faq summary i { transition: none; } }
            .proj-note-links { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: .75rem; }
            .proj-note-links a { font-weight: 800; color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; }
            .proj-note-links a:hover { color: #1d1d1a; }
            .proj-note-links a:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 3px; border-radius: .25rem; }
            .proj-note-ico { position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border: 2px solid var(--color-brand); border-radius: 9999px; background: #fff; color: var(--color-brand); font-size: 1.1rem; }
            .proj-choice { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); margin: 0; padding: 0; list-style: none; }
            .proj-choice > li { display: flex; }
            .proj-choice-tile { display: flex; flex: 1; flex-direction: column; gap: .5rem; padding: 1.25rem; border: 2px solid #1d1d1a; border-top-width: 6px; border-radius: .75rem; background: #fff; color: #1d1d1a; text-decoration: none; transition: transform .15s, box-shadow .15s; }
            .proj-choice-tile.is-free { border-top-color: #166534; }
            .proj-choice-tile.is-paid { border-top-color: var(--color-brand); }
            .proj-choice-tile.is-off { border-color: #6b7280; border-top-color: #6b7280; background: #f3f4f6; }
            .proj-choice-tile.is-off .proj-choice-kind { color: #374151; }
            .proj-choice-tile.is-off .proj-choice-cta { color: #1d1d1a; }
            .proj-choice-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.12); }
            .proj-choice-tile:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
            .proj-choice-kind { font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
            .is-free .proj-choice-kind { color: #166534; } .is-paid .proj-choice-kind { color: #1d4ed8; }
            .proj-choice-title { font-size: 1.15rem; font-weight: 800; line-height: 1.3; }
            .proj-choice-text { font-size: .95rem; line-height: 1.5; }
            .proj-choice-price { font-size: 1rem; font-weight: 700; }
            .proj-choice-cta { margin-top: auto; padding-top: .5rem; display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: var(--color-brand); }
            .proj-choice-tile:hover .proj-choice-cta { text-decoration: underline; }
            @media (prefers-reduced-motion: reduce) { .proj-choice-tile { transition: none; } .proj-choice-tile:hover { transform: none; } }
            .proj-part { display: inline-flex; align-items: center; gap: .6rem; max-width: 100%; padding: .4rem .9rem .4rem .45rem; border: 2px solid var(--color-brand); border-radius: 9999px; background: #fff; font-size: .9rem; line-height: 1.3; color: #1d1d1a; }
            .proj-part-ico { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background: var(--color-brand); color: #fff; font-size: .8rem; }
            .proj-part a { font-weight: 700; color: var(--color-brand); text-decoration: underline; text-underline-offset: 2px; }
            .proj-part a:hover { color: #1d1d1a; }
            @media (min-width: 1024px) {
                .proj-cols { grid-template-columns: minmax(0, 1fr) 18rem; }
                .proj-cols > .proj-main { grid-column: 1; grid-row: 1 / span 3; }
                .proj-cols > .proj-nav, .proj-cols > .proj-aside { grid-column: 2; }
            }
        </style>
        <div @class(['grid items-start gap-10', 'proj-cols' => $hasAside || $navSidebar])>
        <div class="min-w-0 proj-main">
            <div id="panel-opis" @if ($hasTabs && ! $navSidebar) role="tabpanel" aria-labelledby="tab-opis" @endif x-show="tab === 'opis'" class="proj-measure proj-flow">
                @if ($project->isPaidOffer())
                    {{-- Usługa wyłącznie odpłatna: wyraźna informacja, objaśnienie i przycisk kontaktu. --}}
                    <div class="proj-note">
                        <span>Z tej usługi skorzystasz <strong>za opłatą</strong>. Cenę i zasady znajdziesz poniżej. Jeśli masz pytania, napisz do nas.</span>
                        <span class="proj-note-links"><a href="{{ route('contact.show') }}">Napisz do nas, żeby ustalić termin <span aria-hidden="true">→</span></a></span>
                        @include('projects.partials.paid-info', ['infoProject' => $project])
                        <span class="proj-note-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                    </div>
                @endif
                @if ($siteSettings->projects_subprojects_enabled && $project->is_paid && $project->parent_id)
                    <div class="proj-note"><span>Ta forma jest <strong>płatna</strong>.</span>
                        @include('projects.partials.paid-info', ['infoProject' => $project])
                        <span class="proj-note-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                    </div>
                @endif
                @php $subProjects = $siteSettings->projects_subprojects_enabled ? $project->publishedChildren : collect(); @endphp
                @if ($subProjects->isNotEmpty())
                    <section aria-labelledby="proj-children-h">
                        @php
                            $offered = $subProjects->filter(fn ($x) => $x->is_offered)->values();
                            $cnt = $offered->count();
                            $cntWord = [2 => 'Dwie', 3 => 'Trzy', 4 => 'Cztery'][$cnt] ?? (string) $cnt;
                            $only = $subProjects->count() === 1 ? $subProjects->first() : null;
                            $hasPaid = $offered->contains('is_paid', true);
                            $hasFree = $offered->contains(fn ($x) => ! $x->is_paid);
                        @endphp
                        <h2 id="proj-children-h" class="proj-h2">
                            @if ($cnt === 0)
                                Jak możesz wziąć udział
                            @elseif ($only)
                                {{ $only->is_paid ? 'Możesz też wybrać formę płatną' : 'Możesz też wybrać formę bezpłatną' }}
                            @elseif ($cnt === 1)
                                Jest jedna forma udziału
                            @else
                                {{ $cntWord }} {{ $cnt >= 5 ? 'form' : 'formy' }} udziału. Wybierz swoją.
                            @endif
                        </h2>
                        {{-- Prosty język (ETR): krótkie zdania, bez żargonu. --}}
                        <div class="proj-note mb-4"><span>
                            @if ($only && $only->is_offered)
                                @if ($only->is_paid)
                                    Z tego działania możesz skorzystać także <strong>za opłatą</strong>. Wtedy sam ustalasz termin i zakres.
                                @else
                                    Z tego działania możesz skorzystać także <strong>bezpłatnie</strong>. Sprawdź, kto może wziąć udział.
                                @endif
                            @elseif ($cnt === 0)
                                Teraz nie prowadzimy tego działania. Zajrzyj tu później albo napisz do nas.
                            @elseif ($hasPaid && $hasFree)
                                To samo działanie możesz wybrać na dwa sposoby. Jeden jest <strong>bezpłatny</strong>. Drugi jest <strong>płatny</strong>. Wybierz ten, który jest dla Ciebie lepszy.
                            @elseif ($hasPaid)
                                Z tego działania skorzystasz <strong>za opłatą</strong>. Możesz sam ustalić termin i zakres, które Ci pasują.
                            @else
                                Z tego działania skorzystasz <strong>bezpłatnie</strong>. Wybierz formę, która Ci odpowiada.
                            @endif
                            </span>
                            <span class="proj-note-links">
                                @foreach ($subProjects as $sp)
                                    <a href="{{ route('projects.show', $sp) }}">
                                        {{ $sp->is_paid ? 'Zobacz, jak działa forma płatna' : 'Zobacz, jak działa forma bezpłatna' }}@unless ($sp->is_offered) (teraz niedostępna)@endunless <span aria-hidden="true">→</span><span class="sr-only">: {{ $sp->title }}</span>
                                    </a>
                                @endforeach
                            </span>
                            @php $paidChild = $subProjects->firstWhere('is_paid', true); @endphp
                            @if ($paidChild)
                                @include('projects.partials.paid-info', ['infoProject' => $paidChild])
                            @endif
                            <span class="proj-note-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                        </div>
                    </section>
                @endif
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

                @if ($extrasOn && filled($project->easy_summary))
                    <section class="proj-frame proj-easy" aria-labelledby="proj-easy-h">
                        <h2 id="proj-easy-h" class="proj-frame-h"><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Prostym językiem</h2>
                        <p class="proj-easy-text">{!! nl2br(e($project->easy_summary)) !!}</p>
                    </section>
                @endif

                @php $qfacts = $extrasOn ? (array) ($project->quick_facts ?? []) : []; @endphp
                @if (! empty(array_filter($qfacts)))
                    <section class="proj-frame" aria-labelledby="proj-facts-h">
                        <h2 id="proj-facts-h" class="proj-frame-h"><i class="fa-solid fa-list-check" aria-hidden="true"></i> W skrócie</h2>
                        <dl class="proj-terms">
                            @if (! empty($qfacts['duration']))<div class="proj-terms-row"><dt>Czas trwania</dt><dd>{{ $qfacts['duration'] }}</dd></div>@endif
                            @if (! empty($qfacts['place']))<div class="proj-terms-row"><dt>Miejsce</dt><dd>{{ $qfacts['place'] }}</dd></div>@endif
                            @if (! empty($qfacts['mode']))<div class="proj-terms-row"><dt>Forma</dt><dd>{{ \App\Models\Project::MODES[$qfacts['mode']] ?? '' }}</dd></div>@endif
                            @if (! empty($qfacts['seats']))<div class="proj-terms-row"><dt>Liczba miejsc</dt><dd>{{ $qfacts['seats'] }}</dd></div>@endif
                        </dl>
                    </section>
                @endif

                {{-- Kto może wziąć udział — ramka z warunkami --}}
                @php $projTerms = $siteSettings->projects_terms_enabled ? collect($project->terms ?? [])->filter(fn ($r) => filled($r['label'] ?? null) && filled($r['text'] ?? null)) : collect(); @endphp
                @if ($projTerms->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-terms-h">
                        <h2 id="proj-terms-h" class="proj-frame-h"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Kto może wziąć udział</h2>
                        <dl class="proj-terms">
                            @foreach ($projTerms as $tr)
                                <div class="proj-terms-row"><dt>{{ $tr['label'] }}</dt><dd>{{ $tr['text'] }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                {{-- Nawiązanie do podstron i stron projektu: jedna ramka z odnośnikami do wszystkiego, co należy do projektu. --}}
                @php
                    $refTabs = $tabPages->values();
                    $refInline = $inlinePages->values();
                    $refAcc = $accordionPages->values();
                    $refLinks = $linkPages->values();
                    $refSubs = $siteSettings->projects_subprojects_enabled ? $project->publishedChildren : collect();
                    $refTotal = $refTabs->count() + $refInline->count() + $refAcc->count() + $refLinks->count();
                @endphp
                @if ($refTotal > 0)
                    <section class="proj-frame proj-ref" aria-labelledby="proj-ref-h">
                        <h2 id="proj-ref-h" class="proj-frame-h"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> W tym projekcie znajdziesz</h2>
                        <ul role="list" class="proj-ref-list">
                            @foreach ($refTabs as $ri => $rp)
                                <li><button type="button" class="proj-ref-link" @click="tab = 'podstrona-{{ $ri }}'; node = null; $nextTick(() => document.getElementById('tab-podstrona-{{ $ri }}')?.scrollIntoView({ block: 'center' }))">{{ $rp->title }}<span class="proj-ref-kind"> — zakładka</span></button></li>
                            @endforeach
                            @foreach ($refInline as $rp)
                                <li><a class="proj-ref-link" href="#{{ ($rp->isSchedule() ? 'harmonogram-'.$rp->id : ($rp->isFaq() ? 'faq-'.$rp->id : 'podstrona-sekcja-'.$rp->id)) }}">{{ $rp->title }}<span class="proj-ref-kind"> — sekcja na tej stronie</span></a></li>
                            @endforeach
                            @if ($refAcc->isNotEmpty())
                                <li><a class="proj-ref-link" href="#projekt-rozwijane">{{ $refAcc->pluck('title')->take(3)->implode(', ') }}{{ $refAcc->count() > 3 ? ' i inne' : '' }}<span class="proj-ref-kind"> — rozwijane sekcje</span></a></li>
                            @endif
                            @foreach ($refLinks as $rp)
                                <li><a class="proj-ref-link" href="{{ route('page.show', $rp) }}">{{ $rp->title }}<span class="proj-ref-kind"> — osobna strona</span></a></li>
                            @endforeach
                            @foreach ($refSubs as $rs)
                                <li><a class="proj-ref-link" href="{{ route('projects.show', $rs) }}">{{ $rs->title }}<span class="proj-ref-kind"> — {{ $rs->is_paid ? 'forma płatna' : 'forma bezpłatna' }}</span></a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @php $projMetrics = $extrasOn ? collect($project->metrics ?? []) : collect(); @endphp
                @if ($projMetrics->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-metrics-h">
                        <h2 id="proj-metrics-h" class="proj-frame-h"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Efekty w liczbach</h2>
                        <ul role="list" class="proj-metrics">
                            @foreach ($projMetrics as $mt)
                                <li><span class="proj-metric-value">{{ $mt['value'] }}</span><span class="proj-metric-label">{{ $mt['label'] }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @php $projTests = $extrasOn ? collect($project->testimonials ?? []) : collect(); @endphp
                @if ($projTests->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-tests-h">
                        <h2 id="proj-tests-h" class="proj-frame-h"><i class="fa-solid fa-comments" aria-hidden="true"></i> Co mówią uczestnicy</h2>
                        <ul role="list" class="proj-tests">
                            @foreach ($projTests as $ts)
                                <li><blockquote><p>„{{ $ts['text'] }}”</p>@if (! empty($ts['author']))<footer>— {{ $ts['author'] }}</footer>@endif</blockquote></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @php $projFaq = $extrasOn ? collect($project->faq ?? []) : collect(); @endphp
                @if ($projFaq->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-faq-h">
                        <h2 id="proj-faq-h" class="proj-frame-h"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> Pytania i odpowiedzi</h2>
                        <div class="proj-faq">
                            @foreach ($projFaq as $fi => $fq)
                                <details class="group" name="proj-faq-{{ $project->id }}" @if ($fi === 0) open @endif>
                                    <summary>{{ $fq['q'] }}<i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
                                    <div class="proj-faq-a">{!! nl2br(e($fq['a'])) !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Etapy (harmonogram) — ramka --}}
                @php $projStages = $siteSettings->projects_stages_enabled ? collect($project->stages ?? []) : collect(); @endphp
                @if ($projStages->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-stages-h">
                        <h2 id="proj-stages-h" class="proj-frame-h"><i class="fa-solid fa-timeline" aria-hidden="true"></i> Etapy projektu</h2>
                        <ol class="proj-stages" role="list">
                            @foreach ($projStages as $st)
                                @php $state = $st['state'] ?? 'upcoming'; @endphp
                                <li class="proj-stage is-{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                                    <span class="proj-stage-dot" aria-hidden="true"><i class="fa-solid {{ $state === 'done' ? 'fa-check' : ($state === 'current' ? 'fa-play' : 'fa-circle') }}"></i></span>
                                    <span class="proj-stage-body">
                                        <span class="proj-stage-title">{{ $st['title'] }} <span class="proj-stage-state">— {{ \App\Models\Project::STAGE_STATES[$state] ?? '' }}</span></span>
                                        @if (! empty($st['from']) || ! empty($st['to']))
                                            <span class="proj-stage-date">{{ ! empty($st['from']) ? \Illuminate\Support\Carbon::parse($st['from'])->locale('pl')->isoFormat('D MMM YYYY') : '' }}@if (! empty($st['to'])) – {{ \Illuminate\Support\Carbon::parse($st['to'])->locale('pl')->isoFormat('D MMM YYYY') }}@endif</span>
                                        @endif
                                        @if (! empty($st['text']))<span class="proj-stage-text">{{ $st['text'] }}</span>@endif
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                {{-- Zespół — ramka --}}
                @php $projTeam = $showTeamFunding ? collect($project->team ?? []) : collect(); @endphp
                @if ($projTeam->isNotEmpty())
                    <section class="proj-frame" aria-labelledby="proj-team-h">
                        <h2 id="proj-team-h" class="proj-frame-h"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Zespół projektu</h2>
                        <ul role="list" class="proj-team">
                            @foreach ($projTeam as $tm)
                                <li>
                                    <span class="proj-team-name">{{ $tm['name'] }}</span>
                                    @if (! empty($tm['role']))<span class="proj-team-role">{{ $tm['role'] }}</span>@endif
                                    @if (! empty($tm['text']))<span class="proj-team-text">{{ $tm['text'] }}</span>@endif
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
                    <section id="{{ $anchor ?: 'podstrona-sekcja-'.$subpage->id }}" class="mt-8 scroll-mt-24 rounded-lg border border-gray-200 p-6">
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
                    <span id="projekt-rozwijane" class="scroll-mt-24"></span>
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
                    {{-- Cennik jako karty cen w ramce: nazwa, opis i duża cena na dole. --}}
                    <section class="proj-frame proj-price" aria-labelledby="proj-price-h">
                        <h2 id="proj-price-h" class="proj-frame-h"><i class="fa-solid fa-coins" aria-hidden="true"></i> Cennik</h2>
                        <ul role="list" class="proj-price-grid">
                            @foreach ($pricing as $row)
                                <li class="proj-price-card">
                                    <span class="proj-price-item">{{ $row['item'] }}</span>
                                    @if (filled($row['note'] ?? null))<span class="proj-price-note">{{ $row['note'] }}</span>@endif
                                    @if (filled($row['price'] ?? null))
                                        <span class="proj-price-amount"><span class="sr-only">Cena: </span>{{ $row['price'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
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

                @if ($projPartners->isNotEmpty())
                    <section class="proj-frame proj-frame-side" aria-labelledby="proj-partners-h">
                        <h2 id="proj-partners-h" class="proj-frame-h"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Partnerzy</h2>
                        <ul role="list" class="proj-partners">
                            @foreach ($projPartners as $pt)
                                <li>
                                    @if ($pt->url)<a href="{{ $pt->url }}" target="_blank" rel="noopener">@endif
                                        @if ($pt->logo_url)<img src="{{ $pt->logo_url }}" alt="{{ $pt->name }}" loading="lazy">@else<span>{{ $pt->name }}</span>@endif
                                        @if ($pt->url)<span class="sr-only"> (otwiera się w nowej karcie)</span></a>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($hasFunding)
                    <section class="proj-frame proj-frame-side" aria-labelledby="proj-fund-h">
                        <h2 id="proj-fund-h" class="proj-frame-h"><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i> Finansowanie</h2>
                        @if (! empty($projFunding['sources']))
                            <ul role="list" class="proj-fund">
                                @foreach ($projFunding['sources'] as $fs)
                                    <li>
                                        <span class="proj-fund-name">@if (! empty($fs['url']))<a href="{{ $fs['url'] }}" target="_blank" rel="noopener">{{ $fs['name'] }}<span class="sr-only"> (nowa karta)</span></a>@else{{ $fs['name'] }}@endif</span>
                                        @if (! empty($fs['text']))<span class="proj-fund-text">{{ $fs['text'] }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (! empty($projFunding['budget']) && ! empty($projFunding['budget_public']))
                            <p class="proj-fund-budget">Budżet projektu: <strong>{{ $projFunding['budget'] }}</strong></p>
                        @endif
                        @if (filled($project->funding_notice))
                            <p class="proj-fund-notice">{{ $project->funding_notice }}</p>
                        @endif
                    </section>
                @endif

                @if ($showNews)
                    {{-- Aktualności projektu w stylu menu sekcji: szare pole, kreska marki, tytuł i pozycje oddzielone liniami. --}}
                    <nav class="proj-menu-box" aria-labelledby="proj-news-h">
                        <span class="proj-menu-line" aria-hidden="true"></span>
                        <p id="proj-news-h" class="proj-menu-title">Aktualności projektu</p>
                        <ul role="list" class="proj-menu-list">
                            @foreach ($project->publishedNews as $item)
                                <li>
                                    <a href="{{ route('news.show', $item) }}">
                                        <span class="proj-menu-date">{{ $item->published_at->format('d.m.Y') }}</span>
                                        <span class="proj-menu-item">{{ $item->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </aside>
            @endif
        </div>
    </section>
    </div>{{-- /x-data zakładek --}}

    </div>
@endsection
