@php $mobile ??= false; @endphp

<li class="relative" x-data="{ open: false }" x-id="['dropdown']"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.projectsTrigger.focus()"
    @click.outside="open = false">
    @php
        $ob      = $onBrand ?? false;
        $hoverW  = $ob && ($siteSettings->wide_mission_nav_hover_white  ?? false);
        $activeW = $ob && ($siteSettings->wide_mission_nav_active_white ?? false);
        $hoverCls  = $hoverW  ? 'hover:border-white hover:text-white' : 'hover:border-brand hover:text-brand';
        $activeBdr = $ob ? ($activeW ? 'border-white' : 'border-brand text-brand') : 'border-brand text-brand';
        $staticCls = $item->isCurrent() ? $activeBdr : 'border-transparent';
    @endphp
    <button type="button" x-ref="projectsTrigger" @click="open = !open"
        :aria-expanded="open.toString()" :aria-controls="$id('dropdown')"
        class="flex w-full items-center gap-1 border-b-2 py-2 uppercase transition-colors {{ $hoverCls }} focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $staticCls }} {{ $mobile ? 'justify-between' : 'pb-1' }}" :class="open ? '{{ $activeBdr }}' : ''">
        {{ $item->label }} <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
    </button>

    <div :id="$id('dropdown')" x-show="open" x-cloak x-transition
        @class([
            'z-50 rounded-lg border border-gray-200 py-2 normal-case tracking-normal shadow-lg',
            'bg-white/90 backdrop-blur-sm' => $item->is_transparent_dropdown,
            'bg-white' => ! $item->is_transparent_dropdown,
            'absolute left-0 top-full mt-1 w-60' => ! $mobile,
            'static mt-1 w-full' => $mobile,
        ])>
        @forelse (($navCategories ?? collect()) as $category)
            <div class="group/cat relative">
                <a href="{{ route('categories.show', $category) }}" class="flex items-center justify-between px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50 hover:text-brand focus-visible:bg-gray-50">
                    {{ $category->name }}
                    @if (! $mobile && $category->publishedProjects->isNotEmpty())
                        <i class="fa-solid fa-chevron-right text-xs text-muted" aria-hidden="true"></i>
                    @endif
                </a>

                @if (! $mobile && $category->publishedProjects->isNotEmpty())
                    <div class="invisible absolute left-full top-0 z-50 ml-1 w-60 rounded-lg border border-gray-200 bg-white py-2 opacity-0 shadow-lg transition group-hover/cat:visible group-hover/cat:opacity-100 group-focus-within/cat:visible group-focus-within/cat:opacity-100">
                        @foreach ($category->publishedProjects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="block px-4 py-2 text-sm font-medium normal-case text-ink hover:bg-gray-50 hover:text-brand focus-visible:bg-gray-50">
                                {{ $project->title }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="px-4 py-2 text-sm normal-case text-muted">Brak kategorii projektów.</p>
        @endforelse

        <div class="mt-1 border-t border-gray-100 pt-1">
            <a href="{{ route('projects.index') }}" class="block px-4 py-2 text-sm font-bold normal-case text-brand hover:bg-gray-50 focus-visible:bg-gray-50">Wszystkie projekty →</a>
            @if ($navHasProjectArchive ?? false)
                <a href="{{ route('projects.archive') }}" class="block px-4 py-2 text-sm font-bold normal-case text-brand hover:bg-gray-50 focus-visible:bg-gray-50">To już zrobiliśmy →</a>
            @endif
        </div>

        @if ($siteSettings->isModuleEnabled('events'))
            {{-- Wyróżniony skrót do nadchodzących szkoleń/wydarzeń na końcu listy — przyciąga wzrok. --}}
            <a href="{{ route('events.index') }}"
                class="mx-2 mt-2 flex items-center gap-2 rounded-lg bg-brand px-3 py-2 text-sm font-bold normal-case text-white transition-colors hover:bg-brand-dark focus-visible:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <span class="relative flex h-2.5 w-2.5 flex-none" aria-hidden="true">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/70"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-white"></span>
                </span>
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Nadchodzące szkolenia
            </a>
        @endif
    </div>
</li>
