@php $mobile ??= false; $transparent ??= false; $label ??= $page->title; @endphp

<li class="relative" x-data="{ open: false }" x-id="['dropdown']"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.pageTrigger.focus()"
    @click.outside="open = false">
    @php
        $triggerCurrent = request()->routeIs('page.show') && (request()->route('page')?->id === $page->id || request()->route('page')?->parent_id === $page->id);
    @endphp
    <div class="flex items-center gap-1 border-b-2 transition-colors {{ $triggerCurrent ? 'border-brand text-brand' : 'border-transparent' }} {{ $mobile ? 'w-full justify-between' : 'pb-1' }}"
        :class="open ? 'border-brand text-brand' : ''">
        {{-- Nagłówek działa jak zwykły link w headerze — klik prowadzi do strony. --}}
        <a href="{{ route('page.show', $page) }}" x-ref="pageTrigger"
            class="uppercase transition-colors hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $mobile ? 'py-2' : 'pt-2' }}">
            {{ $label }}
        </a>
        {{-- Osobna strzałka rozwija/zamyka podmenu (działa też na mobile). --}}
        <button type="button" @click="open = !open" x-ref="pageToggle"
            :aria-expanded="open.toString()" :aria-controls="$id('dropdown')" aria-label="Rozwiń podmenu: {{ $label }}"
            class="flex items-center px-1 text-brand hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $mobile ? 'py-2' : 'pt-2' }}">
            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
        </button>
    </div>

    <div :id="$id('dropdown')" x-show="open" x-cloak x-transition
        @class([
            'z-50 rounded-lg border border-gray-200 py-2 normal-case tracking-normal shadow-lg',
            'bg-white/90 backdrop-blur-sm' => $transparent,
            'bg-white' => ! $transparent,
            'absolute left-0 top-full mt-1 w-60' => ! $mobile,
            'static mt-1 w-full' => $mobile,
        ])>
        @foreach ($page->publishedChildren as $child)
            <a href="{{ route('page.show', $child) }}" class="block px-4 py-2 text-sm font-medium normal-case {{ request()->routeIs('page.show') && request()->route('page')?->id === $child->id ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                {{ $child->title }}
            </a>
        @endforeach

        {{-- Strona „O organizacji": FAQ zawsze jako pozycja submenu (gdy moduł włączony). --}}
        @if ($page->type === 'about' && $siteSettings->isModuleEnabled('faq'))
            <a href="{{ route('faq.index') }}" class="block px-4 py-2 text-sm font-medium normal-case {{ request()->routeIs('faq.index') ? 'text-brand' : 'text-ink' }} hover:bg-gray-50 hover:text-brand focus:bg-gray-50">
                Najczęstsze pytania (FAQ)
            </a>
        @endif
    </div>
</li>
