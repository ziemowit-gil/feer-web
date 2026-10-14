{{--
    Kolumna boczna portalu: menu (pozycje i podstrony z panelu, wygląd menu bocznego działania) i „Na skróty” jako lista.
    Expects (z portal/show): $navItems, $navTitle, $navItem, $shortcuts, $shortcutsTitle, $shortcutsInSidebar.
--}}
<div class="space-y-4 lg:sticky lg:top-4">
    <nav aria-labelledby="portal-nav" class="rounded-md bg-gray-50 p-5">
        <p id="portal-nav" class="text-base font-bold leading-snug text-ink">{{ $navTitle }}</p>
        <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
        @if ($navItems->isEmpty())
            <p class="px-3 text-sm text-ink">{{ $category->portalLabel('nav_empty') }}</p>
        @else
            <ul role="list" class="space-y-0.5">
                @foreach ($navItems as $item)
                    @if ($item->type === 'link')
                        <li><a href="{{ $item->url }}" class="{{ $navItem }}"><span class="min-w-0">{{ $item->label }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                    @elseif ($item->subs === 'none')
                        <li><a href="{{ route('projects.show', $item->project) }}" class="{{ $navItem }}"><span class="min-w-0">{{ $item->label }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                    @else
                        @php $subId = 'portal-sub-' . $item->project->id; $open = $item->subs === 'expanded'; @endphp
                        <li x-data="{ open: {{ $open ? 'true' : 'false' }} }">
                            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $subId }}" class="{{ $navItem }}">
                                <span class="min-w-0">{{ $item->label }}</span>
                                <i class="fa-solid fa-chevron-right flex-none text-xs transition-transform motion-reduce:transition-none {{ $open ? 'rotate-90' : '' }}" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i>
                            </button>
                            <ul id="{{ $subId }}" role="list" class="side-sub mt-0.5 space-y-0.5" x-show="open" @unless ($open) x-cloak @endunless>
                                <li><a href="{{ route('projects.show', $item->project) }}" class="{{ $navItem }} font-bold"><span class="min-w-0">{{ $category->portalLabel('overview') }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                                @foreach ($item->pages as $sub)
                                    <li><a href="{{ $sub->publicUrl() }}" class="{{ $navItem }}"><span class="min-w-0">{{ $sub->title }}</span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </nav>

    @if ($shortcutsInSidebar)
        <nav aria-labelledby="portal-shortcuts" class="rounded-md bg-gray-50 p-5">
            <p id="portal-shortcuts" class="text-base font-bold text-ink">{{ $shortcutsTitle }}</p>
            <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
            <ul role="list" class="space-y-0.5">
                @foreach ($shortcuts as [$label, $url, $icon])
                    <li><a href="{{ $url }}" class="{{ $navItem }}"><span class="flex min-w-0 items-center gap-3">@if ($icon)<i class="{{ $icon }} w-4 flex-none text-center text-brand-dark" aria-hidden="true"></i>@endif<span>{{ $label }}</span></span><i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i></a></li>
                @endforeach
            </ul>
        </nav>
    @endif
</div>
