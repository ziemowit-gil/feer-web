@if ($page->sideNavStyle() === 'tree')
    {{-- Styl „drzewo działu" (TYPO3): pełne, wielopoziomowe drzewo od korzenia działu. --}}
    @include('partials.page-tree-nav', ['menuSiblings' => $menuSiblings])
@else
@php
    // The heading and "up" link for a page's local sub-menu: a project it is
    // attached to takes precedence, then a parent page. $menuSiblings is passed
    // in by the caller (App\Models\Page::menuSiblings()).
    $localUp = null;
    $localHeading = $page->title;

    if ($page->project) {
        $localHeading = $page->project->title;
        $localUp = ['label' => $page->project->title, 'url' => route('projects.show', $page->project)];
    } elseif ($page->parent) {
        $localHeading = $page->parent->title;
        $localUp = ['label' => $page->parent->title, 'url' => route('page.show', $page->parent), 'current' => $page->parent->is($page)];
    }

    // Płaski styl menu działu: pozycje jako pełnej szerokości linki z szewronem,
    // odstępy zamiast linii; bieżąca strona tłem marki, pogrubieniem i aria-current.
    $panelStyle = ($variant ?? null) === 'project' || $page->sideNavStyle() === 'panel';
    $localItem = fn (bool $current) => 'flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-base transition '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark '
        . ($current ? 'bg-brand-light font-bold text-brand-dark' : 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4');
    $localChild = fn (bool $current) => 'flex min-h-10 w-full items-center justify-between gap-3 rounded-md px-3 py-1.5 text-[0.9375rem] transition '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark '
        . ($current ? 'bg-brand-light font-bold text-brand-dark' : 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4');
@endphp

@if ($panelStyle)
{{-- Wygląd menu jak na stronie działania: szare pole, kreska w kolorze marki, tytuł działu i lista linków. --}}
<nav aria-label="Podstrony w tym dziale" class="rounded-md bg-gray-50 p-5">
    <p class="text-lg font-bold leading-snug text-ink">{{ $localHeading }}</p>
    <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    <ul role="list" class="space-y-1">
        @if ($localUp)
            <li>
                <a href="{{ $localUp['url'] }}" @if (! empty($localUp['current'])) aria-current="page" @endif
                    class="{{ $localItem(! empty($localUp['current'])) }}">
                    <span class="min-w-0">{{ $localUp['label'] }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </a>
            </li>
        @endif
        @foreach ($menuSiblings as $sibling)
            @php $kids = $sibling->publishedChildren; @endphp
            <li>
                <a href="{{ $sibling->publicUrl() }}" @if ($sibling->is($page)) aria-current="page" @endif
                    class="{{ $localItem($sibling->is($page)) }}">
                    <span class="min-w-0">{{ $sibling->title }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </a>
                @if ($kids->isNotEmpty() && ($sibling->is($page) || $kids->contains(fn ($k) => $k->is($page))))
                    <ul role="list" class="mt-1 space-y-1 pl-4">
                        @foreach ($kids as $child)
                            <li>
                                <a href="{{ $child->publicUrl() }}" @if ($child->is($page)) aria-current="page" @endif
                                    class="{{ $localChild($child->is($page)) }}">
                                    <span class="min-w-0">{{ $child->title }}</span>
                                    <i class="fa-solid fa-chevron-right flex-none text-[0.625rem]" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@else
<aside aria-label="Podstrony w tym dziale" class="rounded-md bg-gray-50 p-5">
    <p class="text-base font-bold leading-snug text-ink">{{ $localHeading }}</p>
    <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    <ul role="list" class="space-y-1">
        @if ($localUp)
            <li>
                <a href="{{ $localUp['url'] }}"
                    {{ ! empty($localUp['current']) ? 'aria-current=page' : '' }}
                    class="{{ $localItem(! empty($localUp['current'])) }}">
                    <span class="min-w-0">{{ $localUp['label'] }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </a>
            </li>
        @endif
        @foreach ($menuSiblings as $sibling)
            <li>
                <a href="{{ $sibling->publicUrl() }}"
                    {{ $sibling->is($page) ? 'aria-current=page' : '' }}
                    class="{{ $localItem($sibling->is($page)) }}">
                    <span class="min-w-0">{{ $sibling->title }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </a>

                {{-- Drzewo: opublikowane podstrony rodzeństwa jako zagnieżdżona gałąź (wcięcie zamiast linii). --}}
                @php $childBranch = $sibling->publishedChildren; @endphp
                @if ($childBranch->isNotEmpty())
                    <ul role="list" class="mt-1 space-y-1 pl-4">
                        @foreach ($childBranch as $child)
                            <li>
                                <a href="{{ $child->publicUrl() }}"
                                    {{ $child->is($page) ? 'aria-current=page' : '' }}
                                    class="{{ $localChild($child->is($page)) }}">
                                    <span class="min-w-0">{{ $child->title }}</span>
                                    <i class="fa-solid fa-chevron-right flex-none text-[0.625rem]" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</aside>
@endif
@endif
