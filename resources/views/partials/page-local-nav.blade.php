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
                <a href="{{ route('page.show', $sibling) }}"
                    {{ $sibling->is($page) ? 'aria-current=page' : '' }}
                    class="block rounded px-2 py-1.5 {{ $sibling->is($page) ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-50' }}">
                    {{ $sibling->title }}
                </a>
            </li>
        @endforeach
    </ul>
</aside>
