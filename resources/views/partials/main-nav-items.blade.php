@php
    $navItems ??= collect();
    $mobile ??= false;
    $onBrand ??= false;
@endphp

<ul @class([
    'flex items-center gap-5 text-lg font-bold uppercase tracking-wide xl:gap-6' => ! $mobile,
    'nav-on-brand text-white' => ! $mobile && $onBrand,
    'text-ink' => ! $mobile && ! $onBrand,
    'flex flex-col gap-1 pt-3 text-lg font-bold uppercase tracking-wide text-ink' => $mobile,
])>
    @foreach ($navItems as $item)
        @if ($item->type === 'projects')
            @include('partials.nav-projects-dropdown', ['item' => $item, 'mobile' => $mobile])
        @elseif ($item->type === 'pages')
            @include('partials.nav-pages', ['item' => $item, 'mobile' => $mobile])
        @elseif ($item->type === 'dropdown')
            @include('partials.nav-dropdown', ['item' => $item, 'mobile' => $mobile])
        @else
            @include('partials.nav-item', ['item' => $item, 'mobile' => $mobile])
        @endif
    @endforeach
</ul>
