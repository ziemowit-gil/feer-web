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
@endphp

@if (($variant ?? null) === 'project')
{{-- Wygląd menu jak na stronie projektu: szare pole, kreska w kolorze marki, tytuł i pozycje oddzielone cienkimi liniami. --}}
<nav aria-label="Podstrony w tym dziale" class="relative bg-gray-100 p-6">
    <span class="absolute block bg-brand" style="left:0;top:0;height:.25rem;width:4rem" aria-hidden="true"></span>
    <p class="mb-4 border-b border-gray-900 pb-3 text-lg font-bold text-ink">{{ $localHeading }}</p>
    <ul role="list" class="text-ink">
        @if ($localUp)
            <li class="border-b border-gray-300">
                <a href="{{ $localUp['url'] }}" @if (! empty($localUp['current'])) aria-current="page" @endif
                    class="flex w-full items-center py-3 text-base font-normal hover:text-brand focus-visible:outline-2 focus-visible:outline-brand {{ ! empty($localUp['current']) ? 'text-brand' : '' }}">{{ $localUp['label'] }}</a>
            </li>
        @endif
        @foreach ($menuSiblings as $sibling)
            @php $kids = $sibling->publishedChildren; @endphp
            <li class="border-b border-gray-300 last:border-0">
                <a href="{{ $sibling->publicUrl() }}" @if ($sibling->is($page)) aria-current="page" @endif
                    class="flex w-full items-center justify-between gap-2 py-3 text-base font-normal hover:text-brand focus-visible:outline-2 focus-visible:outline-brand {{ $sibling->is($page) ? 'text-brand' : '' }}">
                    <span>{{ $sibling->title }}</span>
                    @if ($kids->isNotEmpty())<i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>@endif
                </a>
                @if ($kids->isNotEmpty() && ($sibling->is($page) || $kids->contains(fn ($k) => $k->is($page))))
                    <ul role="list" class="border-t border-gray-300">
                        @foreach ($kids as $child)
                            <li class="border-b border-gray-300 last:border-0">
                                <a href="{{ $child->publicUrl() }}" @if ($child->is($page)) aria-current="page" @endif
                                    class="flex py-2.5 pl-3 text-[0.9375rem] font-normal hover:text-brand focus-visible:outline-2 focus-visible:outline-brand {{ $child->is($page) ? 'text-brand' : '' }}">{{ $child->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
@else
<aside aria-label="Podstrony w tym dziale" class="md:border-l md:border-gray-200 md:pl-6">
    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">{{ $localHeading }}</p>
    <ul class="space-y-1 text-sm">
        @if ($localUp)
            <li>
                <a href="{{ $localUp['url'] }}"
                    {{ ! empty($localUp['current']) ? 'aria-current=page' : '' }}
                    class="block rounded px-2 py-1.5 {{ ! empty($localUp['current']) ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-50' }}">
                    {{ $localUp['label'] }}
                </a>
            </li>
        @endif
        @foreach ($menuSiblings as $sibling)
            <li>
                <a href="{{ $sibling->publicUrl() }}"
                    {{ $sibling->is($page) ? 'aria-current=page' : '' }}
                    class="block rounded px-2 py-1.5 {{ $sibling->is($page) ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-50' }}">
                    {{ $sibling->title }}
                </a>

                {{-- Drzewo: opublikowane podstrony rodzeństwa jako zagnieżdżona gałąź. --}}
                @php $childBranch = $sibling->publishedChildren; @endphp
                @if ($childBranch->isNotEmpty())
                    <ul class="mt-1 space-y-1 border-l border-gray-200 pl-3">
                        @foreach ($childBranch as $child)
                            <li>
                                <a href="{{ $child->publicUrl() }}"
                                    {{ $child->is($page) ? 'aria-current=page' : '' }}
                                    class="block rounded px-2 py-1 text-[0.8rem] {{ $child->is($page) ? 'bg-brand-light font-bold text-brand' : 'text-muted hover:bg-gray-50 hover:text-ink' }}">
                                    {{ $child->title }}
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
