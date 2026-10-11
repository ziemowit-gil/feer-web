@extends('layouts.site')

@section('title', ($project->meta_title ?: $project->title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $project->meta_description ?: $project->excerpt)
@if ($project->image_url)
    @section('og_image', $project->image_url)
@endif

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_values(array_filter([
        ['label' => 'Działania', 'url' => route('projects.index')],
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
        // Drzewo podstron (dowolna głębokość): na poziomie działania liczą się tylko korzenie; potomków pokazuje menu boczne w zakładce.
        $pageRoots = $project->pageTree(true);
        $tabPages = $pageRoots->where('project_display', 'tab')->values();
        // Przekierowanie / „Własny link w menu” w trybie zakładki to zwykły link (w menu bocznym albo obok paska zakładek), nie zakładka.
        $tabLinks = $tabPages->filter(fn ($p) => $p->navUrl() !== $p->publicUrl())->values();
        $tabPages = $tabPages->reject(fn ($p) => $p->navUrl() !== $p->publicUrl())->values();
        $inlinePages = $pageRoots->where('project_display', 'inline')->values();
        $accordionPages = $pageRoots->where('project_display', 'accordion')->values();
        // Zakładki (jak w kontakcie): „O projekcie” + sekcje własne (gdy włączono zakładki) + podstrony w trybie zakładki.
        $sectionTabs = $project->sections_as_tabs ? $customSections->values() : collect();
        $tabItems = collect([['id' => 'opis', 'label' => 'O projekcie']])
            ->merge($sectionTabs->map(fn ($sec, $i) => ['id' => 'sekcja-'.$i, 'label' => $sec['title'] ?: 'Sekcja '.($i + 1)]))
            ->merge($tabPages->map(fn ($sp, $i) => ['id' => 'podstrona-'.$i, 'label' => $sp->title]))
            ->all();
        $hasTabs = count($tabItems) > 1 || ($tabLinks->isNotEmpty() && $project->sectionsNavMode() === 'sidebar');
        // Formularz wysłany z zakładki (np. z błędami) — po powrocie otwieramy tę zakładkę i podstronę z formularzem.
        $openTab = 'opis';
        $openNodeId = null;
        if ($sentFormSlug = session('_form_slug')) {
            $findForm = function ($n) use (&$findForm, $sentFormSlug) {
                if ($n->type === 'form_page' && ($n->typeData()['fp_form'] ?? null) === $sentFormSlug) {
                    return $n;
                }
                foreach ($n->tree_children as $c) {
                    if ($hit = $findForm($c)) {
                        return $hit;
                    }
                }

                return null;
            };
            foreach ($tabPages as $i => $sp) {
                if ($hit = $findForm($sp)) {
                    $openTab = 'podstrona-'.$i;
                    $openNodeId = $hit->id;
                    break;
                }
            }
        }
        // Zdania-ostrzeżenia („Ważne:", „Uwaga:") zamieniamy na wyróżnioną ramkę (callout), niezależnie od tego, jak wyrównał je edytor.
        $projectContentHtml = preg_replace('~<p((?![^>]*\bclass=)[^>]*)>(\s*(?:<(?:strong|b)[^>]*>)?\s*(?:Ważne|Ważna informacja|Uwaga)\b)~iu', '<p class="proj-callout"$1>$2', (string) $project->content);
        // Tryb nawigacji: pasek zakładek albo menu boczne (ustawienie serwisu lub własny wybór działania).
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
            // „Dla kogo" bywa tym samym, co kategoria (taksonomia działań jest wg
            // odbiorcy, np. „Dla NGO"), więc pokazujemy je tylko, gdy wnosi coś ponad
            // nazwę kategorii — inaczej grupa docelowa dublowałaby się z plakietką.
            $showForWhom = $forWhom !== ''
                && mb_strtolower($forWhom) !== mb_strtolower($catName)
                && mb_strtolower($forWhom) !== mb_strtolower(trim(\Illuminate\Support\Str::after($catName, 'Dla ')));
        @endphp

    {{-- ══ NAGŁÓWEK (układ portalu): kategoria, tytuł, status i termin zwykłym tekstem, zajawka, zdjęcie — płasko, na białym ══ --}}
    @php $imageHero = $project->image_hero && $project->image_url; @endphp
    @if ($imageHero)
        {{-- Zdjęcie jako hero: baner na całą szerokość ekranu; nagłówek na białym polu nachodzącym na dolną krawędź zdjęcia
             (tytuł nigdy nie leży na samym zdjęciu — kontrast nie zależy od jego treści). --}}
        <div class="mt-4">
            <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: 'Zdjęcie ilustracyjne: '.$project->title }}" data-lightbox
                class="h-56 w-full object-cover sm:h-72 lg:h-[24rem]">
        </div>
    @endif
    <section>
        <div @class([
            'mx-auto grid max-w-6xl items-center gap-8 px-4 pb-4',
            'relative -mt-16 sm:-mt-24' => $imageHero,
            'pt-8 sm:pt-10' => ! $imageHero,
            'lg:grid-cols-[minmax(0,1fr)_26rem]' => $project->image_url && ! $imageHero,
        ])>
            <div @class(['min-w-0', 'max-w-3xl rounded-md bg-white p-6 sm:p-8' => $imageHero])>
                <a href="{{ route('categories.show', $project->category) }}" class="inline-block text-sm font-bold uppercase tracking-widest text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    {{ $project->category->name }}
                </a>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
                @if (($siteSettings->site_template ?? 'default') === 'feer')<span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
                @if ($siteSettings->projects_stages_enabled && ($project->status || $project->starts_on))
                    {{-- Status i termin jako zwykły tekst (bez plakietek). --}}
                    <dl class="proj-meta">
                        @if ($project->status)<div><dt>Status:</dt> <dd>{{ \App\Models\Project::STATUSES[$project->status] ?? '' }}</dd></div>@endif
                        @if ($project->starts_on)<div><dt>Termin:</dt> <dd>{{ $project->starts_on->locale('pl')->isoFormat('D MMMM YYYY') }}@if ($project->ends_on) – {{ $project->ends_on->locale('pl')->isoFormat('D MMMM YYYY') }}@endif</dd></div>@endif
                    </dl>
                @endif
                @php $extrasOn = $siteSettings->projects_extras_enabled; $mainCta = $extrasOn ? (array) ($project->main_cta ?? []) : []; @endphp
                @if ($project->excerpt)
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">{{ $project->excerpt }}</p>
                @endif
                <p class="mt-4 no-print">@include('partials.print-button')</p>
                @if (! empty($mainCta['label']) && ! empty($mainCta['url']))
                    <p class="mt-5"><a href="{{ $mainCta['url'] }}" class="proj-cta">{{ $mainCta['label'] }} <span aria-hidden="true">→</span></a></p>
                @endif
                @if ($showForWhom || $project->since)
                    <p class="mt-4 text-base text-ink">
                        @if ($showForWhom)<span @if ($canInlineEdit) data-inline-field="for_whom" data-inline-kind="text" data-inline-multiline @endif><i class="fa-solid fa-users mr-1.5 text-brand-dark" aria-hidden="true"></i>Dla kogo: <span class="font-bold">{{ $project->for_whom }}</span></span>@endif
                        @if ($showForWhom && $project->since)<span aria-hidden="true"> · </span>@endif
                        @if ($project->since)<span><i class="fa-solid fa-calendar-days mr-1.5 text-brand-dark" aria-hidden="true"></i>Od kiedy: <span class="font-bold">{{ $project->since }}</span></span>@endif
                    </p>
                @endif
            </div>

            @if ($project->image_url && ! $imageHero)
                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: 'Zdjęcie ilustracyjne: '.$project->title }}" data-lightbox
                    class="aspect-[4/3] w-full rounded-md object-cover">
            @endif
        </div>
    </section>

    <div x-data="{
            tabs: @js(array_column($tabItems, 'id')),
            tab: @js($openTab), node: @js($openTab !== 'opis' && $navSidebar ? $openNodeId : null), openIds: [],
            move(step) { const i = this.tabs.indexOf(this.tab); this.tab = this.tabs[(i + step + this.tabs.length) % this.tabs.length]; this.focusActive(); },
            jump(id) { this.tab = id; this.focusActive(); },
            focusActive() { this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); },
        }">
    @if ($hasTabs && ! $navSidebar)
        @include('partials.tab-strip', ['tabItems' => $tabItems, 'tabsLabel' => 'Sekcje działania', 'tabLinks' => $tabLinks])
    @endif

    <section class="mx-auto max-w-6xl px-4 py-8">
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
            /* Płasko (jak strona w układzie portalu): bez ramek, kresek, pigułek i gradientów — grupowanie tłem (szare / jasnoniebieskie),
               linki i akcenty w kolorze brand-dark (≥ 4,5:1 na bieli i na tłach), treść ink. Układ kolumn w zwykłym CSS (nie zależy od zbudowanych klas Tailwinda). */
            .proj-measure { max-width: 46rem; }
            .proj-measure.proj-flow > * + * { margin-top: 2.5rem; } /* wyższa swoistość: nagłówki .proj-h2 mają własny margines i nie mogą go zerować */
            .proj-prose { color: #1d1d1a; font-size: 1.0625rem; line-height: 1.75; font-weight: 500; }
            .proj-prose p, .proj-prose li { color: #1d1d1a; }
            .proj-prose p + p { margin-top: 1rem; }
            .proj-prose strong { font-weight: 700; }
            .proj-prose ul, .proj-prose ol { padding-left: 1.4rem; }
            .proj-prose li { margin-top: .5rem; padding-left: .25rem; }
            .proj-prose li::marker { color: var(--color-brand-dark); font-weight: 700; }
            .proj-prose a { color: var(--color-brand-dark); font-weight: 700; text-decoration: underline; text-underline-offset: 4px; }
            .proj-prose a:hover { text-decoration: none; }
            .proj-prose h2, .proj-prose h3 { color: #1d1d1a; font-weight: 800; line-height: 1.3; }
            .proj-prose h2 { margin: 2.5rem 0 1rem; font-size: 1.5rem; }
            .proj-prose h3 { margin: 1.75rem 0 .5rem; font-size: 1.2rem; }
            .proj-h2 { margin: 0 0 1rem; font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
            /* Komunikaty („Ważne:", „Uwaga:", wyśrodkowane akapity z edytora) — jasnoniebieskie tło zamiast ramki. */
            .proj-callout, .proj-prose p[style*="text-align: center"], .proj-prose p[style*="text-align:center"] {
                margin: 1.5rem 0; padding: 1rem 1.25rem; border-radius: .375rem; background: var(--color-brand-light); color: #1d1d1a; text-align: left !important; font-weight: 600;
            }
            .proj-note { position: relative; margin: 0 0 1rem; padding: 1rem 4rem 1rem 1.25rem; border-radius: .375rem; background: var(--color-brand-light); color: #1d1d1a; font-size: 1.0625rem; line-height: 1.6; font-weight: 600; }
            .proj-note-links { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: .75rem; }
            .proj-note-links a { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
            .proj-note-links a:hover { text-decoration: none; }
            .proj-note-links a:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 3px; border-radius: .25rem; }
            .proj-note-ico { position: absolute; top: 1rem; right: 1.25rem; color: var(--color-brand-dark); font-size: 1.25rem; }
            /* Sekcje informacyjne: zwykły nagłówek bez paska i ramki (ikona w nagłówku ukryta). */
            .proj-frame { margin-top: 2.5rem; padding: 0; }
            .proj-frame-side { margin-top: 0; padding: 1.25rem; border-radius: .375rem; background: #f9fafb; }
            .proj-frame-side .proj-frame-h { font-size: 1.125rem; }
            .proj-frame-h { display: block; margin: 0 0 1rem; font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
            .proj-frame-h i { display: none; }
            .proj-stages { list-style: none; margin: 0; padding: 0; display: grid; gap: .9rem; }
            .proj-stage { display: flex; gap: .85rem; }
            .proj-stage-dot { display: inline-flex; flex: none; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: .25rem; background: #e5e7eb; color: #1d1d1a; font-size: .7rem; }
            .proj-stage.is-done .proj-stage-dot { background: #166534; color: #fff; }
            .proj-stage.is-current .proj-stage-dot { background: var(--color-brand-dark); color: #fff; }
            .proj-stage-body { display: grid; gap: .1rem; }
            .proj-stage-title { font-weight: 800; color: #1d1d1a; } .proj-stage-state { font-weight: 600; color: #4b5563; font-size: .85em; }
            .proj-stage-date { font-size: .85rem; color: #374151; } .proj-stage-text { font-size: .95rem; line-height: 1.5; color: #1d1d1a; }
            .proj-team { list-style: none; margin: 0; padding: 0; display: grid; gap: .9rem 1.5rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
            .proj-team li { display: grid; gap: .1rem; } .proj-team-name { font-weight: 800; } .proj-team-role { font-size: .9rem; font-weight: 700; color: var(--color-brand-dark); } .proj-team-text { font-size: .9rem; line-height: 1.45; }
            .proj-partners { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr)); align-items: center; }
            .proj-partners img { display: block; max-height: 3.5rem; max-width: 100%; object-fit: contain; } .proj-partners a { display: block; } .proj-partners span { font-weight: 700; font-size: .9rem; }
            .proj-partners a:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
            .proj-fund { list-style: none; margin: 0 0 .75rem; padding: 0; display: grid; gap: .6rem; } .proj-fund-name { display: block; font-weight: 800; } .proj-fund-name a { color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; } .proj-fund-name a:hover { text-decoration: none; } .proj-fund-text { display: block; font-size: .9rem; line-height: 1.45; }
            .proj-fund-budget { margin: 0 0 .5rem; font-size: .95rem; } .proj-fund-notice { margin: .75rem 0 0; font-size: .85rem; line-height: 1.5; color: #1d1d1a; }
            .proj-menu-box { padding: 1.25rem; border-radius: .375rem; background: #f9fafb; }
            .proj-menu-title { margin: 0 0 .5rem; font-size: 1.125rem; font-weight: 800; color: #1d1d1a; }
            .proj-menu-list { list-style: none; margin: 0; padding: 0; }
            .proj-menu-list a { display: block; padding: .6rem 0; color: #1d1d1a; text-decoration: none; }
            .proj-menu-list a:hover .proj-menu-item { color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
            .proj-menu-list a:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; border-radius: .25rem; }
            .proj-menu-date { display: block; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #374151; }
            .proj-menu-item { display: block; font-size: 1rem; font-weight: 600; line-height: 1.4; }
            .proj-paid-info { font-weight: 500; }
            .proj-paid-info summary { cursor: pointer; font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
            .proj-paid-info summary:hover { text-decoration: none; } .proj-paid-info summary:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 3px; border-radius: .25rem; }
            .proj-paid-link { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
            .proj-paid-link:hover { text-decoration: none; } .proj-paid-link:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 3px; border-radius: .25rem; }
            .proj-paid-body { margin-top: .6rem; padding: 1rem 1.25rem; border-radius: .375rem; background: #f9fafb; font-size: .95rem; line-height: 1.6; font-weight: 500; }
            .proj-paid-body p { margin: 0 0 .6rem; } .proj-paid-body p:last-child { margin-bottom: 0; }
            /* Cennik: wiersze rozdzielone tłem (pasy), nagłówek na jasnoniebieskim — bez linii. */
            .proj-price-wrap { overflow-x: auto; border-radius: .375rem; background: #fff; }
            .proj-price-table { width: 100%; border-collapse: collapse; font-size: 1rem; color: #1d1d1a; }
            .proj-price-table th, .proj-price-table td { padding: .7rem .9rem; text-align: left; vertical-align: top; }
            .proj-price-table thead th { background: var(--color-brand-light); font-weight: 800; }
            .proj-price-table tbody tr:nth-child(even) > * { background: #f9fafb; }
            .proj-price-item { font-weight: 700; }
            .proj-price-note { font-size: .92rem; line-height: 1.45; }
            .proj-price-amount { font-weight: 800; white-space: nowrap; }
            @media print {
                .proj-price-wrap { overflow: visible; border: 1px solid #000; border-radius: 0; }
                .proj-price-table { font-size: 11pt; }
                .proj-price-table th, .proj-price-table td { padding: .4rem .6rem; border-bottom: 1px solid #000; }
                .proj-price-table thead th { background: none; border-bottom: 2px solid #000; }
                .proj-price-table tr, .proj-price-table td, .proj-price-table th { break-inside: avoid; }
                .proj-price-amount { white-space: normal; }
            }
            .proj-terms { margin: 0; display: grid; gap: .5rem; }
            .proj-terms-row { display: grid; gap: .15rem 1rem; grid-template-columns: minmax(8rem, 12rem) 1fr; padding: .75rem 1rem; border-radius: .375rem; background: #f9fafb; }
            .proj-terms-row dt { font-weight: 800; color: #1d1d1a; } .proj-terms-row dd { margin: 0; line-height: 1.5; color: #1d1d1a; }
            @media (max-width: 40rem) { .proj-terms-row { grid-template-columns: 1fr; } }
            .proj-cta { display: inline-flex; align-items: center; gap: .6rem; min-height: 3rem; padding: .6rem 1.4rem; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-size: 1.05rem; font-weight: 800; text-decoration: none; }
            .proj-cta:hover { background: #1d1d1a; } .proj-cta:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 3px; }
            .proj-easy-text { margin: 0; padding: 1rem 1.25rem; border-radius: .375rem; background: #f9fafb; font-size: 1.15rem; line-height: 1.7; font-weight: 600; color: #1d1d1a; }
            .proj-metrics { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); }
            .proj-metrics li { display: grid; gap: .15rem; text-align: center; padding: .75rem .5rem; border-radius: .375rem; background: #f9fafb; }
            .proj-metric-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: var(--color-brand-dark); } .proj-metric-label { font-size: .9rem; line-height: 1.35; color: #1d1d1a; }
            .proj-tests { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; }
            .proj-tests blockquote { margin: 0; padding: 1rem 1.25rem; border-radius: .375rem; background: #f9fafb; } .proj-tests blockquote p { margin: 0; font-size: 1.05rem; line-height: 1.6; font-style: italic; color: #1d1d1a; } .proj-tests footer { margin-top: .35rem; font-size: .9rem; font-weight: 700; color: #374151; }
            .proj-faq { display: grid; gap: .5rem; }
            .proj-faq details { border-radius: .375rem; background: #f9fafb; }
            .proj-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 3rem; padding: .5rem 1rem; cursor: pointer; list-style: none; font-weight: 800; color: #1d1d1a; } .proj-faq summary::-webkit-details-marker { display: none; }
            .proj-faq summary:hover { color: var(--color-brand-dark); } .proj-faq summary:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; border-radius: .375rem; }
            .proj-faq details[open] summary i { transform: rotate(180deg); } .proj-faq summary i { flex: none; font-size: .8rem; transition: transform .15s; }
            .proj-faq-a { padding: 0 2rem .9rem 1rem; line-height: 1.6; color: #1d1d1a; }
            @media (prefers-reduced-motion: reduce) { .proj-faq summary i { transition: none; } }
            /* Grupy w kolumnie bocznej i proste odnośniki (zamiast kafli z ramką). */
            .proj-box { padding: 1.25rem; border-radius: .375rem; background: #f9fafb; }
            .proj-box.is-brand { background: var(--color-brand-light); }
            .proj-box-h { display: flex; align-items: center; gap: .5rem; margin: 0 0 .75rem; font-size: 1.125rem; font-weight: 800; color: #1d1d1a; }
            .proj-box-h i { color: var(--color-brand-dark); }
            .proj-link { font-weight: 700; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
            .proj-link:hover { text-decoration: none; } .proj-link:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; border-radius: .25rem; }
            .proj-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 2.75rem; padding: .5rem 1.25rem; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-weight: 800; text-decoration: none; }
            .proj-btn:hover { background: #1d1d1a; } .proj-btn:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
            .proj-meta { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; margin: .75rem 0 0; color: #1d1d1a; font-size: .95rem; }
            .proj-meta dt { display: inline; font-weight: 400; color: #4b5563; } .proj-meta dd { display: inline; margin: 0; font-weight: 700; }
            @media (min-width: 1024px) {
                .proj-cols { grid-template-columns: minmax(0, 1fr) 20rem; }
                .proj-cols > .proj-main { grid-column: 1; grid-row: 1 / span 3; }
                .proj-cols > .proj-nav, .proj-cols > .proj-aside { grid-column: 2; }
                .proj-aside { position: sticky; top: 6rem; }
            }
        </style>
        <div @class(['grid items-start gap-10', 'proj-cols' => $hasAside || $navSidebar])>
        <div class="min-w-0 proj-main">
            <div id="panel-opis" @if ($hasTabs && ! $navSidebar) role="tabpanel" aria-labelledby="tab-opis" @endif x-show="tab === 'opis'" class="proj-measure proj-flow">
                @if ($project->isPaidOffer())
                    {{-- Usługa wyłącznie odpłatna: wyraźna informacja, objaśnienie i przycisk kontaktu. --}}
                    <div class="proj-note">
                        <span>Z tej usługi skorzystasz <strong>za opłatą</strong>. Cenę i zasady znajdziesz poniżej.</span>
                        @if ($rulesPage = $siteSettings->paidInfoPage())
                            <span class="proj-note-links"><a href="{{ route('page.show', $rulesPage) }}">Zasady odpłatnej działalności <span aria-hidden="true">→</span><span class="sr-only">: {{ $rulesPage->title }}</span></a></span>
                        @endif
                        <span class="proj-note-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                    </div>
                @endif
                @if ($siteSettings->projects_subprojects_enabled && $project->is_paid && $project->parent_id)
                    <div class="proj-note"><span>Ta forma jest <strong>płatna</strong>.</span>
                        @if ($rulesPage = $siteSettings->paidInfoPage())
                            <span class="proj-note-links"><a href="{{ route('page.show', $rulesPage) }}">Zasady odpłatnej działalności <span aria-hidden="true">→</span><span class="sr-only">: {{ $rulesPage->title }}</span></a></span>
                        @endif
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
                                @if ($rulesPage = $siteSettings->paidInfoPage())
                                    <a href="{{ route('page.show', $rulesPage) }}">Zasady odpłatnej działalności <span aria-hidden="true">→</span><span class="sr-only">: {{ $rulesPage->title }}</span></a>
                                @endif
                            </span>
                            <span class="proj-note-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
                        </div>
                    </section>
                @endif
                @if ($sectionTabs->isNotEmpty())
                    {{-- Sekcje własne są w zakładkach (pasek pod nagłówkiem) --}}
                @else
                    @foreach ($featuredSections as $section)
                        <div class="mb-8 rounded-md bg-brand-light p-6">
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
                    <div class="mb-8 rounded-md bg-brand-light p-5">
                        <div class="flex items-start gap-4">
                            <i class="fa-solid fa-clock-rotate-left mt-1 text-xl text-brand-dark" aria-hidden="true"></i>
                            <div>
                                <p class="text-base font-bold text-ink">To działanie realizowaliśmy przed uruchomieniem nowej strony.</p>
                                @if ($project->legacy_url)
                                    <a href="{{ $project->legacy_url }}" target="_blank" rel="noopener"
                                        class="proj-btn mt-3">
                                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz informacje o projekcie
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if ($project->content)
                    <h2 class="proj-h2">Opis działania</h2>
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
                        <h2 id="proj-stages-h" class="proj-frame-h"><i class="fa-solid fa-timeline" aria-hidden="true"></i> Etapy działania</h2>
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
                        <h2 id="proj-team-h" class="proj-frame-h"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Zespół działania</h2>
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
                    <section id="{{ $anchor ?: 'podstrona-sekcja-'.$subpage->id }}" class="mt-8 scroll-mt-24 rounded-md bg-gray-50 p-6">
                        <h2 class="proj-h2">{{ $subpage->title }}</h2>
                        @if ($subpage->content)
                            <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
                        @endif
                        @if ($subpage->isSchedule())
                            @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
                        @elseif ($subpage->isFaq())
                            @include('partials.faq', ['page' => $subpage])
                        @endif
                    </section>
                @endforeach

                {{-- Podstrony działania jako rozwijane sekcje (akordeon) --}}
                @if ($accordionPages->isNotEmpty())
                    <span id="projekt-rozwijane" class="scroll-mt-24"></span>
                    @include('partials.page-children-accordion', ['children' => $accordionPages, 'page' => (object) ['id' => 'p'.$project->id, 'title' => $project->title]])
                @endif

                {{-- Project subpages shown as tabs --}}
                @if ($project->outcomes)
                    <div class="mt-8 rounded-md bg-gray-50 p-6">
                        <h2 class="proj-h2">Co udało się osiągnąć</h2>
                        <div class="prose proj-prose max-w-none">{!! $project->outcomes !!}</div>
                    </div>
                @endif

                @php $pricing = collect($project->pricing ?? [])->filter(fn ($p) => filled($p['item'] ?? null) || filled($p['price'] ?? null)); @endphp
                @if ($project->is_paid && $pricing->isNotEmpty())
                    {{-- Cennik jako tabela: pozycja, opis i cena. --}}
                    <section class="proj-frame proj-price" aria-labelledby="proj-price-h">
                        <h2 id="proj-price-h" class="proj-frame-h"><i class="fa-solid fa-coins" aria-hidden="true"></i> Cennik</h2>
                        <div class="proj-price-wrap">
                            <table class="proj-price-table" aria-labelledby="proj-price-h">
                                <thead>
                                    <tr>
                                        <th scope="col">Pozycja</th>
                                        <th scope="col">Opis</th>
                                        <th scope="col">Cena</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pricing as $row)
                                        <tr>
                                            <th scope="row" class="proj-price-item">{{ $row['item'] ?? '' }}</th>
                                            <td class="proj-price-note">{{ $row['note'] ?? '' }}</td>
                                            <td class="proj-price-amount">{{ $row['price'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

            </div>{{-- /panel-opis --}}

            {{-- Panele pozostałych zakładek --}}
            @foreach ($sectionTabs as $i => $section)
                <div id="panel-sekcja-{{ $i }}" role="tabpanel" aria-labelledby="tab-sekcja-{{ $i }}" tabindex="0" x-cloak
                     x-show="tab === 'sekcja-{{ $i }}'" class="prose proj-prose max-w-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-dark">
                    @if (! empty($section['title']))<h2 class="mb-3 text-xl font-bold text-ink">{{ $section['title'] }}</h2>@endif
                    {!! $section['content'] ?? '' !!}
                </div>
            @endforeach
            @foreach ($tabPages as $i => $subpage)
                <div id="panel-podstrona-{{ $i }}" role="tabpanel" aria-labelledby="tab-podstrona-{{ $i }}" tabindex="0" x-cloak
                     x-show="tab === 'podstrona-{{ $i }}'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-dark">
                    @include('projects.partials.tab-page', ['root' => $subpage, 'sidebarMode' => $navSidebar, 'openNodeId' => $openNodeId])
                </div>
            @endforeach
            </div>

            {{-- ══ PANEL BOCZNY: harmonogram, kontakt, strony działania, powrót ══ --}}
            @if ($navSidebar)
                @include('projects.partials.sidebar-nav', ['sectionTabs' => $sectionTabs, 'tabPages' => $tabPages, 'tabLinks' => $tabLinks])
            @endif
            @if ($hasAside)
            <aside class="proj-aside space-y-5" aria-label="Informacje o projekcie">
        @if ($schedulePage)
            <div class="proj-box is-brand">
                <h2 class="proj-box-h"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $schedulePage->title }}</h2>
                <p class="mb-4 text-ink">Sprawdź terminy zajęć i spotkań w ramach tego projektu.</p>
                <a href="{{ $scheduleHref }}" class="proj-btn w-full">
                    Zobacz harmonogram <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif

                {{-- Kontakt w sprawie działania — szare pole w kolumnie bocznej, dane zwykłym tekstem i odnośnikami. --}}
                @if (! $project->is_completed && $project->showsCoordinator())
                    <div class="proj-box">
                        <h2 class="proj-box-h">
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i> Kontakt w sprawie działania
                        </h2>
                        <div class="space-y-1.5 text-ink">
                            @if ($project->coordinator_name)
                                <p class="font-bold">{{ $project->coordinator_name }}</p>
                            @endif
                            <p>
                                <a href="mailto:{{ $project->contactEmail() }}" class="proj-link break-all">{{ $project->contactEmail() }}</a>
                            </p>
                            @if ($project->coordinator_phone)
                                <p>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $project->coordinator_phone) }}" class="proj-link">{{ $project->coordinator_phone }}</a>
                                </p>
                                @if (filled($project->coordinator_phone_hours))
                                    <p class="text-sm">Telefon czynny {{ $project->coordinator_phone_hours }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                @if ($linkPages->isNotEmpty())
                    <div class="proj-box">
                        <h2 class="proj-box-h">
                            <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Strony działania
                        </h2>
                        <ul class="flex flex-col gap-3">
                            @foreach ($linkPages as $projectPage)
                                <li>
                                    @if ($projectPage->isSchedule())
                                        {{-- Harmonogram wyróżnia się jako przycisk-wezwanie do działania. --}}
                                        <a href="{{ route('page.show', $projectPage) }}" class="proj-btn">
                                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> {{ $projectPage->title }}
                                        </a>
                                    @else
                                        <a href="{{ route('page.show', $projectPage) }}" class="proj-link">{{ $projectPage->title }}</a>
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
                            <p class="proj-fund-budget">Budżet działania: <strong>{{ $projFunding['budget'] }}</strong></p>
                        @endif
                        @if (filled($project->funding_notice))
                            <p class="proj-fund-notice">{{ $project->funding_notice }}</p>
                        @endif
                    </section>
                @endif

                @if ($showNews)
                    {{-- Aktualności działania: szare pole, tytuł i lista wpisów (bez kresek i linii). --}}
                    <nav class="proj-menu-box" aria-labelledby="proj-news-h">
                        <p id="proj-news-h" class="proj-menu-title">Aktualności działania</p>
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
