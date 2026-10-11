{{--
    Jedna gałąź drzewa działu (partials/page-tree-nav) — wywoływana rekurencyjnie.

    Zmienne: $nodes (kolekcja Page), $depth (1 = podstrony korzenia),
             $rootlineIds (id stron na ścieżce korzeń → bieżąca), $page (bieżąca).

    Gałąź jest rozwinięta, gdy leży na ścieżce do bieżącej strony lub jest
    bieżącą stroną (pokazujemy jej podstrony). Pozostałe gałęzie zwijamy —
    jak w drzewie stron TYPO3 — ale bez JS pozostają widoczne.
--}}
@php
    $branchIndent = ['', 'pl-3', 'pl-6', 'pl-9', 'pl-12', 'pl-14'][min($depth, 5)];
@endphp

<ul role="list" class="mt-0.5 space-y-0.5 {{ $depth === 1 ? 'ml-3' : 'ml-2' }}">
    @foreach ($nodes as $node)
        @php
            $isCurrent = $node->is($page);
            $onRootline = in_array($node->id, $rootlineIds, true);
            $branch = $depth < \App\Models\Page::TREE_NAV_MAX_DEPTH ? $node->publishedChildren : collect();
            $hasBranch = $branch->isNotEmpty();
            $branchId = 'tree-branch-' . $node->id;
        @endphp
        <li @if ($hasBranch) x-data="{ open: {{ $onRootline ? 'true' : 'false' }} }" @endif>
            <div class="flex items-stretch gap-0.5 pl-2">
                @if ($node->isMenuHeading())
                    <p class="flex min-h-11 min-w-0 flex-1 items-center px-2.5 py-2 text-xs font-bold uppercase tracking-wide text-muted">{{ $node->title }}</p>
                @else
                <a href="{{ $node->navUrl() }}" {!! $node->navLinkAttributes() !!} @if ($isCurrent) aria-current="page" @endif
                    class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-md px-2.5 py-2 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark
                        {{ $isCurrent ? 'bg-brand-light font-bold text-brand-dark' : ($onRootline ? 'font-semibold ' : '') . 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4' }}">
                    <i class="fa-solid {{ $hasBranch ? 'fa-folder' : 'fa-file-lines' }} w-4 flex-none text-center text-xs {{ $isCurrent ? 'text-brand-dark' : 'text-muted' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 leading-snug">{{ $node->title }}{!! $node->navNewTabNotice() !!}</span>
                </a>
                @endif

                @if ($hasBranch)
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $onRootline ? 'true' : 'false' }}"
                        aria-controls="{{ $branchId }}"
                        aria-label="{{ $onRootline ? 'Zwiń' : 'Rozwiń' }}: {{ $node->title }} ({{ $branch->count() }} {{ trans_choice('podstrona|podstrony|podstron', $branch->count()) }})"
                        :aria-label="(open ? 'Zwiń' : 'Rozwiń') + ': {{ addslashes($node->title) }} ({{ $branch->count() }} {{ trans_choice('podstrona|podstrony|podstron', $branch->count()) }})'"
                        class="flex min-h-11 w-9 flex-none items-center justify-center rounded-md text-xs font-bold text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <span class="inline-flex items-center justify-center gap-0.5 font-mono text-xs leading-none">
                            <span x-text="open ? '−' : '+'" aria-hidden="true">{{ $onRootline ? '−' : '+' }}</span><span aria-hidden="true">{{ $branch->count() }}</span>
                        </span>
                    </button>
                @endif
            </div>

            @if ($hasBranch)
                <div id="{{ $branchId }}" x-show="open">
                    @include('partials.page-tree-nav-branch', [
                        'nodes'       => $branch,
                        'depth'       => $depth + 1,
                        'rootlineIds' => $rootlineIds,
                        'page'        => $page,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>
