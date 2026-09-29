@php
    $navItems ??= collect();
    $mobile ??= false;
    $onBrand ??= false;
    $navDarkText ??= false;
    // Substyl paska: 'underline' (domyślny), 'icons' (ikona nad etykietą), 'pills' (zakładki).
    $navStyle ??= (($iconsNav ?? false) ? 'icons' : 'underline');
    $iconsNav = $navStyle === 'icons';
@endphp

<ul @class([
    'flex items-center gap-5 text-lg font-bold uppercase tracking-wide xl:gap-6' => ! $mobile,
    'nav-icons gap-1 xl:gap-2' => ! $mobile && $navStyle === 'icons',
    'nav-pills gap-1 xl:gap-1.5 normal-case tracking-normal' => ! $mobile && $navStyle === 'pills',
    'nav-on-brand text-white' => ! $mobile && $onBrand && ! $navDarkText,
    'nav-on-brand-dark text-gray-900' => ! $mobile && $onBrand && $navDarkText,
    'text-ink' => ! $mobile && ! $onBrand,
    'flex flex-col gap-1 pt-3 text-lg font-bold uppercase tracking-wide text-ink' => $mobile,
])>
    @foreach ($navItems as $item)
        @if (! $mobile && $item->isMega())
            @include('partials.nav-mega', ['item' => $item, 'mobile' => false])
        @elseif ($item->type === 'projects')
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
