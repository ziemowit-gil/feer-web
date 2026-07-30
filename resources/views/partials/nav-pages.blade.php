@php $mobile ??= false; @endphp

@foreach (($navPages ?? collect()) as $page)
    @if ($page->publishedChildren->isNotEmpty())
        @include('partials.nav-page-dropdown', ['page' => $page, 'mobile' => $mobile, 'transparent' => $item->is_transparent_dropdown])
    @else
        <li>
            <a href="{{ route('page.show', $page) }}"
                {{ request()->routeIs('page.show') && request()->route('page')?->id === $page->id ? 'aria-current="page"' : '' }}
                class="block border-b-2 py-2 transition-colors hover:border-brand hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ request()->routeIs('page.show') && request()->route('page')?->id === $page->id ? 'border-brand text-brand' : 'border-transparent' }} {{ $mobile ? '' : 'pb-1' }}">
                {{ $page->title }}
            </a>
        </li>
    @endif
@endforeach
