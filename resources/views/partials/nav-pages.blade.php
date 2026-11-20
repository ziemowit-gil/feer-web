@php $mobile ??= false; @endphp

@foreach (($navPages ?? collect()) as $page)
    @if ($page->menuChildren->isNotEmpty())
        @include('partials.nav-page-dropdown', ['page' => $page, 'mobile' => $mobile, 'transparent' => $item->is_transparent_dropdown])
    @else
        <li>
            @php
                $pageActive = request()->routeIs('page.show') && request()->route('page')?->id === $page->id;
                $ob      = $onBrand ?? false;
                $hoverW  = $ob && ($siteSettings->wide_mission_nav_hover_white  ?? true);
                $activeW = $ob && ($siteSettings->wide_mission_nav_active_white ?? true);
                $hoverCls  = $hoverW  ? 'hover:border-white hover:text-white hover:underline' : 'hover:border-brand hover:text-brand';
                $activeCls = $pageActive ? ($activeW ? 'border-white' : 'border-brand text-brand') : 'border-transparent';
            @endphp
            <a href="{{ $page->navUrl() }}"
                @if ($pageActive) aria-current="page" @endif
                class="block border-b-2 py-2 transition-colors {{ $hoverCls }} focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current {{ $activeCls }} {{ $mobile ? '' : 'pb-1' }}">
                {{ $page->navLabel() }}
            </a>
        </li>
    @endif
@endforeach
