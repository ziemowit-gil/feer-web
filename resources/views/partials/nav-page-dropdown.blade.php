@php $mobile ??= false; $transparent ??= false; @endphp

<li class="relative" x-data="{ open: false }"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusin="open = true"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.pageTrigger.focus()"
    @click.outside="open = false">
    <a href="{{ route('page.show', $page) }}" x-ref="pageTrigger" @click.prevent="open = !open"
        aria-haspopup="true" :aria-expanded="open.toString()"
        class="flex w-full items-center gap-1 border-b-2 py-2 uppercase transition-colors hover:border-brand hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ request()->routeIs('page.show') && (request()->route('page')?->id === $page->id || request()->route('page')?->parent_id === $page->id) ? 'border-brand text-brand' : 'border-transparent' }} {{ $mobile ? 'justify-between' : 'pb-1' }}" :class="open ? 'border-brand text-brand' : ''">
        {{ $page->title }} <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
    </a>

    <div x-show="open" x-cloak x-transition
        @class([
            'z-50 rounded-lg border border-gray-200 py-2 normal-case tracking-normal shadow-lg',
            'bg-white/90 backdrop-blur-sm' => $transparent,
            'bg-white' => ! $transparent,
            'absolute left-0 top-full mt-1 w-60' => ! $mobile,
            'static mt-1 w-full' => $mobile,
        ])>
        <a href="{{ route('page.show', $page) }}" class="block px-4 py-2 text-sm font-bold normal-case text-ink hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
            {{ $page->title }} (strona główna)
        </a>
        @foreach ($page->publishedChildren as $child)
            <a href="{{ route('page.show', $child) }}" class="block px-4 py-2 text-sm font-medium normal-case {{ request()->routeIs('page.show') && request()->route('page')?->id === $child->id ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                {{ $child->title }}
            </a>
        @endforeach
    </div>
</li>
