{{--
    Drzewo działu — nawigacja po podstronach w stylu TYPO3, włączana na stronie
    nadrzędnej działu polem `side_nav_style = tree` (Page::SIDE_NAV_STYLES).

    W odróżnieniu od bocznej listy rodzeństwa (partials/page-local-nav) pokazuje
    cały dział od jego korzenia: stronę działu jako nagłówek, a pod nią wszystkie
    opublikowane podstrony na dowolnej głębokości (do Page::TREE_NAV_MAX_DEPTH).
    Gałęzie na ścieżce do bieżącej strony (rootline) oraz jej własne podstrony
    są rozwinięte, pozostałe zwinięte za przyciskiem +/−. Nad drzewem widnieje
    ścieżka „Jesteś tu" — odpowiednik rootline TYPO3.

    Strona podpięta pod działanie (bez rodzica): korzeniem jest działanie, a
    pierwszym poziomem jego opublikowane strony.

    Zmienne: $page, $menuSiblings (App\Models\Page::menuSiblings()).

    WCAG: nawigacja z listami zagnieżdżonymi (nie widżet ARIA tree — to zwykłe
    linki), bieżąca strona aria-current="page", przyciski rozwijania mają
    aria-expanded + aria-controls i etykietę z tytułem gałęzi (4.1.2), cele
    ≥ 44 px (2.5.8). Bez JS wszystkie gałęzie pozostają widoczne.
--}}
@php
    $treeRootIsProject = (bool) ($page->project && ! $page->parent_id);
    $treeRoot = $treeRootIsProject ? null : $page->sectionRoot();

    $treeRootLabel = $treeRootIsProject ? $page->project->title : $treeRoot->title;
    $treeRootUrl = $treeRootIsProject ? route('projects.show', $page->project) : $treeRoot->publicUrl();
    $treeRootCurrent = ! $treeRootIsProject && $treeRoot->is($page);

    $treeNodes = $treeRootIsProject ? $menuSiblings : $treeRoot->publishedChildren;

    // Identyfikatory stron na ścieżce korzeń → bieżąca (łącznie z bieżącą):
    // te gałęzie są rozwinięte domyślnie.
    $treeRootlineIds = $page->ancestors()->pluck('id')->push($page->id)->all();

    $treeCrumbs = $treeRootIsProject
        ? collect([['label' => $page->project->title, 'url' => route('projects.show', $page->project)]])
        : $page->ancestors()->map(fn ($a) => ['label' => $a->title, 'url' => $a->publicUrl()]);
@endphp

<nav aria-label="Podstrony w tym dziale" class="page-tree-nav text-sm md:border-r md:border-gray-200 md:pr-6">
    <p class="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-muted">
        <i class="fa-solid fa-sitemap text-brand-dark" aria-hidden="true"></i> W tym dziale
    </p>

    @if ($treeCrumbs->isNotEmpty())
        <p class="mb-3 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed text-muted">
            <span class="font-bold">Jesteś tu:</span>
            @foreach ($treeCrumbs as $crumb)
                <a href="{{ $crumb['url'] }}" class="rounded text-ink underline-offset-2 hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $crumb['label'] }}</a>
                <span aria-hidden="true" class="mx-0.5 text-gray-400">›</span>
            @endforeach
            <span class="font-semibold text-ink" aria-current="page">{{ $page->title }}</span>
        </p>
    @endif

    <ul role="list" class="space-y-0.5">
        <li>
            <a href="{{ $treeRootUrl }}" @if ($treeRootCurrent) aria-current="page" @endif
                class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $treeRootCurrent ? 'bg-brand text-white' : 'text-ink hover:bg-brand-light hover:text-brand-dark' }}">
                <i class="fa-solid {{ $treeRootIsProject ? 'fa-diagram-project' : 'fa-house' }} w-4 text-center text-xs opacity-70" aria-hidden="true"></i>
                <span class="min-w-0 flex-1">{{ $treeRootLabel }}</span>
            </a>

            @if ($treeNodes->isNotEmpty())
                @include('partials.page-tree-nav-branch', [
                    'nodes'       => $treeNodes,
                    'depth'       => 1,
                    'rootlineIds' => $treeRootlineIds,
                    'page'        => $page,
                ])
            @endif
        </li>
    </ul>
</nav>
